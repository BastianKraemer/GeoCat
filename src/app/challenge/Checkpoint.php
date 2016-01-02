<?php

	require_once(__DIR__ . "/../DBTools.php");

	class Checkpoint {

		public static function isReachedBy($dbh, $challengeCoordId, $teamId){

			$res = DBTools::fetchNum($dbh, "SELECT time FROM ChallengeCheckpoint WHERE challenge_coord_id = :ccid AND team_id = :team",
											array("ccid" => $challengeCoordId, "team" => $teamId));

			return $res ? $res[0] : null;
		}

		public static function getReachedCheckpoints($dbh, $teamId){

			$res = DBTools::fetchAll($dbh, "SELECT challenge_coord_id, time FROM ChallengeCheckpoint WHERE team_id = :team",
											array("team" => $teamId), PDO::FETCH_ASSOC);

			return $res ? $res : array();
		}

		public static function setReached($dbh, $challengeCoordId, $teamId){

			if(self::isReachedBy($dbh, $challengeCoordId, $teamId)){
				throw new InvalidArgumentException("This coordinate has been already reached by this team.");
			}

			$res = DBTools::query($dbh, "INSERT INTO ChallengeCheckpoint (challenge_coord_id, team_id) VALUES (:ccid, :team)",
										array("ccid" => $challengeCoordId, "team" => $teamId));

			if(!$res){
				error_log("Error: Unable to inester into table ChallengeCheckpoint. Database retuned '" . $res . "' (" . __METHOD__ . "@" .__CLASS__ . ")");
				throw new Exception("Unable to set checkpoint as reached.");
			}
		}

		public static function clearCheckpointsOfTeam($dbh, $teamId){
			$res = DBTools::query($dbh, "DELETE FROM ChallengeCheckpoint WHERE team_id = :team",
									array("team" => $teamId));

			if(!$res){
				error_log("Error: Unable to delete rows in table ChallengeCheckpoint. Database retuned '" . $res . "' (" . __METHOD__ . "@" .__CLASS__ . ")");
				throw new Exception("Unable to remove checkpoints from challenge");
			}
		}

		public static function clearCheckpointsOfChallenge($dbh, $challengeId){

			require_once(__DIR__ . "/ChallengeManager.php");

			$teams = ChallengeManager::getTeams($dbh, $challengeId);

			foreach ($teams as $t){
				self::clearCheckpointsOfTeam($dbh, $t["team_id"]);
			}
		}
	}
?>