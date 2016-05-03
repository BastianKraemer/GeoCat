<?php
/* GeoCat - Geocaching and -tracking application
 * Copyright (C) 2015-2016 Bastian Kraemer, Raphael Harzer
 *
 * ChallengeManager.php
 *
 * This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * GeoCat challenge manager
 * @package app.challenge
 */

	require_once(__DIR__ . "/../DBTools.php");

	/**
	 * This class handles the interaction with challenges
	 */
	class ChallengeManager {

		CONST CHALLENGE_TYPE_ID_MAX = 1; // Max value for a challenge type id

		/**
		 * Get a challenge id by the challenge key
		 * @param PDO $dbh Database handler
		 * @param string $sessionKey
		 * @return integer the challenge id
		 */
		public static function getChallengeIdBySessionKey($dbh, $sessionKey){
			$res = DBTools::fetchAll($dbh, "SELECT challenge_id FROM Challenge WHERE sessionkey = :key", array("key" => strtoupper($sessionKey)));

			if(count($res) == 1){
				return $res[0][0];
			}
			else{
				return -1;
			}
		}

		/**
		 * Returns an array of all public challenges
		 * @param PDO $dbh Database handler
		 * @param integer $limit
		 * @param integer $offset
		 * @return array
		 */
		public static function getPublicChallengs($dbh, $limit = 1000, $offset = -1){
			$res = DBTools::fetchAll($dbh,	"SELECT Account.username AS owner_name, Challenge.challenge_id, Challenge.name, Challenge.description, " .
												"Challenge.challenge_type_id AS type_id, ChallengeType.full_name AS type_name, " .
												"Challenge.max_teams, Challenge.max_team_members, Challenge.predefined_teams, " .
												"Challenge.start_time, Challenge.end_time, Challenge.is_public, Challenge.sessionkey " .
											"FROM Challenge, Account, ChallengeType WHERE is_public = 1 AND Challenge.is_enabled = 1 " .
												"AND Account.account_id = Challenge.owner AND Challenge.challenge_type_id = ChallengeType.challenge_type_id " .
											"ORDER BY Challenge.start_time DESC" .
											($limit > 0 ? " LIMIT " . $limit : "") . ($offset > 0 ? " OFFSET " . $offset : ""),null, PDO::FETCH_ASSOC);

			return $res;
		}

		/**
		 * Returns a list of all challenges that has beeen created by a specific user
		 * @param PDO $dbh Database handler
		 * @param SessionManager $session
		 * @param integer $limit
		 * @param integer $offset
		 * @return array
		 */
		public static function getMyChallenges($dbh, $session, $limit = 1000, $offset = -1){
			$res = DBTools::fetchAll($dbh,	"SELECT Account.username AS owner_name, Challenge.challenge_id, Challenge.name, Challenge.description, " .
												"Challenge.challenge_type_id AS type_id, ChallengeType.full_name AS type_name, " .
												"Challenge.max_teams, Challenge.max_team_members, Challenge.predefined_teams, " .
												"Challenge.start_time, Challenge.end_time, Challenge.is_public, Challenge.sessionkey " .
											"FROM Challenge " .
											"JOIN Account ON (Challenge.owner = Account.account_id) " .
											"JOIN ChallengeType ON (Challenge.challenge_type_id = ChallengeType.challenge_type_id) " .
											"WHERE Challenge.owner = :accId " .
											"ORDER BY Challenge.start_time DESC" .
											($limit > 0 ? " LIMIT " . $limit : "") . ($offset > 0 ? " OFFSET " . $offset : ""),
											array("accId" => $session->getAccountId()), PDO::FETCH_ASSOC);
			return $res;
		}

		/**
		 * Returns a list of all challenges a specific user takes part of
		 * @param PDO $dbh Database handler
		 * @param SessionManager $session
		 * @param integer $limit
		 * @param integer $offset
		 * @return array
		 */
		public static function getParticipatedChallenges($dbh, $session, $limit = 1000, $offset = -1){
			$res = DBTools::fetchAll($dbh,	"SELECT Account.username AS owner_name, Challenge.challenge_id, Challenge.name, Challenge.description, " .
												"Challenge.challenge_type_id AS type_id, ChallengeType.full_name AS type_name, " .
												"Challenge.max_teams, Challenge.max_team_members, Challenge.predefined_teams, " .
												"Challenge.start_time, Challenge.end_time, Challenge.is_public, Challenge.sessionkey " .
											"FROM ChallengeMember " .
											"JOIN ChallengeTeam ON (ChallengeMember.team_id = ChallengeTeam.team_id) " .
											"JOIN Challenge ON (ChallengeTeam.challenge_id = Challenge.challenge_id) " .
											"JOIN Account ON (Challenge.owner = Account.account_id) " .
											"JOIN ChallengeType ON (Challenge.challenge_type_id = ChallengeType.challenge_type_id) " .
											"WHERE ChallengeMember.account_id = :accId " .
											"ORDER BY Challenge.start_time DESC" .
											($limit > 0 ? " LIMIT " . $limit : "") . ($offset > 0 ? " OFFSET " . $offset : ""),
											array("accId" => $session->getAccountId()), PDO::FETCH_ASSOC);
			return $res;
		}

		/**
		 * Checks if a challenge exists
		 * @param PDO $dbh Database handler
		 * @param integer $challengeId
		 * @return boolean
		 */
		public static function challengeExists($dbh, $challengeId){
			$res = DBTools::fetchAll($dbh, "SELECT COUNT(challenge_id) FROM Challenge WHERE challenge_id = :id", array("id" => $challengeId));

			return $res[0][0] == 1;
		}

		/**
		 * Returns all information about a challenge.
		 *
		 * <p>The following values are part of the result:</p>
		 * <ul>
		 * <li>owner</li>
		 * <li>owner_name</li>
		 * <li>name</li>
		 * <li>description</li>
		 * <li>max_teams</li>
		 * <li>max_team_members</li>
		 * <li>predefined_teams</li>
		 * <li>start_time</li>
		 * <li>end_time</li>
		 * <li>current_team_cnt</li>
		 * </ul>
		 * @param PDO $dbh Database handler
		 * @param integer $challengeId
		 * @return array An array with the values from above, or an empty array if there is no challenge with this id
		 */
		public static function getChallengeInformation($dbh, $challengeId){

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

		/**
		 * Returns al list of all chaches of a challenge
		 * @param PDO $dbh Database handler
		 * @param integer $challengeId
		 * @param boolean $includeCacheCodes (Optional)
		 * @return array
		 */
		public static function getChallengeCoordinates($dbh, $challengeId, $includeCacheCodes = false){

			$res = DBTools::fetchAll($dbh,	"SELECT ChallengeCoord.challenge_coord_id, ChallengeCoord.hint, ChallengeCoord.priority, ChallengeCoord.captured_by, ChallengeCoord.capture_time," .
												"Coordinate.coord_id, Coordinate.name, Coordinate.description, Coordinate.latitude, Coordinate.longitude, " .
												"(CASE WHEN ChallengeCoord.code IS NULL THEN 0 ELSE 1 END) AS code_required" .
												($includeCacheCodes ? ", ChallengeCoord.code " : " ") .
											"FROM ChallengeCoord, Coordinate " .
											"WHERE ChallengeCoord.challenge_id = :challengeId AND ChallengeCoord.coord_id = Coordinate.coord_id " .
											"ORDER BY ChallengeCoord.priority ASC",
										array("challengeId" => $challengeId), PDO::FETCH_ASSOC);
			return $res;
		}

		/**
		 * Checks a challenge key
		 * @param PDO $dbh Database handler
		 * @param integer $challengeId
		 * @param string $sessionkey The session key of a challenge
		 * @boolean <code>true</code> if there is a challenge with this key, <code>false</code> if not
		 */
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

		/**
		 * Returns the owner of a challenge
		 * @param PDO $dbh Database handler
		 * @param integer $challengeId The challenge id
		 * @return integer The account id of the owner or <code>-1</code> if there is no challenge with this id
		 */
		public static function getOwner($dbh, $challengeId){
			$res = DBTools::fetchNum($dbh, "SELECT owner FROM Challenge WHERE challenge_id = :id", array("id" => $challengeId));
			return $res ? $res[0] : -1;
		}

		/**
		 * Returns the name of the challenge owner
		 * @param PDO $dbh Database handler
		 * @param integer $ownerId The account id of the owner
		 * @return string The name of the owner, or <code>null</code > if there is no account with this id
		 */
		public static function getOwnerName($dbh, $ownerId){
			$res = DBTools::fetchNum($dbh, "SELECT account.username FROM Account where account.account_id = :id", array("id" => $ownerId));
			if($res){
				return $res[0];
			}
			else{
				return null;
			}
		}

		/**
		 * Returns al list of all members of a team
		 * @param PDO $dbh Database handler
		 * @param integer $teamid The team id
		 * @return string[] An array with the usernames of all team members
		 */
		public static function getTeamlistById($dbh, $teamid){
			$res = DBTools::fetchAll($dbh,	"SELECT Account.username " .
											"FROM Account " .
											"JOIN ChallengeMember ON (Account.account_id = ChallengeMember.account_id) " .
											"WHERE team_id = :id", array("id" => $teamid));
			return $res;
		}

		/**
		 * Return a list of all teams of a challenge
		 * @param PDO $dbh Database handler
		 * @param integer $challengeId The challenge id
		 * @return array
		 */
		public static function getTeams($dbh, $challengeId){
			return DBTools::fetchAll($dbh, "SELECT team_id, name, color FROM ChallengeTeam WHERE challenge_id = :id", array("id" => $challengeId), PDO::FETCH_ASSOC);
		}

		/**
		 * Returns an array of all teams of a challenge including the number of team members
		 * @param PDO $dbh Database handler
		 * @param integer $challengeId The challenge id
		 * @return array
		 */
		public static function getTeamsAndMemberCount($dbh, $challengeId){
			return DBTools::fetchAll($dbh,	"SELECT ChallengeTeam.team_id, ChallengeTeam.name, ChallengeTeam.color, " .
												"(CASE WHEN access_code IS NULL THEN 0 ELSE 1 END) AS has_code, " .
												"(SELECT COUNT(*) FROM ChallengeMember WHERE ChallengeMember.team_id = ChallengeTeam.team_id) as member_cnt " .
											"FROM ChallengeTeam WHERE challenge_id = :id", array("id" => $challengeId), PDO::FETCH_ASSOC);
		}

		/**
		 * Returns the number of teams of a specific challenge
		 * @param PDO $dbh Database handler
		 * @param integer $challengeId The challenge id
		 * @param integer The number of teams or <code>-1</code> if the challenge does not exist
		 */
		public static function getMaxNumberOfTeams($dbh, $challengeId){
			$res = DBTools::fetchNum($dbh, "SELECT max_teams FROM Challenge WHERE challenge_id = :id", array("id" => $challengeId));
			return $res ? $res[0] : -1;
		}

		/**
		 * Returns the member limit for teams of a challenge
		 * @param PDO $dbh Database handler
		 * @param integer $challengeId
		 * @return integer the member limit or <code>null</code> if the challenge does not exist
		 */
		public static function getMaxMembersPerTeam($dbh, $challengeId){
			$res = DBTools::fetchNum($dbh, "SELECT max_team_members FROM Challenge WHERE challenge_id = :id", array("id" => $challengeId));
			return $res ? $res[0] : null;
		}

		/**
		 * Checks if a challenge is bisible for everyone
		 * @param PDO $dbh Database handler
		 * @param integer $challengeId The challenge id
		 * @return boolean
		 */
		public static function isChallengePublic($dbh, $challengeId){
			return (self::getSingleValue($dbh, $challengeId, "is_public") == 1);
		}

		/**
		 * Checks if a challenge is enabled
		 * @param PDO $dbh Database handler
		 * @param integer $challengeId
		 * @return boolean
		 */
		public static function isChallengeEnabled($dbh, $challengeId){
			return (self::getSingleValue($dbh, $challengeId, "is_enabled") == 1);
		}

		/**
		 * Counts the number of teams of a challenge
		 * @param PDO $dbh Database handler
		 * @param integer $challengeId The challenge id
		 * @return integer
		 */
		public static function countExisitingTeams($dbh, $challengeId){
			$res = DBTools::fetchNum($dbh, "SELECT COUNT(team_id) FROM ChallengeTeam WHERE challenge_id = :id", array("id" => $challengeId));
			return $res[0];
		}

		/**
		 * Returns a list of all challanges a user takes part of
		 * @param PDO $dbh Database handler
		 * @param integer $accountId The account id
		 * @return array An array of challenge ids
		 */
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

		/**
		 * Creates an ew challenge
		 * @param PDO $dbh Database handler
		 * @param string $name The name of the challenge
		 * @param ChallengeType $challengeType The challenge type
		 * @param integer $owner_accId The account id of the owner
		 * @param string $description The challenge description
		 * @param integer $isPublic (Optional) Is the challenge visible for everyone (1 = yes; 0 = no)
		 * @param boolean $predefinedTeams (Optional) Are the teams predefined by the owner (1 = yes; 0 = no)
		 * @param number $max_teams (Optional)  The maximum number of teams
		 * @param number $maxTeamMembers (Optional) The limit of members per team
		 * @return string The sessinon key of the challenge
		 * @throws InvalidArgumentException if name or description are too long
		 * @throws Exception if the database returns an error
		 */
		public static function createChallenge($dbh, $name, $challengeType, $owner_accId, $description, $isPublic = 0, $predefinedTeams = 0, $max_teams = 4, $maxTeamMembers = 4){

			if(strlen($name) > 64){throw new InvalidArgumentException("The challenge name is too long.");}
			if(strlen($description) > 512){throw new InvalidArgumentException("The challenge description is too long.");}
			$sessionkey = self::generateSessionKey($dbh);

			$res = DBTools::query($dbh, "INSERT INTO Challenge " .
										"(challenge_id, challenge_type_id, owner, sessionkey, name, description, " .
										"predefined_teams, max_teams, max_team_members, " .
										"start_time, end_time, is_public, is_enabled) " .
										"VALUES " .
											"(DEFAULT, :type_id, :owner, :key, :name, :desc, :predefTeams, :maxTeams, :maxMembers, " .
											"DEFAULT, NULL, :isPublic, 0)",
									array(	"type_id" => $challengeType, "owner" => $owner_accId, "key" => $sessionkey, "name" => $name, "desc" => $description,
											"predefTeams" => $predefinedTeams, "maxTeams" => $max_teams, "maxMembers" => $maxTeamMembers, "isPublic" => $isPublic ? 1 : 0));

			if(!$res){
				error_log("Unable to INSERT into table Challenge. Database returned: " . $res);
				throw new Exception("Unable to access database.");
			}

			return $sessionkey;
		}

		/**
		 * Updates the name of a challenge
		 * @param PDO $dbh Database handler
		 * @param integer $challengenId The challenge id
		 * @param string $name The new name for the challenge
		 * @throws InvalidArgumentException if the name is too long
		 * @throws Exception if the database returns an error
		 */
		public static function updateName($dbh, $challengenId, $name){

			if(strlen($name) > 64){throw new InvalidArgumentException("The challenge name is too long.");}
			$res = DBTools::query($dbh, "UPDATE Challenge SET name = :name WHERE challenge_id = :challenge_id",
									array("name" => $name, "challenge_id" => $challengenId));

			if(!$res){
				error_log("Unable to update table Challenge. Database returned: " . $res);
				throw new Exception("Unable to access database.");
			}
		}

		/**
		 * Updates the description of a challenge
		 * @param PDO $dbh Database handler
		 * @param integer $challengenId The challenge id
		 * @param string $description The new description
		 * @throws InvalidArgumentException if the description is too long
		 * @throws Exception if the database returns an error
		 */
		public static function updateDescription($dbh, $challengenId, $description){

			if(strlen($description) > 512){throw new InvalidArgumentException("The challenge description is too long.");}
			$res = DBTools::query($dbh, "UPDATE Challenge SET description = :desc WHERE challenge_id = :challenge_id",
									array("desc" => $description, "challenge_id" => $challengenId));

			if(!$res){
				error_log("Unable to update table Challenge. Database returned: " . $res);
				throw new Exception("Unable to access database.");
			}
		}

		/**
		 * Sets a challenge private or public (visible for everyone)
		 * @param PDO $dbh Database handler
		 * @param integer $challengenId The challenge id
		 * @param integer $value 1 = public; 0 = private
		 */
		public static function setPublic($dbh, $challengenId, $value){
			self::updateSingleValue($dbh, $challengenId, "is_public", $value? 1 : 0);
		}

		/**
		 * Sets a the status of a challenge to 'enabled' or 'disabled'
		 * @param PDO $dbh Database handler
		 * @param integer $challengenId The challenge id
		 * @param integer $value 1 = enabled; 0 = disabled
		 */
		public static function setEnabled($dbh, $challengenId, $value){
			self::updateSingleValue($dbh, $challengenId, "is_enabled", $value ? 1 : 0);
		}

		/**
		 * Sets the start time of a challenge
		 * @param PDO $dbh Database handler
		 * @param integer $challengenId The challenge id
		 * @param string $newStartTime The new start time as timestamp
		 */
		public static function setStartTime($dbh, $challengenId, $newStartTime){
			self::updateSingleValue($dbh, $challengenId, "start_time", $newStartTime);
		}

		/**
		 * Sets the end time of a challenge
		 * @param PDO $dbh Database handler
		 * @param integer $challengenId The challenge id
		 * @param string $newEndTime The new end time as timestamp
		 */
		public static function setEndTime($dbh, $challengenId, $newEndTime){
			self::updateSingleValue($dbh, $challengenId, "end_time", $newEndTime);
		}

		/**
		 * Resets a challenge. The will remove all teams and their stats.
		 * @param PDO $dbh Database handler
		 * @param integer $challengenId The challenge id
		 * @throws InvalidArgumentException if the challenge does not exist
		 */
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

		/**
		 * Deletes a challenge
		 * @param PDO $dbh Database handler
		 * @param integer $challengenId The challenge id
		 * @throws Exception if the database returns an error
		 */
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


		/**
		 * Returns a single value from the challenge table
		 * @param PDO $dbh Database handler
		 * @param integer $challengenId The challenge id
		 * @param string $key The column name
		 * @return string The value for this key
		 */
		public static function getSingleValue($dbh, $challengenId, $key){
			$res = DBTools::fetchNum($dbh, "SELECT  " . $key . " FROM Challenge WHERE challenge_id = :challenge_id", array("challenge_id" => $challengenId));

			if($res){
				return $res[0];
			}
			else{
				return null;
			}
		}

		/**
		 * Updates a single value in the challenge table
		 * @param PDO $dbh Database handler
		 * @param integer $challengenId The challenge id
		 * @param string $key The column name
		 * @param string $value The value for this key
		 * @throws Exception if the database returns an error
		 */
		public static function updateSingleValue($dbh, $challengenId, $key, $value){
			$res = DBTools::query($dbh, "UPDATE Challenge SET " . $key . " = :value WHERE challenge_id = :challenge_id",
					array("value" => $value, "challenge_id" => $challengenId));

			if(!$res){
				error_log("Unable to update table Challenge. Database returned: " . $res);
				throw new Exception("Unable to access database.");
			}
		}

		/**
		 * Prettifies the sesion key by adding spaces
		 * @param string $key The session key
		 * @return string The prettified key
		 */
		public static function prettifySessionKey($key){
			$prettyfiedKey = "";

			for($i = 0; $i < strlen($key); $i += 2){
				$prettyfiedKey .= ($i == 0 ? "" : " ") . substr($key, $i, 2);
			}

			return $prettyfiedKey;
		}

		/**
		 * Generates a new session key
		 * @param PDO $dbh Database handler
		 * @param number $length (Optional) Number of charaacters
		 */
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
	}

	/**
	 * Enumeration for the challenge types
	 */
	abstract class ChallengeType
	{
		//Usage: $type = ChallengeType::DefaultChallenge;
		const DefaultChallenge = 0;
		const CaptureTheFlag = 1;
	}

?>
