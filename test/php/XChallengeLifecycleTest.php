<?php
/* GeoCat - Geocaching and -tracking application
 * Copyright (C) 2016 Bastian Kraemer
 *
 * XChallengeLifecycleTest.php
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

load("app/challenge/ChallengeManager.php");
load("app/challenge/ChallengeCoord.php");
load("app/challenge/ChallengeStats.php");
load("app/challenge/Checkpoint.php");
load("app/CoordinateManager.php");
load("app/AccountManager.php");

/**
 * This Test case simulates a challenge lifecycle.
 * The 'X' at the beginnig ist needed to make this test run at the end
 */
class XChallengeLifecycleTest extends PHPUnit_Framework_TestCase {

	const STOREKEY_ID = "challenge_lifecycle_test.id";
	const STOREKEY_KEY = "challenge_lifecycle_test.key";
	const STOREKEY_COORDS = "challenge_lifecycle_test.coords";
	const STOREKEY_TEAMS = "challenge_lifecycle_test.teamids";

	// ------------------------------------------------------

	const DEFAULT_TEAM_ACCESS_CODE = "test";

	private $owner;
	private $user1;
	private $user2;
	private $dbh;

	private $coords;
	private $challengeId;
	private $challengeKey;

	private $teamIds;

	/**
	 * @before
	 */
	public function prepareTest(){
		global $completeChallengeTest_ChallengeData;

		$this->owner = TestHelper::getTestAccountId(0);
		$this->user1 = TestHelper::getTestAccountId(1);
		$this->user2 = TestHelper::getTestAccountId(2);
		$this->dbh = TestHelper::getDBH();

		if(!TestHelper::hasVal(SELF::STOREKEY_COORDS)){
			$this->coords = [
					["lat" => "50.0000", "lon" => "8.0000", "code" => null],
					["lat" => "50.123456", "lon" => "8.765432", "code" => "cache1"],
					["lat" => "51.010101", "lon" => "8.9012345", "code" => "cache2"]
			];
			TestHelper::storeVal(SELF::STOREKEY_COORDS, $this->coords);
		}
		else{
			$this->coords = TestHelper::getVal(SELF::STOREKEY_COORDS);
		}


		if(TestHelper::hasVal(SELF::STOREKEY_ID)){
			$this->challengeId = TestHelper::getVal(SELF::STOREKEY_ID);
		}
		if(TestHelper::hasVal(SELF::STOREKEY_KEY)){
			$this->challengeKey = TestHelper::getVal(SELF::STOREKEY_KEY);
		}

		if(TestHelper::hasVal(SELF::STOREKEY_TEAMS)){
			$this->teamIds = TestHelper::getVal(SELF::STOREKEY_TEAMS);
		}
	}

	public function testCreateChallenge(){
		global $completeChallengeTest_ChallengeData;
		$this->challengeKey = ChallengeManager::createChallenge($this->dbh, "Test", ChallengeType::DefaultChallenge, $this->owner, "test");
		$this->challengeId = ChallengeManager::getChallengeIdBySessionKey($this->dbh, $this->challengeKey);

		$this->assertGreaterThan(0, $this->challengeId);
		$this->assertFalse(ChallengeStats::isCTFChallenge($this->dbh, $this->challengeId));

		// This data is needed in every case
		TestHelper::storeVal(SELF::STOREKEY_ID, $this->challengeId);
		TestHelper::storeVal(SELF::STOREKEY_KEY, $this->challengeKey);
	}

/* ============================================================================
 *  Challenge created - test create coordinates
 * ============================================================================ */

