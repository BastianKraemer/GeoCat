<?php
	/*	GeoCat - Geocaching and -Tracking platform
	 Copyright (C) 2016 Bastian Kraemer

	 TeamManager.php

	 This program is free software: you can redistribute it and/or modify
	 it under the terms of the GNU General Public License as published by
	 the Free Software Foundation, either version 3 of the License, or
	 (at your option) any later version.

	 This program is distributed in the hope that it will be useful,
	 but WITHOUT ANY WARRANTY; without even the implied warranty of
	 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	 GNU General Public License for more details.

	 You should have received a copy of the GNU General Public License
	 along with this program.  If not, see <http://www.gnu.org/licenses/>.
	 */

	require_once(__DIR__ . "/../DBTools.php");
	require_once(__DIR__ . "/ChallengeManager.php");
	require_once(__DIR__ . "/../AccountManager.php");

	class TeamManager {

		public static function createTeam($dbh, $challengeId, $teamName, $teamColor, $isPredefinedTeam, $access_code){

			if(!ChallengeManager::isValidChallengeName($teamName, 32, false)){throw new InvalidArgumentException("Invalid team name.");}
			if(ChallengeManager::countExisitingTeams($dbh, $challengeId) >= ChallengeManager::getMaxNumberOfTeams($dbh, $challengeId)){
				throw new InvalidArgumentException("Unable create new team: The maximal number of teams for this challenge is already reached.");
			}

			if($access_code == ""){$access_code = null;}

			$res = DBTools::query($dbh, "INSERT INTO ChallengeTeam (team_id, challenge_id, name, color, is_predefined, access_code) " .
										"VALUES (null, :id, :name, :color, :predef, :code)",
									array("id" => $challengeId, "name" => $teamName, "color" => $teamColor, "predef" => $isPredefinedTeam ? 1 : 0, "code" => $access_code));

			if(!$res){
				error_log("Unable to insert into table ChallengeTeam. Database returned: " . $res);
				throw new Exception("Unable to create team.");
			}

			return $dbh->lastInsertId("team_id");
		}

		public static function teamExists($dbh, $teamId){
			$res = DBTools::fetchAll($dbh, "SELECT COUNT(challenge_id) FROM ChallengeTeam WHERE team_id = :id", array("id" => $teamId));
			return $res[0][0] == 1;
		}

		public static function getChallengeIdOfTeam($dbh, $teamId){
			$res = DBTools::fetchAll($dbh, "SELECT challenge_id FROM ChallengeTeam WHERE team_id = :id", array("id" => $teamId));

			if($res){
				return $res[0][0];
			}
			else{
				return -1;
			}
		}

		public static function isMemberOfTeam($dbh, $teamId, $accountId){
			$res = DBTools::fetchAll($dbh, "SELECT COUNT(team_id) FROM ChallengeMember WHERE team_id = :team AND account_id = :accId",
					array("team" => $teamId, "accId" => $accountId));

			return $res[0][0] > 0;
		}

		public static function checkTeamAccessCode($dbh, $teamId, $accessCode){
			if(!self::teamExists($dbh, $teamId)){throw new InvalidArgumentException("The requested team does not exists.");}
			$res = DBTools::fetchAll($dbh, "SELECT access_code FROM ChallengeTeam WHERE team_id = :team", array("team" => $teamId));

			if($res[0][0] == null){return true;}
			return ($res[0][0] == $accessCode);
		}

		public static function joinTeam($dbh, $teamId, $me, $accessCode){
			// Check if the team exists
			if(!self::teamExists($dbh, $teamId)){throw new InvalidArgumentException("The requested team does not exists.");}

			$challengeId = self::getChallengeIdOfTeam($dbh, $teamId);

			if(!ChallengeManager::isChallengeEnabled($dbh, $challengeId)){
				throw new InvalidArgumentException("Unable to add user to team: Challenge is not enabled.");
			}

			// Check if the number of max. team members is already reached
			if(self::countTeamMembers($dbh, $teamId) >= ChallengeManager::getMaxMembersPerTeam($dbh, $challengeId)){
				throw new InvalidArgumentException("This team has already reached the maximum number of members");
			}

			$challenges = ChallengeManager::getChallengesOfUser($dbh, $me);

			// Verify that the user is not already a member of one team of this challenge
			if(in_array($challengeId, $challenges)){
				throw new InvalidArgumentException("The user has already jonied a team of this challenge.");
			}

			// Verify 'access_code'
			if(!self::checkTeamAccessCode($dbh, $teamId, $accessCode)){
				throw new InvalidArgumentException("Access denied: Invalid team access code.");
			}

			$res = DBTools::query($dbh, "INSERT INTO ChallengeMember (team_id, account_id) VALUES (:team, :accId)", array("team" => $teamId, "accId" =>  $me));

			if(!$res){
				error_log("Unable to insert into table ChallengeMember. Database returned: " . $res);
				throw new Exception("Unable to add user to team.");
			}
		}

		public static function leaveTeam($dbh, $teamId, $accountId){
			if(self::isMemberOfTeam($dbh, $teamId, $accountId)){
				$res = DBTools::query($dbh, "DELETE FROM ChallengeMember WHERE team_id = :team AND account_id = :accId", array("team" => $teamId, "accId" => $accountId));

				if(!$res){
					error_log("Unable to remove user from team. Database returned: " . $res);
					throw new Exception("Unable remove user from team.");
				}

				// Remove team if empty
				if(self::countTeamMembers($dbh, $teamId) == 0){self::cleanupEmptyTeam($dbh, $teamId, false);}
			}
		}

		public static function isFinalized($dbh, $teamId){
			$res = DBTools::fetchAll($dbh, "SELECT is_predefined FROM ChallengeTeam WHERE team_id = :team", array("team" => $teamId));

			if($res){
				return $res[0][0] == 1;
			}
			else{
				return false;
			}
		}

		public static function deleteTeam($dbh, $teamId){
			// Remove all team members
			$res = DBTools::query($dbh, "DELETE FROM ChallengeMember WHERE team_id = :team", array("team" => $teamId));

			if(!$res){
				error_log("Unable to delete team. Database returned: " . $res);
				throw new Exception("Unable to delete team.");
			}

			// Remove team
			self::cleanupEmptyTeam($dbh, $teamId, true);
		}

		protected static function cleanupEmptyTeam($dbh, $teamId, $force){
			if($force || !self::isFinalized($dbh, $teamId)){
				DBTools::query($dbh, "DELETE FROM ChallengeTeam WHERE team_id = :team", array("team" => $teamId));
			}
		}

		public static function countTeamMembers($dbh, $teamId){
			$res = DBTools::fetchAll($dbh, "SELECT COUNT(account_id) FROM ChallengeMember WHERE team_id = :team", array("team" => $teamId));
			return $res[0][0];
		}
	}

?>