<?php
/* GeoCat - Geocaching and -tracking application
 * Copyright (C) 2016 Bastian Kraemer, Raphael Harzer
 *
 * ChallengeCoord.php
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
 * GeoCat challenge coordinate manager
 * @package app.challenge
 */

	require_once(__DIR__ . "/../DBTools.php");

	/**
	 * This class handles the interaction with challenge coordinates (caches)
	 */
	class ChallengeCoord {

		/**
		 * Create a new challenge coordinate (respectively cache)
		 * @param PDO $dbh Database handler
		 * @param integer $challengeId The challenge id
		 * @param integer $coordId The coordinate id
		 * @param integer $priority The priority of this chache
		 * @param string $hint The cache hint
		 * @param string $code The codeword of this cache
		 * @throws InvalidArgumentException if a parameter has an invalid value
		 * @throws Exception if the database returns an error
		 */
		public static function create($dbh, $challengeId, $coordId, $priority, $hint, $code){

			require_once(__DIR__ . "/../CoordinateManager.php");
			require_once(__DIR__ . "/ChallengeManager.php");

			$code = self::verifyCodeValue($code);

			if($hint != null){
				$hint = htmlspecialchars($hint, ENT_QUOTES);
				if(strlen($hint) > 256){
					throw new InvalidArgumentException("The maximum length for the hint field is limited to 256 characters.");
				}
			}

			if(!CoordinateManager::coordinateExists($dbh, $coordId)){
				throw new InvalidArgumentException("Coordinate does not exist.");
			}

			if(!ChallengeManager::challengeExists($dbh, $challengeId)){
				throw new InvalidArgumentException("Challenge does not exist.");
			}

			$coords = ChallengeManager::getChallengeCoordinates($dbh, $challengeId);
			for($i = 0; $i < count($coords); $i++){
				if($coords[$i]["coord_id"] == $coordId){
					throw new InvalidArgumentException("The coordinate has been already added to this challenge.");
				}
			}

			$res = DBTools::query($dbh, "INSERT INTO ChallengeCoord " .
											"(challenge_coord_id, challenge_id, coord_id, priority, hint, code) " .
										"VALUES (DEFAULT, :challengeId, :coordId, :priority, :hint, :code)",
									array(	"challengeId" => $challengeId, "coordId" => $coordId, "priority" => $priority,
											"hint" => $hint, "code" => $code));

			if($res){
				return $dbh->lastInsertId("challengecoord_challenge_coord_id_seq");
			}
			else{
				error_log("Error: Unable to insert into table ChallengeCoord. Database retuned '" . $res . "' (" . __METHOD__ . "@" .__CLASS__ . ")");
				throw new Exception("Unable to append coordinate to challenge");
			}
		}

		/**
		 * Returns the challenge that is assigned to this challenge coordinate
		 * @param PDO $dbh Database handler
		 * @param integer $challengeCoordId
		 * @return integer the challenge id
		 */
		public static function getChallengeOfCoordinate($dbh, $challengeCoordId){
			$res = DBTools::fetchNum($dbh, "SELECT challenge_id FROM ChallengeCoord WHERE challenge_coord_id = :ccid", array("ccid" => $challengeCoordId));
			return $res ? $res[0] : -1;
		}

		/**
		 * Return the coordinate id of a cache
		 * @param PDO $dbh Database handler
		 * @param integer $challengeCoordId
		 * @return integer The coordinate id or <code>-1</code> if there is no cache with this id
		 */
		public static function getCoordinate($dbh, $challengeCoordId){
			$res = DBTools::fetchNum($dbh, "SELECT coord_id FROM ChallengeCoord WHERE challenge_coord_id = :ccid", array("ccid" => $challengeCoordId));

			return $res ? $res[0] : -1;
		}

		/**
		 * Counts the coordinates of a challenge
		 * @param PDO $dbh Database handler
		 * @param integer $challengeId The challenge id
		 * @param boolean $onlyWithPriority0 If set, this function returns <code>1</code> if there is already a start point defined, false if not
		 * @return mixed
		 */
		public static function countCoordsOfChallenge($dbh, $challengeId, $onlyWithPriority0){
			$res = DBTools::fetchNum($dbh,	"SELECT COUNT(coord_id) FROM ChallengeCoord " .
											"WHERE challenge_id = :cid" . ($onlyWithPriority0 ? " AND priority = 0" : ""), array("cid" => $challengeId));
			return $res[0];
		}

		/**
		 * Returns the start point of a challenge
		 * @param PDO $dbh Database handler
		 * @param integer $challengeId The challenge id
		 * @return integer The challenge_coord_id of teh challenge start point
		 */
		public static function getPriority0Coord($dbh, $challengeId){
			return DBTools::fetchNum($dbh, "SELECT challenge_coord_id FROM ChallengeCoord WHERE challenge_id = :cid AND priority = 0 LIMIT 1", array("cid" => $challengeId));
		}

		/**
		 * Updates the data of a cache
		 * @param PDO $dbh Database handler
		 * @param integer $challengeCoordId
		 * @param integer $priority
		 * @param string $hint
		 * @param string $code
		 * @throws Exception if the database returns an error
		 */
		public static function update($dbh, $challengeCoordId, $priority, $hint, $code){

			$code = self::verifyCodeValue($code);

			$res = DBTools::query($dbh, "UPDATE ChallengeCoord " .
										"SET priority = :priority, hint = :hint, code = :code " .
										"WHERE challenge_coord_id = :ccid",
										 array(	"ccid" => $challengeCoordId, "priority" => $priority,
												"hint" => $hint, "code" => $code));
			if(!$res){
				error_log("Error: Unable to update row in table ChallengeCoord. Database retuned '" . $res . "' (" . __METHOD__ . "@" .__CLASS__ . ")");
				throw new Exception("Unable to remove coordinate from challenge");
			}
		}

		/**
		 * Remove a cache from a challenge
		 * @param PDO $dbh Database handler
		 * @param integer $challengeCoordId The id of the cache
		 * @throws Exception if the database returns an error
		 */
		public static function remove($dbh, $challengeCoordId){

			$coordId = self::getCoordinate($dbh, $challengeCoordId);
			$res = DBTools::query($dbh, "DELETE FROM ChallengeCoord WHERE challenge_coord_id = :ccid", array("ccid" => $challengeCoordId));

			if($res){
				require_once(__DIR__ . "/../CoordinateManager.php");
				CoordinateManager::tryToRemoveCooridate($dbh, $coordId);
			}
			else{
				error_log("Error: Unable to delete row in table ChallengeCoord. Database retuned '" . $res . "' (" . __METHOD__ . "@" .__CLASS__ . ")");
				throw new Exception("Unable to remove coordinate from challenge");
			}
		}

		/**
		 * Remove all caches of a challenge
		 * @param PDO $dbh Database handler
		 * @param integer $challengeId The challenge id
		 * @throws Exception if the database returns an error
		 */
		public static function removeByChallenge($dbh, $challengeId){

			$coords = ChallengeManager::getChallengeCoordinates($dbh, $challengeId);

			$res = DBTools::query($dbh, "DELETE FROM ChallengeCoord WHERE challenge_id = :challengeId", array("challengeId" => $challengeId));

			if($res){
				require_once(__DIR__ . "/../CoordinateManager.php");

				// Cleanup coordinates
				for($i = 0; $i < count($coords); $i++){
					CoordinateManager::tryToRemoveCooridate($dbh, $coords[$i]["coord_id"]);
				}
			}
			else{
				error_log("Error: Unable to delete row in table ChallengeCoord. Database retuned '" . $res . "' (" . __METHOD__ . "@" .__CLASS__ . ")");
				throw new Exception("Unable to remove coordinate from challenge");
			}
		}

		/**
		 * Set a cache as captured (this is part of 'Capture the Flag' challenge)
		 * @param PDO $dbh Database handler
		 * @param integer $challengeCoordId The cache id
		 * @param integer $teamId The id of the team that has captured this cache
		 */
		public static function capture($dbh, $challengeCoordId, $teamId){

			$res = DBTools::query($dbh, "UPDATE ChallengeCoord " .
										"SET captured_by = CASE WHEN captured_by IS NULL THEN :team ELSE captured_by END, " .
										"capture_time = CASE WHEN capture_time IS NULL THEN CURRENT_TIMESTAMP ELSE capture_time END " .
										"WHERE challenge_coord_id = :ccid",
									array("team" => $teamId, "ccid" => $challengeCoordId));
		}

		/**
		 * Checks if cache has been captured by a team
		 * @param PDO $dbh Database handler
		 * @param integer $challengeCoordId The cache id
		 * @return integer <code>1</code> if the cache has been captured, <code>0</code> if not
		 */
		public static function isCaptured($dbh, $challengeCoordId){

			$res = DBTools::fetchNum($dbh,	"SELECT ChallengeCoord.captured_by " .
											"FROM ChallengeCoord " .
											"WHERE ChallengeCoord.challenge_coord_id = :ccid",
										array("ccid" => $challengeCoordId));

			if($res == null){
				return -1;
			}
			else{
				return $res[0] != null ? 1 : 0;
			}
		}

		/**
		 * Returns the timestamp of the capture event
		 * @param PDO $dbh Database handler
		 * @param integer $challengeCoordId The cache id
		 * @return The timestamp or <code>null</code> if there is no cache with this id
		 */
		public static function getCaptureTime($dbh, $challengeCoordId){

			$res = DBTools::fetchNum($dbh,	"SELECT ChallengeCoord.capture_time " .
											"FROM ChallengeCoord " .
											"WHERE ChallengeCoord.challenge_coord_id = :ccid",
											array("ccid" => $challengeCoordId));
			if($res == null){
				return null;
			}
			else{
				return $res[0];
			}
		}

		/**
		 * Resets the capture state of all caches of a challenge
		 * @param PDO $dbh Database handler
		 * @param integer $challengeId The challenge id
		 */
		public static function resetCaptureFlag($dbh, $challengeId){

			DBTools::query($dbh, "UPDATE ChallengeCoord " .
								 "SET captured_by = NULL, capture_time = NULL WHERE challenge_id = :cid",
								 array("cid" => $challengeId));
		}

		/**
		 * Removes trailing whitespaces and converts <code>""</code> to <code>null</code>
		 * @param string $code
		 * @return string
		 * @throws InvalidArgumentException if the code is too long
		 */
		private static function verifyCodeValue($code){
			if($code != null){
				if($code == ""){
					return null;
				}
				else{
					if(strlen($code) > 32){
						throw new InvalidArgumentException("The maximum length for the code is limited to 32 characters.");
					}
					else{
						$code = trim($code);
						return $code == "" ? null : $code;
					}
				}
			}
			return $code;
		}

		/**
		 * Checks if a cache needs a code to be captured/set as reached
		 * @param PDO $dbh Database handler
		 * @param integer $challengeCoordId The cache id
		 * @return boolean
		 */
		public static function hasCode($dbh, $challengeCoordId){
			$res = DBTools::fetchNum($dbh, "SELECT code FROM ChallengeCoord WHERE challenge_coord_id = :ccid", array("ccid" => $challengeCoordId));

			return $res[0] != null;
		}

		/**
		 * Checks if a code matches with the code of the cache
		 * @param PDO $dbh Database handler
		 * @param integer $challengeCoordId The cache id
		 * @param string $code The code for this cache
		 * @return boolean
		 */
		public static function checkCode($dbh, $challengeCoordId, $code){
			$res = DBTools::fetchNum($dbh, "SELECT code FROM ChallengeCoord WHERE challenge_coord_id = :ccid", array("ccid" => $challengeCoordId));

			return (strcasecmp($res[0], $code) == 0);
		}
	}
?>
