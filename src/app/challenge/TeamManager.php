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

/**
 * GeoCat TeamManager
 * @package app.challenge
 */

	require_once(__DIR__ . "/../DBTools.php");
	require_once(__DIR__ . "/ChallengeManager.php");
	require_once(__DIR__ . "/../AccountManager.php");

	/**
	 * Handle interaction with GeoCat challenge teams
	 */
	class TeamManager {

		/**
		 * Create a new team
		 * @param PDO $dbh Database handler
		 * @param integer $challengeId Your challenge
		 * @param string $teamName Your teamname
		 * @param string  $teamColor The team color
		 * @param boolean $isPredefinedTeam Is it a predefined team
		 * @param string $access_code The code for joining the team
		 * @return integer The team id
		 * @throws InvalidArgumentException if the maximal number of teams for this challenge is already reached
		 * @throws Exception if the team could not be stored in the database
		 */
		public static function createTeam($dbh, $challengeId, $teamName, $teamColor, $isPredefinedTeam, $access_code){

			$maxNumberOfTeams = ChallengeManager::getMaxNumberOfTeams($dbh, $challengeId);
			if($maxNumberOfTeams > 0){
				if(ChallengeManager::countExisitingTeams($dbh, $challengeId) > $maxNumberOfTeams){
					throw new InvalidArgumentException("Unable create new team: The maximal number of teams for this challenge is already reached.");
				}
			}

			if($access_code == ""){$access_code = null;}

			$res = DBTools::query($dbh, "INSERT INTO ChallengeTeam (team_id, challenge_id, name, color, is_predefined, access_code) " .
										"VALUES (DEFAULT, :id, :name, :color, :predef, :code)",
									array("id" => $challengeId, "name" => $teamName, "color" => $teamColor, "predef" => ($isPredefinedTeam ? 1 : 0), "code" => $access_code));

			if(!$res){
				error_log("Unable to insert into table ChallengeTeam. Database returned: " . $res);
				throw new Exception("Unable to create team.");
			}

			return $dbh->lastInsertId("challengeteam_team_id_seq");
		}

		/**
		 * Checks if a team exists
		 * @param PDO $dbh Database handler
		 * @param integer $teamId The teamid
		 * @return boolean
		 */
		public static function teamExists($dbh, $teamId){
			$res = DBTools::fetchAll($dbh, "SELECT COUNT(challenge_id) FROM ChallengeTeam WHERE team_id = :id", array("id" => $teamId));
			return $res[0][0] == 1;
		}

		/**
		 * Checks if a team exists
		 * @param PDO $dbh Database handler
		 * @param integer $challengeId The challenge of this team
		 * @param string $teamName The teamname
		 * @return boolean
		 */
		public static function teamWithNameExists($dbh, $challengeId, $teamName){
			$res = DBTools::fetchNum($dbh,	"SELECT COUNT(challenge_id) " .
											"FROM ChallengeTeam " .
											"WHERE challenge_id = :cid AND name = :name",
											array("cid" => $challengeId, "name" => $teamName));
			return $res[0] >= 1;
		}

		/**
		 * Get team name and color from of a team
		 * @param PDO $dbh Database handler
		 * @param integer $teamId
		 * @return array
		 */
		public static function getTeamInfo($dbh, $teamId){
			return DBTools::fetchAssoc($dbh, "SELECT ChallengeTeam.name, ChallengeTeam.color, ChallengeTeam.starttime " .
											 "FROM ChallengeTeam WHERE ChallengeTeam.team_id = :teamId",
											 array("teamId" => $teamId));
		}

		/**
		 * Returns all members of a team
		 * @param PDO $dbh Database handler
		 * @param integer $teamId
		 * @return mixed[]
		 */
		public static function getTeamMembers($dbh, $teamId){
			$res = DBTools::fetchAll($dbh,	"SELECT Account.username " .
											"FROM Account, ChallengeMember " .
											"WHERE Account.account_id = ChallengeMember.account_id AND ChallengeMember.team_id = :teamId",
											array("teamId" => $teamId), PDO::FETCH_NUM);

			$ret = array();
			for($i = 0; $i < count($res); $i++){
				$ret[] = $res[$i][0];
			}

			return $ret;
		}

		/**
		 * Returns the challenge id of a team
		 * @param PDO $dbh Database handler
		 * @param integer $teamId The team id
		 * @return integer The challenge id
		 */
		public static function getChallengeIdOfTeam($dbh, $teamId){
			$res = DBTools::fetchAll($dbh, "SELECT challenge_id FROM ChallengeTeam WHERE team_id = :teamId", array("teamId" => $teamId));

			if($res){
				return $res[0][0];
			}
			else{
				return -1;
			}
		}

		/**
		 * Returns the team id of a user identified by its account id
		 * @param PDO $dbh Database handler
		 * @param integer $challengeId The challenge id
		 * @param integer $accId The account id
		 * @return integer The team id or <code>-1</code> if the user has not joind any team yet
		 */
		public static function getTeamOfUser($dbh, $challengeId, $accId){
			$res = DBTools::fetchNum($dbh,	"SELECT ChallengeTeam.team_id " .
											"FROM ChallengeTeam, ChallengeMember, Account " .
											"WHERE ChallengeMember.team_id = ChallengeTeam.team_id AND ChallengeMember.account_id = Account.account_id " .
												"AND ChallengeTeam.challenge_id = :challengeId AND ChallengeMember.account_id = :accId",
											array("challengeId" => $challengeId, "accId" => $accId));

			return ($res[0] != "" ? $res[0] : -1);
		}

		/**
		 * Checks if the user is a member of a team
		 * @param PDO $dbh Database handler
		 * @param integer $teamId The team id
		 * @param integer $accountId  The account id
		 * @return boolean
		 */
		public static function isMemberOfTeam($dbh, $teamId, $accountId){
			$res = DBTools::fetchAll($dbh, "SELECT COUNT(team_id) FROM ChallengeMember WHERE team_id = :team AND account_id = :accId",
					array("team" => $teamId, "accId" => $accountId));

			return $res[0][0] > 0;
		}

		/**
		 * Checks the access code of a team
		 * @param PDO $dbh Database handler
		 * @param integer $teamId The team id
		 * @param string $accessCode the access code
		 * @return boolean
		 * @throws InvalidArgumentException if the team does not exist
		 */
		public static function checkTeamAccessCode($dbh, $teamId, $accessCode){
			if(!self::teamExists($dbh, $teamId)){throw new InvalidArgumentException("The requested team does not exists.");}
			$res = DBTools::fetchAll($dbh, "SELECT access_code FROM ChallengeTeam WHERE team_id = :team", array("team" => $teamId));

			if($res[0][0] == null){return true;}
			return ($res[0][0] == $accessCode);
		}

		/**
		 * Add a user to a team
		 * @param PDO $dbh Database handler
		 * @param integer $teamId The team id
		 * @param integer $me Your account id
		 * @param string $accessCode The access code for this team
		 * @throws InvalidArgumentException if the team does not exist
		 * @throws Exception if the operation fails
		 */
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
				throw new InvalidArgumentException("The user has already joined a team of this challenge.");
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

		/**
		 * Remove a user from a team
		 * @param PDO $dbh Database handler
		 * @param integer $teamId the team id
		 * @param integer $accountId the account id
		 * @throws Exception if the operation fails
		 */
		public static function leaveTeam($dbh, $teamId, $accountId){
			if(self::isMemberOfTeam($dbh, $teamId, $accountId)){
				$res = DBTools::query($dbh, "DELETE FROM ChallengeMember WHERE team_id = :team AND account_id = :accId", array("team" => $teamId, "accId" => $accountId));

				if(!$res){
					error_log("Unable to remove user from team. Database returned: " . $res);
					throw new Exception("Unable remove user from team.");
				}

				// Remove team if empty
				if(self::countTeamMembers($dbh, $teamId) == 0){
					self::cleanupEmptyTeam($dbh, $teamId, false);
				}
			}
		}

		/**
		 * Checks if the team is a 'predefined' team. (Predefined teams are created by the owner and cannot be deleted by their members)
		 * @param PDO $dbh Database handler
		 * @param integer $teamId The team id
		 * @return boolean
		 */
		public static function isPredefinedTeam($dbh, $teamId){
			$res = DBTools::fetchAll($dbh, "SELECT is_predefined FROM ChallengeTeam WHERE team_id = :team", array("team" => $teamId));

			if($res){
				return $res[0][0] == 1;
			}
			else{
				return false;
			}
		}

		/**
		 * Deletes a team
		 * @param PDO $dbh Database handler
		 * @param integer $teamId The team id
		 * @throws Exception if the team cannot be deleted
		 */
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

		/**
		 * Removes a team if it is NOT predefined and without members
		 * @param PDO $dbh Database handler
		 * @param integer $teamId The team id
		 * @param boolean $force Force to delete this team
		 */
		private static function cleanupEmptyTeam($dbh, $teamId, $force){
			if($force || !self::isPredefinedTeam($dbh, $teamId)){

				require_once(__DIR__ . "/Checkpoint.php");

				Checkpoint::clearCheckpointsOfTeam($dbh, $teamId);
				DBTools::query($dbh, "DELETE FROM ChallengeTeam WHERE team_id = :team", array("team" => $teamId));
			}
		}

		/**
		 * Counts the members of a team
		 * @param PDO $dbh Database handler
		 * @param integer $teamId The team id
		 * @return integer The number of team members
		 */
		public static function countTeamMembers($dbh, $teamId){
			$res = DBTools::fetchAll($dbh, "SELECT COUNT(account_id) FROM ChallengeMember WHERE team_id = :team", array("team" => $teamId));
			return $res[0][0];
		}
	}

?>