	/**
	 * @depends testCreateChallenge
	 */
	public function testRemoveCoords(){

		$coords = array(3);
		$ccids = array(3);

		for($i = 0; $i < count($this->coords); $i++){
			$coords[$i] = CoordinateManager::createCoordinate($this->dbh, "Cache " . $i, $this->coords[$i]["lat"], $this->coords[$i]["lon"], "");
			$ccids[$i] = ChallengeCoord::create($this->dbh, $this->challengeId, $coords[$i], $i, "Cache " . $i, $this->coords[$i]["code"]);
		}

		$this->assertEquals(3, ChallengeCoord::countCoordsOfChallenge($this->dbh, $this->challengeId, false));

		ChallengeCoord::remove($this->dbh, $ccids[1]);
		$this->assertEquals(2, ChallengeCoord::countCoordsOfChallenge($this->dbh, $this->challengeId, false));

		$coordList = ChallengeManager::getChallengeCoordinates($this->dbh, $this->challengeId);
		$this->assertCount(2, $coordList);
		$this->assertArrayHasKey("challenge_coord_id", $coordList[0]);
		$this->assertArrayHasKey("coord_id", $coordList[0]);
		$this->assertEquals($ccids[0], $coordList[0]["challenge_coord_id"]);
		$this->assertEquals($ccids[2], $coordList[1]["challenge_coord_id"]);
		$this->assertEquals($coords[0], $coordList[0]["coord_id"]);
		$this->assertEquals($coords[2], $coordList[1]["coord_id"]);

		ChallengeCoord::removeByChallenge($this->dbh, $this->challengeId);

		$this->assertEquals(0, ChallengeCoord::countCoordsOfChallenge($this->dbh, $this->challengeId, false));
		$this->assertEquals(0, ChallengeCoord::countCoordsOfChallenge($this->dbh, $this->challengeId, true));
	}

	/**
	 * @depends testRemoveCoords
	 */
	public function testAddCoords(){

		for($i = 0; $i < count($this->coords); $i++){
			$coordId = CoordinateManager::createCoordinate($this->dbh, "Cache " . $i, $this->coords[$i]["lat"], $this->coords[$i]["lon"], "");
			$ccId = ChallengeCoord::create($this->dbh, $this->challengeId, $coordId, $i, "Cache " . $i, $this->coords[$i]["code"]);

			$this->assertTrue(ChallengeCoord::checkCode($this->dbh, $ccId, $this->coords[$i]["code"]));
			$this->assertEquals($this->challengeId, ChallengeCoord::getChallengeOfCoordinate($this->dbh, $ccId));
			$this->assertEquals($coordId, ChallengeCoord::getCoordinate($this->dbh, $ccId));

			$this->coords[$i]["coordId"] = $coordId;
			$this->coords[$i]["ccId"] = $ccId;
		}

		$this->assertEquals(3, ChallengeCoord::countCoordsOfChallenge($this->dbh, $this->challengeId, false));
		$this->assertEquals(1, ChallengeCoord::countCoordsOfChallenge($this->dbh, $this->challengeId, true));

		// Update the cooridnates array in the gloabl test helper store
		TestHelper::storeVal(SELF::STOREKEY_COORDS, $this->coords);
	}

	/**
	 * @depends testAddCoords
	 * @expectedException InvalidArgumentException
	 */
	public function testAddCoordTwiceToChallenge(){
		$ccId = ChallengeCoord::create($this->dbh, $this->challengeId, $this->coords[1]["coordId"], 1, $this->coords[1]["code"], null);
	}

	/**
	 * @depends testAddCoordTwiceToChallenge
	 */
	public function testCoordUpdate(){
		$newHint = "New hint";
		$newPriority = 5;
		ChallengeCoord::update($this->dbh, $this->coords[2]["ccId"], $newPriority, $newHint, $this->coords[2]["code"]);

		$coordList = ChallengeManager::getChallengeCoordinates($this->dbh, $this->challengeId, true);
		$this->assertCount(3, $coordList);
		$this->assertArrayHasKey("code", $coordList[2]);
		$this->assertArrayHasKey("hint", $coordList[2]);
		$this->assertArrayHasKey("priority", $coordList[2]);
		$this->assertArrayHasKey("code_required", $coordList[2]);

		$this->assertEquals($newHint, $coordList[2]["hint"]);
		$this->assertEquals($newPriority, $coordList[2]["priority"]);
		$this->assertEquals($this->coords[2]["code"], $coordList[2]["code"]);
		$this->assertEquals(1, $coordList[2]["code_required"]);
	}

/* ============================================================================
 *  Coordinates created - test create teams
 * ============================================================================ */

