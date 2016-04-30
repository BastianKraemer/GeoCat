<?php
/* GeoCat - Geocaching and -tracking application
 * Copyright (C) 2016 Bastian Kraemer
 *
 * ChallengeStats.php
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
 * GeoCat ChallengeStat handler
 * @package app.challenge
 */

require_once(__DIR__ . "/../DBTools.php");

/**
 * This class handles the interaction with challenge statistics
 */
class ChallengeStats {

	/**
	 * Calculate the stats for a team
	 * @param PDO $dbh Database handler
	 * @param integer $challengeId
	 * @param integer $teamId
	 * @return The total time needed to finish the challenge
	 */
	public static function calculateStats($dbh, $challengeId, $teamId){

		require_once(__DIR__ . "/ChallengeCoord.php");

		if(!self::isCTFChallenge($dbh, $challengeId)){
			$coordCnt = ChallengeCoord::countCoordsOfChallenge($dbh, $challengeId, false);

			$check = DBTools::fetchNum($dbh,	"SELECT total_time FROM ChallengeStats " .
												"WHERE challenge_id = :cid AND team_id = :teamId",
												array("cid" => $challengeId, "teamId" => $teamId));

			if(!empty($check)){
				return -2;
			}

			$res = DBTools::fetchAll($dbh,	"SELECT ChallengeCheckpoint.time, ChallengeCoord.challenge_coord_id AS ccid, ChallengeCoord.priority " .
											"FROM ChallengeCheckpoint " .
											"JOIN ChallengeCoord ON (ChallengeCheckpoint.challenge_coord_id = ChallengeCoord.challenge_coord_id) " .
											"WHERE ChallengeCheckpoint.team_id = :team",
											array("team" => $teamId), PDO::FETCH_ASSOC);

			if(count($res) != $coordCnt){
				return -3;
			}

			$starttime = 0;
			$endtime = 0;

			foreach($res as $row){
				if($row["priority"] == 0){
					$starttime = strtotime($row["time"]);
				}
				else{
					$timeVal = strtotime($row["time"]);

					if($starttime == 0 || $timeVal < $starttime){
						$starttime = $timeVal;
					}

					if($endtime == 0 || $timeVal > $endtime){
						$endtime = $timeVal;
					}
				}
			}

			$totalTime = $endtime - $starttime;

			DBTools::query($dbh, "INSERT INTO ChallengeStats (challenge_id, team_id, total_time) VALUES (:cid, :teamId, :time)",
								 array("cid" => $challengeId, "teamId" => $teamId, "time" => $totalTime));

			return $totalTime;
		}
		else{
			return -1;
		}
	}

	/**
	 * Get the stats of a challenge
	 * @param PDO $dbh Database handler
	 * @param integer $challengeId
	 * @return array The ranking as orderd list with team name and the needed time
	 */
	public static function getStats($dbh, $challengeId){
		if(self::isCTFChallenge($dbh, $challengeId)){
			require_once(__DIR__ . "/ChallengeManager.php");
			$data = ChallengeManager::getChallengeCoordinates($dbh, $challengeId);

			$tmp = array();
			$response = array();

			$teams = ChallengeManager::getTeams($dbh, $challengeId);
			$teamMap = array();
			foreach($teams as $team){
				$tmp[$team["team_id"]] = 0;
				$teamMap[$team["team_id"]] = $team["name"];
			}

			foreach($data as $row){
				if($row["priority"] > 0){
					$tmp[$row["captured_by"]]++;
				}
			}

			$teamCnt = count($teams);
			for($i = 0; $i < $teamCnt; $i++){
				$max = 0;
				$maxKey = null;
				foreach($tmp as $key => $value){
					if($value > $max){
						$max = $value;
						$maxKey = $key;
					}
				}

				$response[] = array("caches" => $max, "team" => $teamMap[$maxKey]);
				unset($tmp[$maxKey]);
			}

			return $response;
		}
		else{
			$res = DBTools::fetchAll($dbh,	"SELECT ChallengeStats.total_time, ChallengeTeam.name AS team " .
											"FROM ChallengeStats " .
											"JOIN ChallengeTeam ON (ChallengeStats.team_id = ChallengeTeam.team_id) " .
											"WHERE ChallengeStats.challenge_id = :cid ORDER BY total_time ASC",
											array("cid" => $challengeId), PDO::FETCH_ASSOC);
			return $res;
		}
	}

	/**
	 * Checks if the challenge is a 'Capture the Flag' challenge
	 * @param PDO $dbh Database handler
	 * @param integer $challengeId
	 */
	private static function isCTFChallenge($dbh, $challengeId){
		$res = DBTools::fetchAssoc($dbh,"SELECT Challenge.challenge_type_id AS type_id, ChallengeType.acronym AS type " .
										"FROM Challenge, Account, ChallengeType " .
										"WHERE Challenge.challenge_id = :challengeId AND Challenge.owner = Account.account_id AND ChallengeType.challenge_type_id = Challenge.challenge_type_id",
										array("challengeId" => $challengeId));

		return (strcasecmp($res["type"], "ctf") == 0);
	}
}
