<?php
	/*	GeoCat - Geolocation caching and tracking platform
	 Copyright (C) 2015 Bastian Kraemer

	 places.php

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

	require_once(__DIR__ . "/../app/CoordinateManager.php");
	require_once(__DIR__ . "/../app/SessionManager.php");
	require_once(__DIR__ . "/../app/JSONLocale.php");
	require_once(__DIR__ . "/../app/DBTools.php");
	require_once(__DIR__ . "/../app/DefaultRequestHandler.php");
	$config = require(__DIR__ . "/../config/config.php");

	try{
		$dbh = DBTools::connectToDatabase($config);
		$locale = JSONLocale::withBrowserLanguage();

		$session = new SessionManager();
		$placeHandler = new AJAXPlaceHandler($dbh, $session, $locale);

		print($placeHandler->handleRequest($_POST));
	}
	catch(Exception $e){
		print("Invalid request format.");
	}

	/**
	 * This class provides a interface to the CoordinateManager.
	 * To interact wih this class you have to send a HTTP request with one ore more parameters which will be mapped to the CoordinateManager
	 * @link CoordinateManager.html CoordinateManager
	 */
	class AJAXPlaceHandler {

		/**
		 * @var PDO Database handler
		 */
		private $dbh;

		/**
		 * @var SessionManager Session handler
		 */
		private $session;

		/**
		 * @var JSONLocale The JSONLocale object is used to localize messages
		 */
		private $locale;

		const KEY_NAME = "name";
		const KEY_LAT = "lat";
		const KEY_LON = "lon";
		const KEY_DESC = "desc";
		const KEY_IS_PUBLIC = "is_public";
		const KEY_LIMIT = "limit";
		const KEY_OFFSET = "offset";
		const KEY_COORD_ID = "coord_id";
		const KEY_OTHER_ACCOUNT = "other_account";

		/**
		 * Create a AJAXPlaceHandler
		 * @param PDO $databaseHandler
		 * @param SessionManager $session
		 * @param JSONLocale $translations
		 */
		public function __construct($databaseHandler, $session, $translations){
			$this->dbh = $databaseHandler;
			$this->session = $session;
			$this->locale = $translations;
		}

		/**
		 * Handles a request by using its parameters
		 * The action is selected by the "cmd" parameter which must be defined in the parameters array
		 * @param string[] $requestParameters The parameters from the HTTP request
		 * @throws InvalidArgumentException If the requestdata is invalid
		 * @throws MissingSessionException If the user has to be signed in to use this feature
		 */
		public function handleRequest($requestParameters){

			$req = new DefaultRequestHandler($requestParameters);

			try{
				if(!array_key_exists("cmd", $req->data)){
					throw new InvalidArgumentException("Parameter 'cmd' is not defined.");
				}

				$cmd = $req->data["cmd"];
				$ret;

				if($cmd == "add"){

					if(!$this->session->isSignedIn()){throw new MissingSessionException();}
					$ret = $this->addPlace($req->data);
				}
				else if($cmd == "get"){

					if(!$this->session->isSignedIn()){throw new MissingSessionException();}
					$ret = $this->getPlaces($req->data);
				}
				else if($cmd == "count"){

					if(!$this->session->isSignedIn()){throw new MissingSessionException();}
					$ret = array("count" => CoordinateManager::countPlacesOfAccount($this->dbh, $this->session->getAccountId(), self::getFilter($req->data)));
				}
				else if($cmd == "set" || $cmd == "update"){

					if(!$this->session->isSignedIn()){throw new MissingSessionException();}
					$ret = $this->updatePlace($req->data);
				}
				else if($cmd == "remove"){

					if(!$this->session->isSignedIn()){throw new MissingSessionException();}
					$ret = $this->removePlace($req->data);
				}
				else if($cmd == "get_public"){

					$ret = $this->getPublicPlaces($req->data);
				}
				else if($cmd == "count_public"){
					$ret = array("count" => CoordinateManager::countPublicPlaces($this->dbh, self::getFilter($req->data)));
				}
				else if($cmd == "nav_get"){
					if(!$this->session->isSignedIn()){throw new MissingSessionException();}
					$ret = CoordinateManager::getDestinationList($this->dbh, $this->session->getAccountId());
				}
				else if($cmd == "nav_add"){
					if(!$this->session->isSignedIn()){throw new MissingSessionException();}
					$ret = $this->addNavDestination($req->data);
				}
				else if($cmd == "nav_create"){
					if(!$this->session->isSignedIn()){throw new MissingSessionException();}
					$ret = $this->addNavDestinationCoord($req->data);
				}
				else if($cmd == "nav_remove" || $cmd == "nav_rm"){
					if(!$this->session->isSignedIn()){throw new MissingSessionException();}
					$ret = $this->removeNavDestination($req->data);
				}
				else{
					return "Unsupported command.";
				}

				return $req->prepareResponse($ret);
			}
			catch(InvalidArgumentException $e){
				return $req->prepareResponse(self::createDefaultResponse(false, "Invalid request: " . $e->getMessage()));
			}
			catch(MissingSessionException $e){
				return $req->prepareResponse(self::createDefaultResponse(false, "Access denied. Please sign in at first."));
			}
			catch(Exception $e){
				return $req->prepareResponse(array("status" => "error", "msg" => "Internal server error: " . $e->getMessage()));
			}
		}

		/**
		 * Creates a default response.
		 * A default response contains a "status" and a "msg" property
		 * @param boolean $successful
		 * @param string $msg
		 */
		private static function createDefaultResponse($successful, $msg){
			return array("status" => $successful ? "ok" : "failed", "msg" => $msg);
		}

		/**
		 * Performs a add place request.
		 * Required parameters: name, lat (latitude), lon (longitude), is_public
		 * Optional parameter: desc
		 * @param string[] $data Parameters of the request
		 * @return string[] String array with three elements: "status", "msg" and "coord_id"
		 * @throws InvalidArgumentException If one of the required parameters is undefined
		 * @throws Exception If an unknown error occurs
		 */
		private function addPlace($data){
			if(!self::requireValues($data, array(self::KEY_NAME, self::KEY_LAT, self::KEY_LON, self::KEY_IS_PUBLIC))){
				throw new InvalidArgumentException("One or more required parameters are undefined.\n".
												   "Please define '" . self::KEY_NAME . "', '" . self::KEY_LAT . "', '" . self::KEY_LON . "' and '" . self::KEY_IS_PUBLIC . "'.");
			}

			$accId = $this->session->getAccountId();
			$coordId = CoordinateManager::createCoordinate($this->dbh, $data[self::KEY_NAME], $data[self::KEY_LAT], $data[self::KEY_LON], array_key_exists(self::KEY_DESC, $data) ? $data[self::KEY_DESC] : null);

			if($coordId == -1){
				throw new Exception("Cannot store coordinate.");
			}

			if($data[self::KEY_IS_PUBLIC] == "false"){$data[self::KEY_IS_PUBLIC] = 0;}

			if(CoordinateManager::addPlace($this->dbh, $accId, $coordId, $data[self::KEY_IS_PUBLIC])){
				$ret = self::createDefaultResponse(true, "");
				$ret[self::KEY_COORD_ID] = $coordId;
				return $ret;
			}
			else{
				throw new Exception("Cannot append place.");
			}
		}

		/**
		 * Performs a update place request.
		 * Required parameters: name, lat (latitude), lon (longitude), is_public
		 * Optional parameter: desc
		 * @param string[] $data Parameters of the request
		 * @return string[] String array with two elements: "status", "msg"
		 * @throws InvalidArgumentException If one of the required parameters is undefined
		 * @throws Exception If an unknown error occurs
		 */
		private function updatePlace($data){
			if(!self::requireValues($data, array(self::KEY_NAME, self::KEY_LAT, self::KEY_LON, self::KEY_IS_PUBLIC, self::KEY_COORD_ID))){
				throw new InvalidArgumentException("One or more required parameters are undefined.\n".
												   "Please define '" . self::KEY_NAME . "', '" . self::KEY_LAT . "', '" . self::KEY_LON . "', '" . self::KEY_IS_PUBLIC . "' and '" . self::KEY_COORD_ID . "'.");
			}

			// If the key "SELF::KEY_OTHER_ACCOUNT" is defined then the user has to be an administrator
			$accId = array_key_exists(SELF::KEY_OTHER_ACCOUNT, $data) ? switchUser($dbh, CoordinateManager::getPlaceOwner($dbh, $data[self::KEY_COORD_ID])) : $this->session->getAccountId();

			$value = CoordinateManager::updatePlace($this->dbh, $accId, $data[self::KEY_COORD_ID], $data[self::KEY_NAME],
													$data[self::KEY_LAT], $data[self::KEY_LON],
													(array_key_exists(self::KEY_DESC, $data) ? $data[self::KEY_DESC] : null),
													$data[self::KEY_IS_PUBLIC]);

			if($value){
				return self::createDefaultResponse(true, "");
			}
			else{
				throw new Exception("Cannot update place.");
			}
		}

		/**
		 * Returns another account_id if the user is an administrator - otherwise its not allowed to act as another user
		 * @param PDO $dbh Database handler
		 * @param integer $toUser The account_id of another user
		 */
		private static function switchUser($dbh, $toUser){
			if(AccountManager::isAdministrator($dbh, $this->session->getAccountId())){
				return $toUser;
			}
			else{
				throw InvalidArgumentException("Access denied: You don't have the permission to act as adminsitrator.");
			}
		}

		/**
		 * Performs a get places request which returns the places of an user
		 * Required parameters: limit, offset
		 * Optional parameter: filter
		 * @param string[] $data Parameters of the request
		 * @return Coordinate[] Array of coordinates with a maximum length of $limit
		 * @throws InvalidArgumentException If one of the required parameters is undefined
		 */
		private function getPlaces($data){
			if(self::requireValues($data, array(self::KEY_LIMIT, self::KEY_OFFSET))){
				$accId = $this->session->getAccountId();
				return CoordinateManager::getPlacesByAccountId($this->dbh, $accId, intval($data[self::KEY_LIMIT]), intval($data[self::KEY_OFFSET]), self::getFilter($data));
			}
			else{
				throw new InvalidArgumentException("Required parameter '" . self::KEY_LIMIT . "' or '" . self::KEY_OFFSET . "' is undefined.");
			}
		}

		/**
		 * Performs a get public places request which returns a part of the public places list
		 * Required parameters: limit, offset
		 * Optional parameter: filter
		 * @param string[] $data Parameters of the request
		 * @return Coordinate[] Array of coordinates with a maximum length of $limit
		 * @throws InvalidArgumentException If one of the required parameters is undefined
		 */
		private function getPublicPlaces($data){
			if(self::requireValues($data, array(self::KEY_LIMIT, self::KEY_OFFSET))){
				return CoordinateManager::getPublicPlaces($this->dbh, intval($data[self::KEY_LIMIT]), intval($data[self::KEY_OFFSET]), self::getFilter($data));
			}
			else{
				throw new InvalidArgumentException("Required parameter '" . self::KEY_LIMIT . "' or '" . self::KEY_OFFSET . "' is undefined.");
			}
		}

		/**
		 * Performs a remove place request
		 * Required parameters: coord_id
		 * @param string[] $data Parameters of the request
		 * @return string[] String array with two elements: "status" and "msg"
		 * @throws InvalidArgumentException If $data["coord_id"] is undefined
		 */
		private function removePlace($data){
			if(self::requireValues($data, array(self::KEY_COORD_ID))){

				$accId;

				// If the key "SELF::KEY_OTHER_ACCOUNT" is defined then the user has to be an administrator
				if(array_key_exists(SELF::KEY_OTHER_ACCOUNT, $data)){
					$accId = switchUser($dbh, CoordinateManager::getPlaceOwner($dbh, $data[self::KEY_COORD_ID]));
				}
				else{
					$accId = $this->session->getAccountId();
					$owner = CoordinateManager::getPlaceOwner($this->dbh, $data[self::KEY_COORD_ID]);

					if($owner != $accId){
						throw new InvalidArgumentException("You don't have the permission to delete this place.");
					}
				}

				$result = CoordinateManager::removePlace($this->dbh, $accId, $data[self::KEY_COORD_ID]);

				return self::createDefaultResponse($result == 1, "");
			}
			else{
				throw new InvalidArgumentException("Required parameter '" . self::KEY_COORD_ID . "' is undefined.");
			}
		}

		/**
		 * Performs a add destination request which appends an existing coordinate to your current navigation list
		 * Required parameters: coord_id
		 * @param string[] $data Parameters of the request
		 * @return string[] String array with two elements: "status" and "msg"
		 * @throws InvalidArgumentException If $data["coord_id"] is undefined
		 */
		private function addNavDestination($data){
			if(self::requireValues($data, array(self::KEY_COORD_ID))){
				$result = CoordinateManager::addCoordinateToDestinationList($this->dbh, $this->session->getAccountId(), $data[self::KEY_COORD_ID]);
				return self::createDefaultResponse($result, "");
			}
			else{
				throw new InvalidArgumentException("Required parameter '" . self::KEY_COORD_ID . "' is undefined.");
			}
		}

		/**
		 * Performs a add destination request which appends a new coordinate to your current navigation list
		 * Required parameters: name, lat (latitude), lon (longitude)
		 * Optional parameter: desc
		 * @param string[] $data Parameters of the request
		 * @return string[] String array with three elements: "status", "msg" and "coord_id"
		 * @throws InvalidArgumentException If one of the required parameters is undefined
		 */
		private function addNavDestinationCoord($data){
			if(self::requireValues($data, array(self::KEY_NAME, self::KEY_LAT, self::KEY_LON))){

				$coordId = CoordinateManager::createCoordinate($this->dbh, $data[self::KEY_NAME], $data[self::KEY_LAT], $data[self::KEY_LON],
															   (array_key_exists(self::KEY_DESC, $data) ? $data[self::KEY_DESC] : null));


				$result = CoordinateManager::addCoordinateToDestinationList($this->dbh, $this->session->getAccountId(), $coordId);
				if($result){
					$ret = self::createDefaultResponse($result, "");
					$ret[self::KEY_COORD_ID] = $coordId;
					return $ret;
				}
				else{
					// Something went wrong - delete this coodinate
					CoordinateManager::tryToRemoveCooridate($this->dbh, $coordId);
					return self::createDefaultResponse(false, "An error occured. Please try again later.");
				}
			}
			else{
				throw new InvalidArgumentException("One or more required parameters are undefined.\n".
												   "Please define '" . self::KEY_NAME . "', '" . self::KEY_LAT . "', '" . self::KEY_LON . "'.");
			}
		}

		/**
		 * Performs a remove destination request which removes a coordinate from your current navigation list
		 * Required parameters: coord_id
		 * @param string[] $data Parameters of the request
		 * @return string[] String array with two elements: "status" and "msg"
		 * @throws InvalidArgumentException If $data["coord_id"] is undefined
		 */
		private function removeNavDestination($data){
			if(self::requireValues($data, array(self::KEY_COORD_ID))){
				$result = CoordinateManager::removeCoordinateFromDestinationList($this->dbh, $this->session->getAccountId(), $data[self::KEY_COORD_ID]);
				return self::createDefaultResponse($result == 1, "");
			}
			else{
				throw new InvalidArgumentException("Required parameter '" . self::KEY_COORD_ID . "' is undefined.");
			}
		}

		/**
		 * Extracts the filter from the request parameters and appends wildcards at the beginning and the end of the string
		 * @param string[] $data Request parameters
		 * @return string The filter with "%" as SQL wildcard: %FILTER%
		 */
		private static function getFilter($data){
			return array_key_exists("filter", $data) ? "%" . $data["filter"] . "%" : null;
		}

		/**
		 * Returns <code>true</code> if the array ($arr) contains all keys metioned in $values
		 * @param array $arr The array
		 * @param array $values The key values
		 * @return <code>true</code> if all keys are defined, <code>false</code> if not
		 */
		private static function requireValues($arr, $values){
			foreach($values as $val){
				if(!array_key_exists($val, $arr)){
					return false;
				}
			}
			return true;
		}
	}
?>