	/**
	 * @depends testCoordUpdate
	 */
	public function testCreateTeams(){
		// Create the teams
		$id1 = TeamManager::createTeam($this->dbh, $this->challengeId, "Team1", "#FF0000", true, null);
		$id2 = TeamManager::createTeam($this->dbh, $this->challengeId, "Team2", "#00FF00", true, SELF::DEFAULT_TEAM_ACCESS_CODE);

		// Run some asserts
		$this->assertTrue(TeamManager::checkTeamAccessCode($this->dbh, $id1, null));
		$this->assertTrue(TeamManager::checkTeamAccessCode($this->dbh, $id1, "abc"));
		$this->assertTrue(TeamManager::checkTeamAccessCode($this->dbh, $id2, "test"));
		$this->assertFalse(TeamManager::checkTeamAccessCode($this->dbh, $id2, "abc"));
		$this->assertFalse(TeamManager::checkTeamAccessCode($this->dbh, $id2, null));

		$this->assertTrue(TeamManager::teamWithNameExists($this->dbh, $this->challengeId, "Team1"));
		$this->assertFalse(TeamManager::teamWithNameExists($this->dbh, $this->challengeId, "Team3"));

		$this->assertEquals(2, ChallengeManager::countExisitingTeams($this->dbh, $this->challengeId));

		$this->teamIds = array($id1, $id2);
		TestHelper::storeVal(SELF::STOREKEY_TEAMS, $this->teamIds);
	}

	/**
	 * @depends testCreateTeams
	 * @expectedException InvalidArgumentException
	 */
	public function testJoinNotEnabledChallenge(){
		// Test that you can't join a team in a not enaled challenge
		ChallengeManager::setEnabled($this->dbh, $this->challengeId, false);
		TeamManager::joinTeam($this->dbh, $this->teamIds[0], $this->teamIds[1], $this->user1, null);
	}

	/**
	 * @depends testJoinNotEnabledChallenge
	 */
	public function testJoinSingleTeam(){

		ChallengeManager::setEnabled($this->dbh, $this->challengeId, true);

		// Both users in same team
		TeamManager::joinTeam($this->dbh, $this->teamIds[0], $this->user1, null);
		$this->joinChallengeTwiceTest($this->teamIds[0]);
		$this->joinChallengeTwiceTest($this->teamIds[1]);

		TeamManager::joinTeam($this->dbh, $this->teamIds[0], $this->user2, "what if this is not null?");
		$this->assertEquals(2, TeamManager::countTeamMembers($this->dbh, $this->teamIds[0]), "Team member count is not correct.");

		// Test TeamManager function
		$this->assertEquals($this->challengeId,
			TeamManager::getChallengeIdOfTeam($this->dbh, $this->teamIds[0])
		);

		$this->assertTrue(TeamManager::teamExists($this->dbh, $this->teamIds[0]));
		$this->assertFalse(TeamManager::teamExists($this->dbh, "-4"));

		$this->assertTrue(TeamManager::isPredefinedTeam($this->dbh, $this->teamIds[0]));
		$this->assertEquals($this->teamIds[0], TeamManager::getTeamOfUser($this->dbh, $this->challengeId, $this->user1));
		$this->assertEquals($this->teamIds[0], TeamManager::getTeamOfUser($this->dbh, $this->challengeId, $this->user2));
		$this->assertTrue(TeamManager::isMemberOfTeam($this->dbh, $this->teamIds[0], $this->user1));
		$this->assertFalse(TeamManager::isMemberOfTeam($this->dbh, $this->teamIds[1], $this->user1));

		$teamMembers = TeamManager::getTeamMembers($this->dbh, $this->teamIds[0]);

		$this->assertContains(AccountManager::getUserNameByAccountId($this->dbh, $this->user1), $teamMembers);
		$this->assertContains(AccountManager::getUserNameByAccountId($this->dbh, $this->user1), $teamMembers);
		$this->assertNotContains("ANY_USER", $teamMembers);
	}

