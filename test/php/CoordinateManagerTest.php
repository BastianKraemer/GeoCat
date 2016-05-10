<?php

load("app/CoordinateManager.php");

class CoordinateManagerTest  extends PHPUnit_Framework_TestCase {
	private $testAccId;
	private $dbh;

	/**
	 * @before
	 */
	public function getTestAccount(){
		$this->testAccId = TestHelper::getTestAccountId(0);
		$this->dbh = TestHelper::getDBH();
	}

	public function testCreatePlace(){

		$name = "My coordinate";
		$name2 = "My coordinate with new name";
		$lat = 50.123;
		$lon = 8.765;
		$desc = "This is my coordinate";

		$placesBefore =	CoordinateManager::countPlacesOfAccount($this->dbh, $this->testAccId);

		$cid = CoordinateManager::createCoordinate($this->dbh, $name, $lat, $lon, $desc);
		$this->assertGreaterThanOrEqual(0, $cid);

		$this->assertTrue(
			CoordinateManager::coordinateExists($this->dbh, $cid)
		);

		$this->assertTrue(
			CoordinateManager::addPlace($this->dbh, $this->testAccId, $cid, false)
		);

		$this->assertEquals($placesBefore + 1,
			CoordinateManager::countPlacesOfAccount($this->dbh, $this->testAccId)
		);

		// Verify the values
		$coord = CoordinateManager::getCoordinateById($this->dbh, $cid);
		$this->assertEquals($name, $coord->name);
		$this->assertEquals($lat, $coord->lat);
		$this->assertEquals($lon, $coord->lon);
		$this->assertEquals($desc, $coord->desc);

		// Verify the values after a rename
		CoordinateManager::updateCoordinate($this->dbh, $cid, $name2, $lat, $lon, $desc);
		$coord = CoordinateManager::getCoordinateById($this->dbh, $cid);
		$this->assertEquals($name2, $coord->name);
		$this->assertEquals($lat, $coord->lat);
		$this->assertEquals($lon, $coord->lon);
		$this->assertEquals($desc, $coord->desc);

		// Remove the place
		CoordinateManager::removePlace($this->dbh, $this->testAccId, $cid);
		$this->assertEquals($placesBefore,
			CoordinateManager::countPlacesOfAccount($this->dbh, $this->testAccId)
		);

		// Test that the coordinate has been remove too
		$this->assertFalse(
			CoordinateManager::coordinateExists($this->dbh, $cid)
		);
	}

	public function testCoordinateNotExists(){
		$this->assertFalse(
			CoordinateManager::coordinateExists($this->dbh, POSTGRES_SERIAL_MAX)
		);
	}
}