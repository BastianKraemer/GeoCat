<?php

load("app/challenge/ChallengeManager.php");
load("app/AccountManager.php");

class ChallengeManagerTest extends PHPUnit_Framework_TestCase {
	private $testAccId;
	private $dbh;

	/**
	 * @before
	 */
	public function getTestAccount(){
		$this->testAccId = TestHelper::getTestAccountId(0);
		$this->dbh = TestHelper::getDBH();
	}

	public function testGetPublicOwnChallenges(){
		$c1 = $this->createEmptyChallenge("Public1", "Public challenge 1");
		$c2 = $this->createEmptyChallenge("Public2", "Public challenge 2");

		$this->assertFalse(ChallengeManager::isChallengePublic($this->dbh, $c1[0]), "Newly created challenge shouldn't be public");
		$this->assertFalse(ChallengeManager::isChallengeEnabled($this->dbh, $c1[0]), "Newly created challenge shouldn't be enabled");

		ChallengeManager::setPublic($this->dbh, $c1[0], true);
		ChallengeManager::setEnabled($this->dbh, $c1[0], true);
		ChallengeManager::setPublic($this->dbh, $c2[0], true);
		ChallengeManager::setEnabled($this->dbh, $c2[0], true);

		$this->assertTrue(ChallengeManager::isChallengePublic($this->dbh, $c1[0]), "Unable to set challenge 'public'");
		$this->assertTrue(ChallengeManager::isChallengeEnabled($this->dbh, $c1[0]), "Unable to enable challenge");

		$foundC1 = array(false, false); $foundC2 = array(false, false);

		$challengeList = ChallengeManager::getPublicChallengs($this->dbh, -1, 0);
		foreach($challengeList as $challenge){
			if($challenge["challenge_id"] == $c1[0]){$foundC1[0] = true;}
			if($challenge["challenge_id"] == $c2[0]){$foundC2[0] = true;}
		}

		$challengeList = ChallengeManager::getMyChallenges($this->dbh, $this->testAccId, -1, 0);
		foreach($challengeList as $challenge){
			if($challenge["challenge_id"] == $c1[0]){$foundC1[1] = true;}
			if($challenge["challenge_id"] == $c2[0]){$foundC2[1] = true;}
		}

		ChallengeManager::deleteChallenge($this->dbh, $c1[0]);
		ChallengeManager::deleteChallenge($this->dbh, $c2[0]);

		if(!$foundC1[0] || !$foundC2[0]){
			$this->fail("Public challenges are not found in 'getPublicChallenges");
		}

		if(!$foundC1[1] || !$foundC2[1]){
			$this->fail("Challenges not found in 'getOwnChallenges");
		}
	}

	public function testChallengeUpdate(){
		$newName = "JAC - Just another Challenge";
		$newDesc = "This challenge is not verify interesting";

		$predefTeams = "1";
		$maxTeams = "64";
		$maxTeamMembers = "16";
		$startTime = "2016-05-01 12:00:00";
		$endTime = "2016-06-01 12:00:00";

		$id = $this->createEmptyChallenge("rename_test", "rename desc")[0];

		ChallengeManager::updateName($this->dbh, $id, $newName);
		ChallengeManager::updateDescription($this->dbh, $id, $newDesc);
		ChallengeManager::setStartTime($this->dbh, $id, $startTime);
		ChallengeManager::setEndTime($this->dbh, $id, $endTime);

		ChallengeManager::updateSingleValue($this->dbh, $id, "predefined_teams", $predefTeams);
		ChallengeManager::updateSingleValue($this->dbh, $id, "max_teams", $maxTeams);
		ChallengeManager::updateSingleValue($this->dbh, $id, "max_team_members", $maxTeamMembers);

		$info = ChallengeManager::getChallengeInformation($this->dbh, $id);

		$this->assertEquals($newName, $info["name"]);
		$this->assertEquals($newDesc, $info["description"]);
		$this->assertEquals($maxTeams, $info["max_teams"]);
		$this->assertEquals($maxTeamMembers, $info["max_team_members"]);
		$this->assertEquals($predefTeams, $info["predefined_teams"]);
		$this->assertEquals($startTime, $info["start_time"]);
		$this->assertEquals($endTime, $info["end_time"]);
		$this->assertEquals(0, $info["current_team_cnt"]);

		$this->assertEquals($maxTeams, ChallengeManager::getMaxNumberOfTeams($this->dbh, $id));
		$this->assertEquals($maxTeamMembers, ChallengeManager::getMaxMembersPerTeam($this->dbh, $id));
		$this->assertEquals(0, ChallengeManager::countExisitingTeams($this->dbh, $id));

		ChallengeManager::deleteChallenge($this->dbh, $id);
	}

	public function testSessionKeyPrettify(){
		$this->assertEquals("AB CD", ChallengeManager::prettifySessionKey("ABCD"));
		$this->assertEquals("A1 B2 C3", ChallengeManager::prettifySessionKey("A1B2C3"));
		$this->assertEquals("12 34 56 78", ChallengeManager::prettifySessionKey("12345678"));
	}

	private function createEmptyChallenge($name = null, $desc = null, $owner = null){

		if($name == null){$name = "Testchallenge-" . date("l");}
		if($desc == null){$desc = "Description of challenge '" . date("l") . "'";}
		if($owner == null){$owner = $this->testAccId;}

		$key = ChallengeManager::createChallenge($this->dbh, $name, ChallengeType::DefaultChallenge, $this->testAccId, $desc);

		$this->assertRegExp("/^[A-Z0-9]{2,8}$/", $key);
		$id = ChallengeManager::getChallengeIdBySessionKey($this->dbh, $key);
		$this->assertGreaterThan(0, $id);

		$this->assertTrue(
			ChallengeManager::challengeExists($this->dbh, $id)
		);

		$this->assertTrue(
				ChallengeManager::checkChallengeKey($this->dbh, $id, $key)
		);

		$data = ChallengeManager::getChallengeInformation($this->dbh, $id);
		$this->assertEquals($data["name"], $name);
		$this->assertEquals($data["description"], $desc);
		$this->assertEquals($data["owner"], $this->testAccId);

		$this->assertEquals($this->testAccId, ChallengeManager::getOwner($this->dbh, $id));

		return array($id, $key);
	}

	public function testCoordinateNotExists(){
		$this->assertFalse(
			CoordinateManager::coordinateExists($this->dbh, PHP_INT_MAX)
		);
	}
}