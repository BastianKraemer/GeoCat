<?php
	/*	GeoCat - Geocaching and -Tracking platform
	 Copyright (C) 2015-2016 Bastian Kraemer

	 ChallengeManager.php

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

	class ChallengeManager {

		CONST CHALLENGE_TYPE_ID_MAX = 1; // Max value for a challenge type id

		public static function getChallengeIdBySessionKey($dbh, $sessionKey){
			$res = DBTools::fetchAll($dbh, "SELECT challenge_id FROM Challenge WHERE sessionkey = :key", array("key" => strtoupper($sessionKey)));

			if(count($res) == 1){
				return $res[0][0];
			}
			else{
				return -1;
			}
		}

		public static function getPublicChallengs($dbh, $limit = -1, $offset = -1){
			$res = DBTools::fetchAll($dbh,	"SELECT Account.username AS owner_name, Challenge.challenge_id, Challenge.name, Challenge.description, " .
												"Challenge.challenge_type_id AS type_id, ChallengeType.full_name AS type_name, " .
												"Challenge.max_teams, Challenge.max_team_members, Challenge.predefined_teams, " .
												"Challenge.start_time, Challenge.end_time, Challenge.is_public, Challenge.sessionkey " .
											"FROM Challenge, Account, ChallengeType WHERE is_public = 1 AND Challenge.is_enabled = 1 " .
												"AND Account.account_id = Challenge.owner AND Challenge.challenge_type_id = ChallengeType.challenge_type_id" .
											($limit > 0 ? " LIMIT " . $limit : "") . ($offset > 0 ? " OFFSET " . $offset : ""),null, PDO::FETCH_ASSOC);

			return $res;
		}
		
		public static function getMyChallenges($dbh, $session){
			$res = DBTools::fetchAll($dbh, "SELECT Account.username, Challenge.name, Challenge.description, ChallengeType.full_name, Challenge.sessionkey, Challenge.start_time, Challenge.is_enabled " .
									 "FROM Challenge " .
									 "JOIN Account ON (Challenge.owner = Account.account_id) " .
									 "JOIN ChallengeType ON (Challenge.challenge_type_id = ChallengeType.challenge_type_id) " .
									 "WHERE Challenge.owner = " . $session->getAccountId() . ";", null, PDO::FETCH_ASSOC);
			return $res; 
		}

		public static function countPublicChallenges($dbh){
			$res = DBTools::fetch($dbh,	"SELECT COUNT(Challenge.challenge_id) FROM Challenge WHERE is_public = 1 AND Challenge.is_enabled = 1", null, PDO::FETCH_NUM);
			return $res[0];
		}
		
		public static function countMyChallenges($dbh, $session){
			$res = DBTools::fetch($dbh, "SELECT COUNT(Challenge.challenge_id) " .
								  "FROM Challenge " .
								  "WHERE Challenge.owner = " . $session->getAccountId() . ";" , null, PDO::FETCH_NUM);
			return $res[0];
		}

		public static function challengeExists($dbh, $challengeId){
			$res = DBTools::fetchAll($dbh, "SELECT COUNT(challenge_id) FROM Challenge WHERE challenge_id = :id", array("id" => $challengeId));

			return $res[0][0] == 1;
		}

		public static function getChallengeInformation($dbh, $challengeId){

			/*
			 * Values of the result:
			 * owner
			 * owner_name
			 * name
			 * description
			 * max_teams
			 * max_team_members
			 * predefined_teams
			 * start_time
			 * end_time
			 * current_team_cnt
			 */

			$res = DBTools::fetchAssoc($dbh,"SELECT Challenge.challenge_id, Challenge.owner, Account.username AS owner_name, Challenge.name, Challenge.description, " .
													"Challenge.challenge_type_id AS type_id, ChallengeType.full_name AS type_name, " .
													"Challenge.max_teams, Challenge.max_team_members, Challenge.predefined_teams, " .
													"Challenge.start_time, Challenge.end_time, Challenge.is_public " .
											"FROM Challenge, Account, ChallengeType " .
											"WHERE Challenge.challenge_id = :challengeId AND Challenge.owner = Account.account_id AND ChallengeType.challenge_type_id = Challenge.challenge_type_id",
									array("challengeId" => $challengeId));

			if($res){
				$res["current_team_cnt"] = self::countExisitingTeams($dbh, $challengeId);
				return $res;
			}
			else{
				return array();
			}
		}

		public static function getChallengeCoordinates($dbh, $challengeId, $includeCacheCodes = false){

			$res = DBTools::fetchAll($dbh,	"SELECT ChallengeCoord.challenge_coord_id, ChallengeCoord.hint, ChallengeCoord.priority, ChallengeCoord.captured_by, ChallengeCoord.capture_time," .
												"Coordinate.coord_id, Coordinate.name, Coordinate.description, Coordinate.latitude, Coordinate.longitude, " .
												"(NOT ISNULL(ChallengeCoord.code)) AS code_required" . ($includeCacheCodes ? ", ChallengeCoord.code " : " ") .
											"FROM ChallengeCoord, Coordinate " .
											"WHERE ChallengeCoord.challenge_id = :challengeId AND ChallengeCoord.coord_id = Coordinate.coord_id " .
											"ORDER BY ChallengeCoord.priority ASC",
										array("challengeId" => $challengeId), PDO::FETCH_ASSOC);
			return $res;
		}

		public static function checkChallengeKey($dbh, $challengeId, $sessionkey){
			$res = DBTools::fetchNum($dbh, "SELECT Challenge.sessionkey FROM Challenge WHERE Challenge.challenge_id = :challengeId",
										array("challengeId" => $challengeId));

			if($res){
				if(count($res) == 1){
					return ($sessionkey == $res[0]);
				}
				else{
					return false;
				}
			}
			else{
				return false;
			}
		}

		public static function getOwner($dbh, $challengeId){
			$res = DBTools::fetchNum($dbh, "SELECT owner FROM Challenge WHERE challenge_id = :id", array("id" => $challengeId));
			return $res ? $res[0] : -1;
		}
		
		public static function getTeamlistById($dbh, $teamid){
			$res = DBTools::fetchAll($dbh, "SELECT Account.username " . 
									 "FROM Account " . 
									 "JOIN Challengemember ON (Account.account_id = Challengemember.account_id) " . 
									 "WHERE team_id = :id", array("id" => $teamid));
			return $res; 
		}

		public static function getTeams($dbh, $challengeId){
			return DBTools::fetchAll($dbh, "SELECT team_id, name, color FROM ChallengeTeam WHERE challenge_id = :id", array("id" => $challengeId), PDO::FETCH_ASSOC);
		}

		public static function getTeamsAndMemberCount($dbh, $challengeId){
			return DBTools::fetchAll($dbh,	"SELECT ChallengeTeam.team_id, ChallengeTeam.name, ChallengeTeam.color, " .
												"(CASE WHEN access_code IS NULL THEN 0 ELSE 1 END) AS has_code, " .
												"(SELECT COUNT(*) FROM ChallengeMember WHERE ChallengeMember.team_id = ChallengeTeam.team_id) as member_cnt " .
											"FROM ChallengeTeam WHERE challenge_id = :id", array("id" => $challengeId), PDO::FETCH_ASSOC);
		}

		public static function getMaxNumberOfTeams($dbh, $challengeId){
			$res = DBTools::fetchNum($dbh, "SELECT max_teams FROM Challenge WHERE challenge_id = :id", array("id" => $challengeId));
			return $res ? $res[0] : -1;
		}

		public static function getMaxMembersPerTeam($dbh, $challengeId){
			$res = DBTools::fetchNum($dbh, "SELECT max_team_members FROM Challenge WHERE challenge_id = :id", array("id" => $challengeId));
			return $res ? $res[0] : null;
		}

		public static function isChallengePublic($dbh, $challengeId){
			return (self::getSingleValue($dbh, $challengeId, "is_public") == 1);
		}

		public static function isChallengeEnabled($dbh, $challengeId){
			return (self::getSingleValue($dbh, $challengeId, "is_enabled") == 1);
		}

		public static function countExisitingTeams($dbh, $challengeId){
			$res = DBTools::fetchNum($dbh, "SELECT COUNT(team_id) FROM ChallengeTeam WHERE challenge_id = :id", array("id" => $challengeId));
			return $res[0];
		}

		public static function getChallengesOfUser($dbh, $accountId){
			$res = DBTools::fetchAll($dbh,	"SELECT ChallengeTeam.challenge_id " .
											"FROM ChallengeMember, ChallengeTeam " .
											"WHERE ChallengeMember.account_id = :accId AND ChallengeMember.team_id = ChallengeTeam.team_id",
											array("accId" => $accountId));

			$ret = array();

			if($res){
				for($i = 0; $i < count($res); $i++){
					$ret[] = $res[$i]["challenge_id"];
				}
			}

			return $ret;
		}

		public static function createChallenge($dbh, $name, $challengeType, $owner_accId, $description, $isPublic = 0, $startTime = null, $endTime = null, $predefinedTeams = 0, $max_teams = 4, $maxTeamMembers = 4){

			if(!self::isValidChallengeName($name, 64, false)){throw new InvalidArgumentException("Invalid challenge name");}
			if(!self::isValidChallengeName($description, 512, false)){throw new InvalidArgumentException("Invalid challenge description");}

			$sessionkey = self::generateSessionKey($dbh);

			$res = DBTools::query($dbh, "INSERT INTO Challenge " .
										"(challenge_id, challenge_type_id, owner, sessionkey, name, description, " .
										"predefined_teams, max_teams, max_team_members, " .
										"start_time, end_time, is_public, is_enabled) " .
										"VALUES " .
											"(DEFAULT, :type_id, :owner, :key, :name, :desc, :predefTeams, :maxTeams, :maxMembers, " .
											":startTime, :endTime, :isPublic, 0)",
									array(	"type_id" => $challengeType, "owner" => $owner_accId, "key" => $sessionkey, "name" => $name, "desc" => $description,
											"predefTeams" => $predefinedTeams, "maxTeams" => $max_teams, "maxMembers" => $maxTeamMembers,
											"startTime" => $startTime, "endTime" => $endTime, "isPublic" => $isPublic ? 1 : 0));

			if(!$res){
				error_log("Unable to INSERT into table Challenge. Database returned: " . $res);
				throw new Exception("Unable to access database.");
			}

			return self::getChallengeIdBySessionKey($dbh, $sessionkey);
		}

		public static function updateName($dbh, $challengenId, $name){

			if(!self::isValidChallengeName($name, 64, false)){throw new InvalidArgumentException("Invalid challenge name");}
			$res = DBTools::query($dbh, "UPDATE Challenge SET name = :name WHERE challenge_id = :challenge_id",
									array("name" => $name, "challenge_id" => $challengenId));

			if(!$res){
				error_log("Unable to update table Challenge. Database returned: " . $res);
				throw new Exception("Unable to access database.");
			}
		}

		public static function updateDescription($dbh, $challengenId, $description){

			if(!self::isValidChallengeName($description, 512, false)){throw new InvalidArgumentException("Invalid challenge description");}
			$res = DBTools::query($dbh, "UPDATE Challenge SET description = :desc WHERE challenge_id = :challenge_id",
									array("desc" => $description, "challenge_id" => $challengenId));

			if(!$res){
				error_log("Unable to update table Challenge. Database returned: " . $res);
				throw new Exception("Unable to access database.");
			}
		}

		public static function setPublic($dbh, $challengenId, $value){
			self::updateSingleValue($dbh, $challengenId, "is_public", $value? 1 : 0);
		}

		public static function setEnabled($dbh, $challengenId, $value){
			self::updateSingleValue($dbh, $challengenId, "is_enabled", $value ? 1 : 0);
		}

		public static function setStartTime($dbh, $challengenId, $newStartTime){
			self::updateSingleValue($dbh, $challengenId, "start_time", $newStartTime);
		}

		public static function setEndTime($dbh, $challengenId, $newEndTime){
			self::updateSingleValue($dbh, $challengenId, "end_time", $newEndTime);
		}

		public static function resetChallenge($dbh, $challengeId){

			if(!self::challengeExists($dbh, $challengeId)){
				throw new InvalidArgumentException("The challenge does not exist.");
			}

			require_once(__DIR__ . "/ChallengeCoord.php");
			require_once(__DIR__ . "/TeamManager.php");
			require_once(__DIR__ . "/Checkpoint.php");

			$teams = self::getTeams($dbh, $challengeId);

			ChallengeCoord::resetCaptureFlag($dbh, $challengeId);

			// Remove checkpoints and teams
			foreach ($teams as $t){
				Checkpoint::clearCheckpointsOfTeam($dbh, $t["team_id"]);
				TeamManager::deleteTeam($dbh, $t["team_id"]);
			}
		}

		public static function deleteChallenge($dbh, $challengeId){

			self::resetChallenge($dbh, $challengeId);

			require_once(__DIR__ . "/ChallengeCoord.php");
			ChallengeCoord::removeByChallenge($dbh, $challengeId);

			$res = DBTools::query($dbh, "DELETE FROM Challenge WHERE challenge_id = :challengeId", array("challengeId" => $challengeId));

			if(!$res){
					error_log("Error: Unable to delete row in table Challenge. Database retuned '" . $res . "' (" . __METHOD__ . "@" .__CLASS__ . ")");
					throw new Exception("Unable to remove challenge.");
			}

		}

		public static function getSingleValue($dbh, $challengenId, $key){
			$res = DBTools::fetchNum($dbh, "SELECT  " . $key . " FROM Challenge WHERE challenge_id = :challenge_id", array("challenge_id" => $challengenId));

			if($res){
				return $res[0];
			}
			else{
				return null;
			}
		}

		public static function updateSingleValue($dbh, $challengenId, $key, $value){
			$res = DBTools::query($dbh, "UPDATE Challenge SET " . $key . " = :value WHERE challenge_id = :challenge_id",
					array("value" => $value, "challenge_id" => $challengenId));

			if(!$res){
				error_log("Unable to update table Challenge. Database returned: " . $res);
				throw new Exception("Unable to access database.");
			}
		}

		public static function prettifySessionKey($key){
			$prettyfiedKey = "";

			for($i = 0; $i < strlen($key); $i += 2){
				$prettyfiedKey .= ($i == 0 ? "" : " ") . substr($key, $i, 2);
			}

			return $prettyfiedKey;
		}

		public static function generateSessionKey($dbh, $length = 4){

			$arr = str_split(str_shuffle("0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789"));
			$code = "";
			foreach (array_rand($arr, $length) as $x){
				$code .= $arr[$x];
			}

			if(DBTools::fetchAll($dbh, "SELECT COUNT(challenge_id) FROM Challenge WHERE sessionkey = :code", array("code" => $code))[0][0] > 0){
				// The key is already in use, generate another one.
				return self::generateSessionKey($dbh, $length);
			}
			else{
				return $code;
			}
		}


		/**
		 * Verifies thata challenge name is calid
		 * This function returns <code>true</code> if the string does not contain "<" or ">" and has less than $maxLength characters.
		 * @param string str
		 * @return boolean
		 */
		public static function isValidChallengeName($str, $maxLength, $allowEmptyStr){
			if($str == null || $str == ""){return $allowEmptyStr;}

			return preg_match("/^[^<>]{1," . $maxLength . "}$/", $str);
		}

	}

	abstract class ChallengeType
	{
		//Usage: $type = ChallengeType::DefaultChallenge;
		const DefaultChallenge = 0;
		const CaptureTheFlag = 1;
	}

?>
