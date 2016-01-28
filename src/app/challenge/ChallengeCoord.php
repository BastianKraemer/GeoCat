<?php

	require_once(__DIR__ . "/../DBTools.php");

	class ChallengeCoord {

		public static function create($dbh, $challengeId, $coordId, $priority, $hint, $code){

			require_once(__DIR__ . "/../CoordinateManager.php");
			require_once(__DIR__ . "/ChallengeManager.php");

			if($code != null){
				if($code == ""){
					$code = null;
				}
				else if(strlen($code) > 32){
					throw new InvalidArgumentException("The maximum length for the code is limited to 32 characters.");
				}
			}

			$hint = htmlspecialchars($hint, ENT_QUOTES);
			if(strlen($hint) > 256){
				throw new InvalidArgumentException("The maximum length for the hint field is limited to 256 characters.");
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
										"VALUES (null, :challengeId, :coordId, :priority, :hint, :code)",
									array(	"challengeId" => $challengeId, "coordId" => $coordId, "priority" => $priority,
											"hint" => $hint, "code" => $code));

			if($res){
				return $dbh->lastInsertId("challenge_coord_id");
			}
			else{
				error_log("Error: Unable to insert into table ChallengeCoord. Database retuned '" . $res . "' (" . __METHOD__ . "@" .__CLASS__ . ")");
				throw new Exception("Unable to append coordinate to challenge");
			}
		}

		public static function getCoordinate($dbh, $challengeCoordId){
			$res = DBTools::fetchNum($dbh, "SELECT coord_id FROM ChallengeCoord WHERE challenge_coord_id = :ccid", array("ccid" => $challengeCoordId));

			return $res ? $res[0] : -1;
		}

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

		public static function capture($dbh, $challengeCoordId, $teamId){

			$res = DBTools::query($dbh, "UPDATE ChallengeCoord " .
										"SET captured_by = CASE WHEN captured_by = NULL THEN captured_by = :team ELSE captured_by END, " .
										"SET capture_time = CASE WHEN capture_time = NULL THEN capture_time = CURRENT_TIMESTAMP ELSE capture_time END " .
										"WHERE challenge_coord_id = :ccid",
									array("team" => $teamId, "ccid" => $challengeCoordId));
		}

		public static function checkCode($dbh, $challengeCoordId, $code){
			// TODO: function checkCode
		}

		public static function checkUserPosition($dbh, $challengeCoordId, $userLat, $userLon){
			// TODO: function checkUserPosition
		}
	}

?>