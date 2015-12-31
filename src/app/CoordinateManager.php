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

	require_once(__DIR__ . "/DBTools.php");
	require_once(__DIR__ . "/AccountManager.php");

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
		 * Name of the "CurrentNavigation" table
		 * @var string
		 */
		const TABLE_CURRENT_NAV = "CurrentNavigation";

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
		 * @param string $filter (optional) Filter to exclude entries that doesn't match the string
		 * 						 (use % to match any characters; the filter will be applied to name and description)
		 * @return Place[] An array of Places
		 * @throws InvalidArgumentException If $limit or $offset are not an integer value
		 */
		public static function getPublicPlaces($dbh, $limit, $offset, $filter = null){
			if(!is_int($limit) || !is_int($offset)){throw new InvalidArgumentException("The parameters 'limit' and 'offset' habe to be integer values!");}
			$res = DBTools::fetchAll($dbh,	"SELECT Account.username, Place.coord_id, Place.creation_date, Place.modification_date, " .
													"Coordinate.name, Coordinate.description, Coordinate.latitude, Coordinate.longitude " .
											"FROM " . SELF::TABLE_ACCOUNT . ", " . SELF::TABLE_PLACE . ", " . SELF::TABLE_COORDINATE . " " .
											"WHERE Place.is_public = 1 AND Place.account_id = Account.account_id AND Place.coord_id = Coordinate.coord_id " .
													($filter == null ? "" : "AND (Coordinate.name LIKE :filter OR Coordinate.description LIKE :filter) ") .
											"LIMIT " . $limit . " OFFSET " . $offset, ($filter != null ? array("filter" => $filter) : null));

			if($res){
				$ret = array();

				for($i = 0; $i < count($res); $i++){
					$coord = new Coordinate($res[$i]["coord_id"], $res[$i]["name"], $res[$i]["latitude"], $res[$i]["longitude"], $res[$i]["description"]);
					$ret[] = new Place($res[$i]["username"], 1, $res[$i]["creation_date"], $res[$i]["modification_date"], $coord);
				}

				return $ret;
			}
			else{
				return array();
			}
		}

		/**
		 * Counts all public places
		 * @param PDO $dbh Database handler
		 * @param string $filter (optional) Filter to exclude entries that doesn't match the string
		 * 						 (use % to match any characters; the filter will be applied to name and description)
		 */
		public static function countPublicPlaces($dbh, $filter = null){
			$res;

			if($filter == null){
				$res = DBTools::fetchAll($dbh, "SELECT COUNT(coord_id) FROM ". SELF::TABLE_PLACE . " WHERE is_public = 1");
			}
			else{
				$res = DBTools::fetchAll($dbh,	"SELECT COUNT(Place.coord_id) " .
												"FROM " . SELF::TABLE_PLACE . ", " . SELF::TABLE_COORDINATE . " " .
												"WHERE is_public = 1 AND Place.coord_id = Coordinate.coord_id AND " .
													"(Coordinate.name LIKE :filter OR Coordinate.description LIKE :filter)",
												array("filter" => $filter));
			}

			if($res){
				return $res[0][0];
			}
			else{
				self::printError("ERROR: Count of public places failed (Table: '". self::TABLE_COORDINATE . "')", array("\$res" => json_encode($res)));
				return 0;
			}
		}

		/**
		 * Returns all places of an specific account
		 * @param PDO $dbh Database handler
		 * @param integer $account_id
		 * @param integer $limit Maximal number of places that should be returned
		 * @param integer $offset Number of places that should be skipped (for example an offset of 4 will skip the first four places)
		 * @param string $filter (optional) Filter to exclude entries that doesn't match the string
		 * 						 (use % to match any characters; the filter will be applied to name and description)
		 * @return Place[] An array of Places
		 * @throws InvalidArgumentException If $limit or $offset are not an integer value or if the $account_id does not exist
		 */
		public static function getPlacesByAccountId($dbh, $account_id, $limit, $offset, $filter = null){
			if(!is_int($limit) || !is_int($offset)){throw new InvalidArgumentException("The parameters 'limit' and 'offset' habe to be integer values!");}

			$arr = $filter != null ? array("accid" => $account_id, "filter" => $filter) : array("accid" => $account_id);

			$res = DBTools::fetchAll($dbh, "SELECT Place.coord_id, Place.is_public, Place.creation_date, Place.modification_date, " .
												  "Coordinate.name, Coordinate.description, Coordinate.latitude, Coordinate.longitude " .
											"FROM ". self::TABLE_PLACE . ", " . self::TABLE_COORDINATE . " "  .
											"WHERE account_id = :accid AND Place.coord_id = Coordinate.coord_id " .
												($filter == null ? "" : "AND (Coordinate.name LIKE :filter OR Coordinate.description LIKE :filter) ") .
											"LIMIT " . $limit . " OFFSET " . $offset, $arr);
			if($res){
				$username = AccountManager::getUserNameByAccountId($dbh, $account_id);

				$ret = array();
				for($i = 0; $i < count($res); $i++){
					$coord = new Coordinate($res[$i]["coord_id"], $res[$i]["name"], $res[$i]["latitude"], $res[$i]["longitude"], $res[$i]["description"]);
					$ret[] = new Place($username, $res[$i]["is_public"], $res[$i]["creation_date"], $res[$i]["modification_date"], $coord);
				}
				return $ret;
			}
			else{
				return array();
			}
		}

		/**
		 * Counts the places of a specific account
		 * @param PDO $dbh Database handler
		 * @param integer $account_id
		 * @param string $filter (optional) Filter to exclude entries that doesn't match the string
		 * 						 (use % to match any characters; the filter will be applied to name and description)
		 */
		public static function countPlacesOfAccount($dbh, $account_id, $filter = null){

			$res;
			if($filter == null){
				$res = DBTools::fetchAll($dbh, "SELECT COUNT(coord_id) FROM ". self::TABLE_PLACE . " WHERE account_id = :accid",
												array("accid" => $account_id));
			}
			else{
				$res = DBTools::fetchAll($dbh,	"SELECT COUNT(Place.coord_id) " .
												"FROM " . SELF::TABLE_PLACE . ", " . SELF::TABLE_COORDINATE . " " .
												"WHERE account_id = :accid AND Place.coord_id = Coordinate.coord_id AND " .
													"(Coordinate.name LIKE :filter OR Coordinate.description LIKE :filter)",
												array("accid" => $account_id, "filter" => $filter));
			}

			if($res){
				return $res[0][0];
			}
			else{
				self::printError("ERROR: Count of private account places faied (Table: '". self::TABLE_COORDINATE . "').",
								 array("\$res" => json_encode($res), "\$account_id" => $account_id));
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
			self::verifyCoordinate($name, $latitude, $longitude, $decription);

			$res = DBTools::query($dbh, "INSERT INTO ". self::TABLE_COORDINATE . " (coord_id, name, description, latitude, longitude) VALUES (NULL, :name, :desc, :lat, :lon)",
									array("name" => $name, "desc" => $decription, "lat" => $latitude, "lon" => $longitude));

			if($res){
				return $dbh->lastInsertId("coord_id");
			}
			else{
				self::printError("ERROR: Cannot insert into table '". self::TABLE_COORDINATE . "'",
								  array("\$res" => json_encode($res), "\$name" => $name, "\$latitude" => $latitude, "\$longitude" => $longitude, "\$decription" => $decription));
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
							array("\$res" => json_encode($res), "\$accountid" => $accountid, "\$coordinate_id" => $coordinate_id, "\$isPublic" => $isPublic));
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
		 * @throws InvalidArgumentException If $name or $descripton are not valid names, the coordinate does not exist or if you don't have the right permission
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
					if(self::isOwnerOfPlace($dbh, $account_id, $coordinate_id)){

						self::verifyCoordinate($newName, $newLatitude, $newLongitude, $newDescription);

						$res1 = DBTools::query($dbh, "UPDATE ". self::TABLE_COORDINATE . " " .
													 "SET name = :name, description = :desc, latitude = :lat, longitude = :lon " .
													 "WHERE coord_id = :coordId",
													  array("coordId" => $coordinate_id, "name" => $newName, "desc" => $newDescription,
															"lat" => $newLatitude, "lon" => $newLongitude));

						if(!$res1){
							// ERROR - Write debug information to PHP error log
							self::printError("ERROR: Cannot update coordinate table '". self::TABLE_COORDINATE . "'",
											  array("\$res" => json_encode($res), "\$accountid" => $accountid, "\$coordinate_id" => $coordinate_id,
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
									array("\$res" => json_encode($res), "\$accountid" => $accountid, "\$coordinate_id" => $coordinate_id));
							return false;
						}
					}
					else{
						throw new InvalidArgumentException("You don't have the permission to modify this entry.");
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
				self::tryToRemoveCooridate($dbh, $coord_id);

				return $stmt->rowCount();
			}
			else{
				self::printError("ERROR: Cannot delete rows in table'". self::TABLE_PLACE . "'",
						array("\$res" => json_encode($res), "\$accountid" => $accountid, "\$coordinate_id" => $coordinate_id));
				return -1;
			}
		}

		/**
		 * Checks if a user is authorized to modify a place entry
		 * @param PDO $dbh Database handler
		 * @param integer $accountId The account_id
		 * @param integer $coord_id The coordinate id
		 * @return boolean
		 * @throws InvalidArgumentException If the account_id is undefined in the database
		 */
		public static function isOwnerOfPlace($dbh, $accountId, $coord_id){
			$result = DBTools::fetchAll($dbh, "SELECT account_id FROM " . self::TABLE_PLACE . " WHERE coord_id = :coordId", array("coordId" => $coord_id));
			if(empty($result) || count($result) != 1){throw InvalidArgumentException("Undefined account_id.");}
			return $result[0][0] == $accountId;
		}

		/**
		 * Returns the owner of a place
		 * @param PDO $dbh Database handler
		 * @param integer $coord_id The coordinate id
		 * @return integer
		 * @throws InvalidArgumentException If the account_id is undefined in the database
		 */
		public static function getPlaceOwner($dbh, $coord_id){
			$result = DBTools::fetchAll($dbh, "SELECT account_id FROM " . self::TABLE_PLACE . " WHERE coord_id = :coordId", array("coordId" => $coord_id));
			if(empty($result) || count($result) != 1){throw InvalidArgumentException("Undefined account_id.");}
			return $result[0][0];
		}

		/**
		 * This mehtod will try to remove a coordinate, this will fail if it is still referenced to other tables.
		 * @param PDO $dbh Database handler
		 * @param integer $coord_id The coordinate id
		 */
		public static function tryToRemoveCooridate($dbh, $coord_id){
			// Try to remove a coordinate - if it is referenced to other tables this won't work, so the coordinate is still available
			try {
				DBTools::query($dbh, "DELETE FROM " . self::TABLE_COORDINATE . " WHERE coord_id = :coordId", array("coordId" => $coord_id));
			}
			catch(PDOException $e){} //Ignore this error
		}

		/**
		 * Checks if a coordinate name is valid.
		 * The conditions for this are: Only "A-Z", "a-z", "0-9" or ine of the characters "_ ,;.!#-*()" and a length less than 64.
		 * @param string $name
		 * @return boolean
		 */
		public static function isValidCoordinateName($name){
			return preg_match("/^[A-Za-z0-9ÄäÖöÜüß_ ,;\.\!\#\-\*\(\)]{1,63}$/", $name);
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
		 * Returns an array of Coordinates which matches the current destination list of the user
		 * @param PDO $dbh Database handler
		 * @param integer $accountId
		 * @return Coordinate[]
		 */
		public static function getDestinationList($dbh, $accountId){

			$res = DBTools::fetchAll($dbh, "SELECT CurrentNavigation.coord_id, Coordinate.name, Coordinate.description, Coordinate.latitude, Coordinate.longitude " .
					"FROM ". self::TABLE_CURRENT_NAV . ", " . self::TABLE_COORDINATE . " "  .
					"WHERE CurrentNavigation.account_id = :accid AND CurrentNavigation.coord_id = Coordinate.coord_id",
					array("accid" => $accountId));

			if($res){
				$ret = array();

				for($i = 0; $i < count($res); $i++){
					$ret[] = new Coordinate($res[$i]["coord_id"], $res[$i]["name"], $res[$i]["latitude"], $res[$i]["longitude"], $res[$i]["description"]);
				}

				return $ret;
			}
			else{
				return array();
			}
		}

		/**
		 * Adds a coordinate to the current destination list of a user
		 * @param PDO $dbh
		 * @param integer $accountId
		 * @param integer $coord_id
		 * @throws InvalidArgumentException if the coordinate does not exist
		 */
		public static function addCoordinateToDestinationList($dbh, $accountId, $coord_id){

			if(!self::coordinateExists($dbh, $coord_id)){
				throw new InvalidArgumentException("This coordinate does not exist.");
			}

			$res = DBTools::query($dbh, "INSERT INTO ". self::TABLE_CURRENT_NAV . " (account_id, coord_id) VALUES (:accid, :coordId)",
									array("accid" => $accountId, "coordId" => $coord_id));

			if($res){
				return true;
			}
			else{
				self::printError("ERROR: Cannot insert into table '". self::TABLE_CURRENT_NAV . "'", array("\$res" => json_encode($res), "\$accountId" => $accountId, "\$coord_id" => $coord_id));
				return false;
			}
		}

		/**
		 * Removes a coordinate from the current destination list of a user
		 * @param PDO $dbh
		 * @param Integer $accountId
		 * @param Integer $coord_id
		 * @return Integer The number of deleted rows (should be 1)
		 */
		public static function removeCoordinateFromDestinationList($dbh, $account_id, $coord_id){

			$stmt = $dbh->prepare("DELETE FROM " . self::TABLE_CURRENT_NAV ." WHERE account_id = :accid AND coord_id = :coordId");
			$res = $stmt->execute(array("accid" => $account_id, "coordId" => $coord_id));

			if($res){
				self::tryToRemoveCooridate($dbh, $coord_id);
				return $stmt->rowCount();
			}
			else{
				self::printError("ERROR: Cannot delete rows in table'". self::TABLE_CURRENT_NAV . "'",
						array("\$res" => json_encode($res), "\$accountid" => $account_id, "\$coordinate_id" => $coordinate_id));
				return -1;
			}
		}

		/**
		 * Verifies a coordinate to check if the values are valid.
		 * If one check fails thi smethod throws an exception
		 * @param String $name
		 * @param String|Double $latitude
		 * @param String|Double $longitude
		 * @param String $decription
		 * @throws InvalidArgumentException
		 */
		private static function verifyCoordinate($name, $latitude, $longitude, $decription){
			if($latitude == "" || $longitude == ""){throw new InvalidArgumentException("Latitude or longitude are undefined.");}

			if(!is_double($latitude)){
				if(preg_match("/^(-)?[0-9]+\.[0-9]{1,8}$/", $latitude)){
					$latitide = floatval($latitude);
				}
				else{
					throw new InvalidArgumentException("Value for 'latitude' is invalid.");
				}
			}

			if(!is_double($longitude)){
				if(preg_match("/^(-)?[0-9]+\.[0-9]{1,8}$/", $longitude)){
					$longitude = floatval($longitude);
				}
				else{
					throw new InvalidArgumentException("Value for 'longitude' is invalid.");
				}
			}

			if(!self::isValidCoordinateName($name) || !self::isValidCoordinateDescription($decription)){
				throw new InvalidArgumentException("Latitude or longitude are undefined.");
			}
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
		 * @param integer $owner
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
		public $coord_id;

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
		 * @param integer $coord_id
		 * @param string $coordinateName
		 * @param double $latitude
		 * @param double $longitude
		 * @param string $description
		 */
		public function __construct($coordinteId, $coordinateName, $latitude, $longitude, $description){
			$this->coord_id = $coordinteId;
			$this->name = $coordinateName;
			$this->desc = $description;
			$this->lat = $latitude;
			$this->lon = $longitude;
		}
	}
?>