	private function joinChallengeTwiceTest($teamid){
		try{
			TeamManager::joinTeam($this->dbh, $teamid, $this->user1, null);
			$this->fail("Users can be more than one member of a team!?!");
		}
		catch(InvalidArgumentException $ex){} //everything okay
	}

	/**
	 * @depends testJoinSingleTeam
	 */
	public function testLeaveTeam(){
		// Let user2 leave team 1
		TeamManager::leaveTeam($this->dbh, $this->teamIds[0], $this->user2);
		$this->assertEquals(1, TeamManager::countTeamMembers($this->dbh, $this->teamIds[0]), "Team member count is not correct.");
		$this->assertEquals(-1, TeamManager::getTeamOfUser($this->dbh, $this->challengeId, $this->user2));
	}

	/**
	 * @depends testLeaveTeam
	 */
	public function testEmptyTeamRemove(){
		$newTeam = TeamManager::createTeam($this->dbh, $this->challengeId, "Team3", "#00FF00", false, SELF::DEFAULT_TEAM_ACCESS_CODE);

		TeamManager::joinTeam($this->dbh, $newTeam, $this->user2, SELF::DEFAULT_TEAM_ACCESS_CODE);
		$this->assertEquals($newTeam, TeamManager::getTeamOfUser($this->dbh, $this->challengeId, $this->user2));

		// Leave the team - the teams should be deleted in this case
		TeamManager::leaveTeam($this->dbh, $newTeam, $this->user2);
		$this->assertFalse(TeamManager::teamWithNameExists($this->dbh, $this->challengeId, "Team3"));
	}

	/**
	 * @depends testEmptyTeamRemove
	 * @expectedException InvalidArgumentException
	 */
	public function testJoinWithWrongCode(){
		// Join Team 2 with wrong code
		TeamManager::joinTeam($this->dbh, $this->teamIds[1], $this->user2, "wrong code");
	}

	/**
	 * @depends testJoinWithWrongCode
	 */
	public function testJoinSeparateTeams(){
		// Join Team 2 with wrong code
		TeamManager::joinTeam($this->dbh, $this->teamIds[1], $this->user2, SELF::DEFAULT_TEAM_ACCESS_CODE);
		$this->assertEquals($this->teamIds[1], TeamManager::getTeamOfUser($this->dbh, $this->challengeId, $this->user2));

		// Leave again - the team should exist
		TeamManager::leaveTeam($this->dbh, $this->teamIds[1], $this->user2);
		TeamManager::teamExists($this->dbh, $this->teamIds[1]);
		TeamManager::joinTeam($this->dbh, $this->teamIds[1], $this->user2, SELF::DEFAULT_TEAM_ACCESS_CODE);

		$this->assertEquals(2, ChallengeManager::countExisitingTeams($this->dbh, $this->challengeId));
	}

/* ============================================================================
 *  Teams created - test challenge checkpoints
 * ============================================================================ */

