<?php
	/*	GeoCat - Geocaching and -Tracking platform
	 Copyright (C) 2015 Bastian Kraemer

	 CoordinateManager.php

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

	require_once(__DIR__ . "/dbtools.php");
	require_once(__DIR__ . "/account/accountmanager.php");

	/**
	 * This class is an abstraction layer between the database and the real Place or Coordinate objects
	 */
	class CoordinateManager {
		/**
		 * Name of the "Coordinate" table
		 * @var string
		 */
		const TABLE_COORDINATE = "Coordinate";

		/**
		 * Name of the "Place" table
		 * @var string
		 */
		const TABLE_PLACE = "Place";

		/**
		 * Name of the "Account" table
		 * @var string
		 */
		const TABLE_ACCOUNT = "Account";

		/**
		 * Returns a coordinate defined by its id
		 * @param PDO $dbh Database handler
		 * @param integer $coordinateId
		 * @return Coordinate|null The coordinate or <code>null</code> if $coordinateId is undefined in the database
		 */
		public static function getCoordinateById($dbh, $coordinateId){
			$res = DBTools::fetchAll($dbh, "SELECT name, description, latitude, longitude FROM " . self::TABLE_COORDINATE . " WHERE coord_id = :coordId",
					array("coordId" => $coordinateId));

			if(count($res) == 0){
				return null;
			}
			else{
				return new Coordinate($coordinateId, $res[0]["name"], $res[0]["latitude"], $res[0]["longitude"], $res[0]["description"]);
			}
		}

		/**
		 * Returns an array with all public places
		 * @param PDO $dbh Database handler
		 * @param integer $limit Maximal number of places that should be returned
		 * @param integer $offset Number of places that should be skipped (for example an offset of 4 will skip the first four places)
		 * @return Place[] An array of Places
		 * @throws InvalidArgumentException If $limit or $offset are not an integer value
		 */
		public static function getPublicPlaces($dbh, $limit, $offset){
			if(!is_int($limit) || !is_int($offset)){throw new InvalidArgumentException("The parameters 'limit' and 'offset' habe to be integer values!");}
			$res = DBTools::fetchAll($dbh,	"SELECT Account.username, Place.coord_id, Place.creation_date, Place.modification_date " .
											"FROM " . SELF::TABLE_ACCOUNT . ", " . SELF::TABLE_PLACE . " " .
											"WHERE is_public = 1 AND Place.account_id = Account.account_id LIMIT " . $limit . " OFFSET " . $offset);

			$ret = array();

			for($i = 0; $i < count($res); $i++){
				$coord = self::getCoordinateById($dbh, $res[$i]["coord_id"]);
				$ret[] = new Place($res[$i]["username"], 1, $res[$i]["creation_date"], $res[$i]["modification_date"], $coord);
			}

			return $ret;
		}

		/**
		 * Counts all public places
		 * @param PDO $dbh Database handler
		 */
		public static function countPublicPlaces($dbh){
			$res = DBTools::fetchAll($dbh, "SELECT COUNT(coord_id) FROM ". self::TABLE_PLACE . " WHERE is_public = 1");
			if($res){
				return $res[0][0];
			}
			else{
				self::printError("ERROR: Count of public places failed (Table: '". self::TABLE_COORDINATE . "')", array("\$res" => $res));
				return 0;
			}
		}

		/**
		 * Returns all places of an specific account
		 * @param PDO $dbh Database handler
		 * @param integer $account_id
		 * @param integer $limit Maximal number of places that should be returned
		 * @param integer $offset Number of places that should be skipped (for example an offset of 4 will skip the first four places)
		 * @return Place[] An array of Places
		 * @throws InvalidArgumentException If $limit or $offset are not an integer value or if the $account_id does not exist
		 */
		public static function getPlacesByAccountId($dbh, $account_id, $limit, $offset){
			if(!is_int($limit) || !is_int($offset)){throw new InvalidArgumentException("The parameters 'limit' and 'offset' habe to be integer values!");}
			$res = DBTools::fetchAll($dbh, "SELECT coord_id, is_public, creation_date, modification_date FROM ". self::TABLE_PLACE . " WHERE account_id = :accid LIMIT " . $limit . " OFFSET " . $offset,
									 array("accid" => $account_id));

			$username = AccountManager::getUserNameByAccountId($dbh, $account_id);
			$ret = array();
			for($i = 0; $i < count($res); $i++){
				$coord = self::getCoordinateById($dbh, $res[$i]["coord_id"]);
				$ret[] = new Place($username, $res[$i]["is_public"], $res[$i]["creation_date"], $res[$i]["modification_date"], $coord);
			}
			return $ret;
		}

		/**
		 * Counts the places of a specific account
		 * @param PDO $dbh Database handler
		 * @param integer $account_id
		 */
		public static function countPlacesOfAccount($dbh, $account_id){
			$res = DBTools::fetchAll($dbh, "SELECT COUNT(coord_id) FROM ". self::TABLE_PLACE . " WHERE account_id = :accid",
									 array("accid" => $account_id));

			if($res){
				return $res[0][0];
			}
			else{
				self::printError("ERROR: Count of public places of account (Table: '". self::TABLE_COORDINATE . "')",
								 array("\$res" => $res, "\$account_id" => $account_id));
				return 0;
			}
		}

		/**
		 * Creates a new row in the "Coordinate" table
		 * @param PDO $dbh Database handler
		 * @param string $name coordinate name
		 * @param double $latitude coordinate latitude
		 * @param double $longitude coordinate longitude
		 * @param string $decription coordinate decription (can be <code>null</code>)
		 * @return integer The <code>coord_id</code> of the new row or <code>-1</code> if an error occured.
		 * @throws InvalidArgumentException If $name or $descripton are not valid names
		 * @see self::isValidCoordinateName()
		 * Function isValidCoordinateName()
		 * @see self::isValidCoordinateDescription()
		 * Function isValidCoordinateDescription()
		 */
		public static function createCoordinate($dbh, $name, $latitude, $longitude, $decription){
			if(!is_double($latitude) || !is_double($longitude) || !self::isValidCoordinateName($name) || !self::isValidCoordinateDescription($decription)){
				throw new InvalidArgumentException("One ore more parameters have invalid values.");
			}

			$res = DBTools::query($dbh, "INSERT INTO ". self::TABLE_COORDINATE . " (coord_id, name, description, latitude, longitude) VALUES (NULL, :name, :desc, :lat, :lon)",
									array("name" => $name, "desc" => $decription, "lat" => $latitude, "lon" => $longitude));

			if($res){
				return $dbh->lastInsertId("coord_id");
			}
			else{
				self::printError("ERROR: Cannot insert into table '". self::TABLE_COORDINATE . "'",
								  array("\$res" => $res, "\$name" => $name, "\$latitude" => $latitude, "\$longitude" => $longitude, "\$decription" => $decription));
				return -1;
			}
		}

		/**
		 * Checks if a coordiante exists
		 * @param PDO $dbh Database handler
		 * @param integer $coord_id
		 * @return boolean
		 */
		public static function coordinateExists($dbh, $coord_id){
			$res = DBTools::fetchAll($dbh, "SELECT COUNT(coord_id) FROM ". self::TABLE_COORDINATE . " WHERE coord_id = :coordId; ",
					array("coordId" => $coord_id));

			return $res[0][0] == 1;
		}

		/**
		 * Adds a place to the "Place" table
		 * @param PDO $dbh Database handler
		 * @param integer $accountid The <code>account_id</code> of the user
		 * @param integer $coordinate_id The <code>coord_id</code> of the coordinate
		 * @param boolean $isPublic Specify if the new place should be public (visible for everyone)
		 * @return boolean <code>true</code> if the operation was successful, <code>false</code> if not
		 * @throws InvalidArgumentException
		 */
		public static function addPlace($dbh, $accountid, $coordinate_id, $isPublic){
			if(self::coordinateExists($dbh, $coordinate_id)){
				$res = DBTools::query($dbh, "INSERT INTO ". self::TABLE_PLACE . " (coord_id, account_id, is_public) VALUES (:coordId, :accid, :isPublic)",
						array("coordId" => $coordinate_id, "accid" => $accountid, "isPublic" => $isPublic ? 1 : 0));

				if($res){
					return true;
				}
				else{
					self::printError("ERROR: Cannot insert into table '". self::TABLE_PLACE . "'",
							array("\$res" => $res, "\$accountid" => $accountid, "\$coordinate_id" => $coordinate_id, "\$isPublic" => $isPublic));
					return false;
				}

			}
			else{
				throw new InvalidArgumentException("Coordinate does not exist.");
			}
		}

		/**
		 * Updates a place
		 * @param PDO $dbh Database handler
		 * @param integer $account_id
		 * @param integer $coordinate_id
		 * @param string $newName
		 * @param string $newLatitude
		 * @param string $newLongitude
		 * @param string $newDescription
		 * @param boolean $isPublic
		 * @return boolean <code>true</code> if the operation was successful, <code>false</code> if not
		 * @throws InvalidArgumentException If $name or $descripton are not valid names or the coordinate does not exist
		 * @see self::isValidCoordinateName()
		 * Function isValidCoordinateName()
		 * @see self::isValidCoordinateDescription()
		 * Function isValidCoordinateDescription()
		 * @see self::coordinateExists()
		 * Function coordinateExists()
		 */
		public static function updatePlace($dbh, $account_id, $coordinate_id, $newName, $newLatitude, $newLongitude, $newDescription, $isPublic){
			if(self::isValidCoordinateName($newName) && self::isValidCoordinateDescription($newDescription)){
				if(self::coordinateExists($dbh, $coordinate_id)){

					$res1 = DBTools::query($dbh, "UPDATE ". self::TABLE_COORDINATE . " " .
												 "SET name = :name, description = :desc, latitude = :lat, longitude = :lon " .
												 "WHERE coord_id = :coordId",
												  array("coordId" => $coordinate_id, "name" => $newName, "desc" => $newDescription,
														"lat" => $newLatitude, "lon" => $newLongitude));

					if(!$res1){
						// ERROR - Write debug information to PHP error log
						self::printError("ERROR: Cannot update coordinate table '". self::TABLE_COORDINATE . "'",
										  array("\$res" => $res, "\$accountid" => $accountid, "\$coordinate_id" => $coordinate_id,
												"\$newName" => $newName, "\$newLatitude" => $newLatitude, "\$newLongitude" => $newLongitude,
										  		"\$newDescription" => $newDescription, "\$isPublic" => $isPublic));
						return false;
					}

					$res2 = DBTools::query($dbh, "UPDATE ". self::TABLE_PLACE . " SET is_public = :isPublic, modification_date = CURRENT_TIMESTAMP " .
												 "WHERE account_id = :accid AND coord_id = :coordId",
												  array("accid" => $account_id, "coordId" => $coordinate_id, "isPublic" => $isPublic ? 1 : 0));

					if($res2){
						return true;
					}
					else{
						self::printError("ERROR: Cannot update place in table '". self::TABLE_PLACE . "'",
								array("\$res" => $res, "\$accountid" => $accountid, "\$coordinate_id" => $coordinate_id));
						return false;
					}
				}
				else{
					throw new InvalidArgumentException("Coordinate does not exist.");
				}
			}
			else{
				throw new InvalidArgumentException("Invalid coordinate name or description");
			}
		}

		/**
		 * Removes a place from the "Place" table
		 * @param PDO $dbh Database handler
		 * @param integer $account_id The <code>account_id</code> of the user
		 * @param integer $coord_id The <code>coord_id</code> of the coordinate
		 * @return The number of removed rows (this should be <code>1</code>) or <code>-1</code> if the operation failed.
		 */
		public static function removePlace($dbh, $account_id, $coord_id){
			$stmt = $dbh->prepare("DELETE FROM " . self::TABLE_PLACE ." WHERE account_id = :accid AND coord_id = :coordId");
			$res = $stmt->execute(array("accid" => $account_id, "coordId" => $coord_id));

			if($res){
				// Try to remove the coordinate - if it is referenced to other tables this won't work, so the coordinate is still available
				try {
					DBTools::query($dbh, "DELETE FROM " . self::TABLE_COORDINATE . " WHERE coord_id = :coordId", array("coordId" => $coord_id));
				}
				catch(PDOException $e){} //Ignore this error

				return $stmt->rowCount();
			}
			else{
				self::printError("ERROR: Cannot delete rows in table'". self::TABLE_PLACE . "'",
						array("\$res" => $res, "\$accountid" => $accountid, "\$coordinate_id" => $coordinate_id));
				return -1;
			}
		}

		/**
		 * Checks if a coordinate name is valid.
		 * The conditions for this are: Only "A-Z", "a-z", "0-9" or ine of the characters "_ ,;.!#-*()" and a length less than 64.
		 * @param string $name
		 * @return boolean
		 */
		public static function isValidCoordinateName($name){
			return preg_match("/^[A-Za-z0-9_ ,;\.\!\#\-\*\(\)]{1,63}$/", $name);
		}

		/**
		 * Checks if a coordinate description is valid.
		 * This function returns <code>true</code> if the description does not contain "<" or ">" and has less than 256 characters.
		 * @param string $desc
		 * @return boolean
		 */
		public static function isValidCoordinateDescription($desc){
			if($desc == null){return true;}
			return preg_match("/^[^<>]{1,255}$/", $desc);
		}

		/**
		 * Writes a error message and debug informations to the php error log
		 * @param string $msg The error message
		 * @param string[] $vars Array of varaibles that may contain useful debug information
		 */
		private static function printError($msg, $vars){
			$txt = $msg;
			foreach ($vars as $key => $value){
				$txt = $txt . "\n    [$key=$value]";
			}
			error_log("ERROR in class 'Places' (places.php)\n" . $txt);
		}
	}

	/**
	 * Class Place, this class can store all information that belongs to a place
	 */
	class Place {
		/** @var string The username of the place owner */
		public $owner;

		/** @var boolean Is this place visible for everyone? */
		public $isPublic;

		/** @var string Creation date of this place */
		public $creationDate;

		/** @var string Modification date of this place */
		public $modificationDate;

		/** @var Coordinate Reference to the Coordinate object that belongs to this place */
		public $coordinate;

		/**
		 * Creates a new Place object
		 * @param integer $accountId
		 * @param boolean $isPublic
		 * @param string $creationDate
		 * @param string $modificationDate
		 * @param Coordinate $coordinate
		 */
		public function __construct($owner, $isPublic, $creationDate, $modificationDate, $coordinate){
			$this->owner = $owner;
			$this->isPublic = $isPublic;
			$this->creationDate = $creationDate;
			$this->modificationDate = $modificationDate;
			$this->coordinate = $coordinate;
		}
	}

	/**
	 * Class Coordinate, this class can store all information that belongs to a coordinate
	 */
	class Coordinate{
		/** @var integer coordinate id (coord_id)*/
		public $id;

		/** @var string Name of this coordinate */
		public $name;

		/** @var string Description of this coordinate */
		public $desc;

		/** @var double Latitude of this coordinate */
		public $lat;

		/** @var double Longitude of this coordinate */
		public $lon;

		/**
		 * Creates a new Coordinate object
		 * @param integer $coordinteId
		 * @param string $coordinateName
		 * @param double $latitude
		 * @param double $longitude
		 * @param string $description
		 */
		public function __construct($coordinteId, $coordinateName, $latitude, $longitude, $description){
			$this->id = $coordinteId;
			$this->name = $coordinateName;
			$this->desc = $description;
			$this->lat = $latitude;
			$this->lon = $longitude;
		}
	}
?>
