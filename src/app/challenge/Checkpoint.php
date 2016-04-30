<?php
/**
 * GeoCat Chackpoint handler
 * @package app.challenge
 */

	require_once(__DIR__ . "/../DBTools.php");

	/**
	 * This class handles the interaction with challenge checkpoints
	 */
	class Checkpoint {

		/**
		 * Returns timestamp when this checkpoint has been reached by a team
		 * @param PDO $dbh Database handler
		 * @param integer $challengeCoordId
		 * @param integer $teamId
		 * @returns string The time the checkpoint has been reached or null
		 */
		public static function isReachedBy($dbh, $challengeCoordId, $teamId){

			$res = DBTools::fetchNum($dbh, "SELECT time FROM ChallengeCheckpoint WHERE challenge_coord_id = :ccid AND team_id = :team",
											array("ccid" => $challengeCoordId, "team" => $teamId));
			return $res ? $res[0] : null;
		}

		/**
		 * Returns an array of all checkpoints that have been reached by a team
		 * @param PDO $dbh Database handler
		 * @param integer $teamId
		 * @return array Array of challenge coordinate id and timestamp
		 */
		public static function getReachedCheckpoints($dbh, $teamId){

			$res = DBTools::fetchAll($dbh, "SELECT challenge_coord_id, time FROM ChallengeCheckpoint WHERE team_id = :team",
											array("team" => $teamId), PDO::FETCH_ASSOC);
			return $res ? $res : array();
		}

		/**
		 * Set a checkpoint as reached (for a team)
		 * @param PDO $dbh Database handler
		 * @param integer $challengeCoordId
		 * @param integer $teamId
		 * @throws InvalidArgumentException if the checkpoint is already reached
		 * @throws Exception if the operations fails
		 */
		public static function setReached($dbh, $challengeCoordId, $teamId){

			if(self::isReachedBy($dbh, $challengeCoordId, $teamId)){
				throw new InvalidArgumentException("This coordinate has been already reached by this team.");
			}

			$res = DBTools::query($dbh, "INSERT INTO ChallengeCheckpoint (challenge_coord_id, team_id, time) VALUES (:ccid, :team, DEFAULT)",
										array("ccid" => $challengeCoordId, "team" => $teamId));

			if(!$res){
				error_log("Error: Unable to insert into table ChallengeCheckpoint. Database retuned '" . $res . "' (" . __METHOD__ . "@" .__CLASS__ . ")");
				throw new Exception("Unable to set checkpoint as reached.");
			}

			return self::isReachedBy($dbh, $challengeCoordId, $teamId);
		}

		/**
		 * Removes all timestamps of a cache
		 * @param PDO $dbh Database handler
		 * @param integer $challengeCoordId
		 * @throws Exception if the database couldn't be updated
		 */
		public static function clearCheckpointsOfChallengeCoord($dbh, $challengeCoordId){
			$res = DBTools::query($dbh, "DELETE FROM ChallengeCheckpoint WHERE challenge_coord_id = :ccid",
					array("ccid" => $challengeCoordId));

			if(!$res){
				error_log("Error: Unable to delete rows in table ChallengeCheckpoint. Database retuned '" . $res . "' (" . __METHOD__ . "@" .__CLASS__ . ")");
				throw new Exception("Unable to remove checkpoint by chalenge coord id from challenge");
			}
		}

		/**
		 * Removes all checkpoints (respectively timestamps) of a team
		 * @param PDO $dbh Database handler
		 * @param integer $teamId
		 * @throws Exception if the database returned an error
		 */
		public static function clearCheckpointsOfTeam($dbh, $teamId){
			$res = DBTools::query($dbh, "DELETE FROM ChallengeCheckpoint WHERE team_id = :team",
									array("team" => $teamId));

			if(!$res){
				error_log("Error: Unable to delete rows in table ChallengeCheckpoint. Database retuned '" . $res . "' (" . __METHOD__ . "@" .__CLASS__ . ")");
				throw new Exception("Unable to remove checkpoints from challenge");
			}
		}

		/**
		 * Removes all checkpoints (respectively timestamps) of a challenge.
		 * @param PDO $dbh Database handler
		 * @param integer $challengeId
		 */
		public static function clearCheckpointsOfChallenge($dbh, $challengeId){

			require_once(__DIR__ . "/ChallengeManager.php");

			$teams = ChallengeManager::getTeams($dbh, $challengeId);

			foreach ($teams as $t){
				self::clearCheckpointsOfTeam($dbh, $t["team_id"]);
			}
		}
	}
?>