	/**
	 * @depends testJoinSeparateTeams
	 */
	public function testSetCheckpointReached(){
		$this->assertNull(Checkpoint::isReachedBy($this->dbh, $this->coords[1]["ccId"], $this->teamIds[0]));
		$this->assertEmpty(Checkpoint::getReachedCheckpoints($this->dbh, $this->teamIds[0]));

		ChallengeManager::setEndTime($this->dbh, $this->challengeId, null);

		// Return value is '-3' if there are unreached caches
		$this->assertEquals(-3, ChallengeStats::calculateStats($this->dbh, $this->challengeId, $this->teamIds[0]));

		for($i = 0; $i < 3; $i++){ // Uterate over all three coords
			for($j = 0; $j < 2; $j++){ //Iterate over the teams
				Checkpoint::setReached($this->dbh, $this->coords[$i]["ccId"], $this->teamIds[$j]);

				$this->assertNotNull(
					Checkpoint::isReachedBy($this->dbh, $this->coords[$i]["ccId"], $this->teamIds[$j])
				);

				sleep($i != 2 ? 1 : (($j + 1) * 2)); // different times for both teams
			}
		}

		for($i = 0; $i < 2; $i++){
			$data = Checkpoint::getReachedCheckpoints($this->dbh, $this->teamIds[$i]);

			$arr = array($data[0]["challenge_coord_id"], $data[1]["challenge_coord_id"], $data[2]["challenge_coord_id"]);
			$this->assertEquals(
				array($this->coords[0]["ccId"], $this->coords[1]["ccId"], $this->coords[2]["ccId"]),
				$arr
			);
		}
	}

	/**
	 * @depends testSetCheckpointReached
	 */
	public function testCalculateStats(){
		$times = array(2);
		$times[0] = ChallengeStats::calculateStats($this->dbh, $this->challengeId, $this->teamIds[0]);
		$times[1] = ChallengeStats::calculateStats($this->dbh, $this->challengeId, $this->teamIds[1]);

		// Return value is '-2' if the stats are already calculated
		$this->assertEquals(-2, ChallengeStats::calculateStats($this->dbh, $this->challengeId, $this->teamIds[0]));

		$this->assertRange($times[0], 4, 6); // The value should be '4' but for a very slow database we accept 2s buffer time
		$this->assertRange($times[1], 5, 7); // The value should be '5' but for a very slow database we accept 2s buffer time

		$stats = ChallengeStats::getStats($this->dbh, $this->challengeId);
		$this->assertCount(2, $stats);

		for($i = 0; $i < 2; $i++){
			$this->assertArrayHasKey("total_time", $stats[$i]);
			$this->assertArrayHasKey("team", $stats[$i]);

			$this->assertEquals($times[$i], $stats[$i]["total_time"]);
		}
	}

	private function assertRange($val, $min, $max){
		$this->GreaterThanOrEqual($min, $val);
		$this->LessThanOrEqual($max, $val);
	}

/* ============================================================================
 *  Checkpoints created - test statistics and reset
 * ============================================================================ */

	/**
	 * @depends testCalculateStats
	 */
	public function testChallengeRest(){
		ChallengeManager::resetChallenge($this->dbh, $this->challengeId);
		$this->assertEquals(0, ChallengeManager::countExisitingTeams($this->dbh,  $this->challengeId));
		$this->assertFalse(TeamManager::teamExists($this->dbh, $this->teamIds[0]));
		$this->assertFalse(TeamManager::teamExists($this->dbh, $this->teamIds[1]));
	}

	/**
	 * @depends testChallengeRest
	 */
	public function testCTFChallenge(){
		ChallengeManager::updateSingleValue($this->dbh, $this->challengeId, "challenge_type_id", ChallengeType::CaptureTheFlag);
		ChallengeManager::setEnabled($this->dbh, $this->challengeId, true);
		$this->assertTrue(ChallengeStats::isCTFChallenge($this->dbh, $this->challengeId));

		//create the teams again
		$this->testCreateTeams();

		// Run the team tests again to join the teams
		$this->testJoinSingleTeam();
		$this->testLeaveTeam();
		$this->testJoinSeparateTeams();
	}

/* ============================================================================
 *  Default challenge lifecycle testet - test CTF challenges
 * ============================================================================ */

	/**
	 * @depends testCTFChallenge
	 */
	public function testCTFCapture(){

		for($i = 0; $i < 2; $i++){
			$this->assertEquals(0, ChallengeCoord::isCaptured($this->dbh, $this->coords[$i]["ccId"]));
			$this->assertNull(ChallengeCoord::getCaptureTime($this->dbh, $this->coords[$i]["ccId"]));
		}

		// Bte: The start point has te be reached - not captured
		Checkpoint::setReached($this->dbh, $this->coords[0]["ccId"],  $this->teamIds[0]);
		sleep(1);
		Checkpoint::setReached($this->dbh, $this->coords[0]["ccId"],  $this->teamIds[1]);

		sleep(2);
		ChallengeCoord::capture($this->dbh, $this->coords[1]["ccId"],  $this->teamIds[1]);
		sleep(2);
		ChallengeCoord::capture($this->dbh, $this->coords[2]["ccId"],  $this->teamIds[1]);

		for($i = 1; $i < 3; $i++){
			$this->assertEquals(1, ChallengeCoord::isCaptured($this->dbh, $this->coords[$i]["ccId"]));
			$this->assertNotNull(ChallengeCoord::getCaptureTime($this->dbh, $this->coords[$i]["ccId"]));
		}

		$coordList = ChallengeManager::getChallengeCoordinates($this->dbh, $this->challengeId);
		$this->assertCount(3, $coordList);

		$this->assertArrayHasKey("captured_by", $coordList[0]);
		$this->assertArrayHasKey("capture_time", $coordList[0]);

		$this->assertNull($coordList[0]["captured_by"]);
		$this->assertNull($coordList[0]["capture_time"]);
		$this->assertEquals($this->teamIds[1], $coordList[1]["captured_by"]);
		$this->assertNotNull($coordList[1]["capture_time"]);
		$this->assertEquals($this->teamIds[1], $coordList[2]["captured_by"]);
		$this->assertNotNull($coordList[2]["capture_time"]);
	}

	/**
	 * @depends testCTFCapture
	 */
	public function testCTFStats(){
		$this->assertEquals(-1, ChallengeStats::calculateStats($this->dbh, $this->challengeId, $this->teamIds[0]));

		$stats = ChallengeStats::getStats($this->dbh, $this->challengeId);

		$this->assertCount(2, $stats);
		$this->assertArrayHasKey("caches", $stats[0]);
		$this->assertArrayHasKey("team", $stats[0]);

		$this->assertEquals(2, $stats[0]["caches"]);
		$this->assertEquals("Team2", $stats[0]["team"]);
		$this->assertEquals(0, $stats[1]["caches"]);
		$this->assertEquals("Team1", $stats[1]["team"]);
	}

	/**
	 * @depends testCTFStats
	 */
	public function testResetCaputerFlags(){
		ChallengeCoord::resetCaptureFlag($this->dbh, $this->challengeId);

		for($i = 0; $i < 3; $i++){
			$this->assertEquals(0, ChallengeCoord::isCaptured($this->dbh, $this->coords[$i]["ccId"]));
			$this->assertNull(ChallengeCoord::getCaptureTime($this->dbh, $this->coords[$i]["ccId"]));
		}
	}

	/**
	 * @depends testResetCaputerFlags
	 */
	public function testDeleteChallenge(){

		// Note: It is not important to test deletion of a 'filled' challenge, because 'resetChallenge' (which is already tested
		// is the first call in the 'deleteChallenge' function
		ChallengeManager::deleteChallenge($this->dbh, $this->challengeId);

		$this->assertFalse(ChallengeManager::challengeExists($this->dbh, $this->challengeId));

		for($i = 0; $i < 3; $i++){
			$this->assertFalse(CoordinateManager::coordinateExists($this->dbh, $this->coords[$i]["coordId"]));
		}

		$this->assertFalse(TeamManager::teamExists($this->dbh, $this->teamIds[0]));
		$this->assertFalse(TeamManager::teamExists($this->dbh, $this->teamIds[1]));

		$this->assertEquals(-1, ChallengeManager::getChallengeIdBySessionKey($this->dbh, $this->challengeKey));
	}
}
?>
