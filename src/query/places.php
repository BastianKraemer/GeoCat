<?php
/* GeoCat - Geocaching and -tracking application
 * Copyright (C) 2015-2016 Bastian Kraemer
 *
 * places.php
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
 * RESTful service for GeoCat to deal with places and coordinates
 * @package query
 */

	require_once(__DIR__ . "/../app/RequestInterface.php");
	require_once(__DIR__ . "/../app/DBTools.php");
	require_once(__DIR__ . "/../app/JSONLocale.php");
	require_once(__DIR__ . "/../app/CoordinateManager.php");

	/**
	 * This class provides an interface to the CoordinateManager
	 *
	 * To interact wih this class you have to send a HTTP request to '/query/places.php' with one ore more parameters which will be mapped to the CoordinateManager
	 * @link CoordinateManager.html CoordinateManager
	 */
	class PlacesHTTPRequestHandler extends RequestInterface {

		/**
		 * @var PDO Database handler
		 */
		private $dbh;

		const KEY_NAME = "name";
		const KEY_LAT = "lat";
		const KEY_LON = "lon";
		const KEY_DESC = "desc";
		const KEY_IS_PUBLIC = "is_public";
		const KEY_LIMIT = "limit";
		const KEY_OFFSET = "offset";
		const KEY_COORD_ID = "coord_id";
		const KEY_OTHER_ACCOUNT = "other_account";

		const COORD_REGEX = "/^[0-9]{1,3}([,\.][0-9]+)?$/";

		/**
		 * Create a PlacesHTTPRequestHandler instance
		 * @param String[] HTTP parameters
		 * @param PDO $dbh Database handler
		 */
		public function __construct($parameters, $dbh){
			parent::__construct($parameters, JSONLocale::withBrowserLanguage());
			$this->dbh = $dbh;
		}

		/**
		 * Handles the request by using the value from the 'task' parameter
		 */
		public function handleRequest(){
			$this->handleAndSendResponseByArgsKey("task");
		}

		/**
		 * Performs a add place request
		 *
		 * Required parameters ($this->args): name, lat (latitude), lon (longitude), is_public
		 * Optional parameter: desc
		 * @return string[] String array with two elements: "status", "coord_id"
		 * @throws InvalidArgumentException If one of the required parameters is undefined
		 * @throws Exception If an unknown error occurs
		 */
		protected function add(){
			$this->requireParameters(array(
					self::KEY_NAME => "/^(.*){1,64}$/",
					self::KEY_LAT => self::COORD_REGEX,
					self::KEY_LON => self::COORD_REGEX,
					self::KEY_IS_PUBLIC => "/^[0-1]$/"
			));

			$this->assignOptionalParameter(self::KEY_DESC, null);

			$session = $this->requireLogin();
			$accId = $session->getAccountId();

			$coordId = CoordinateManager::createCoordinate($this->dbh, $this->args[self::KEY_NAME], $this->args[self::KEY_LAT], $this->args[self::KEY_LON],
														   $this->args[self::KEY_DESC]);

			if($coordId == -1){
				throw new Exception("Cannot store coordinate.");
			}

			if(CoordinateManager::addPlace($this->dbh, $accId, $coordId, $this->args[self::KEY_IS_PUBLIC])){
				return self::buildResponse(true, array(self::KEY_COORD_ID => $coordId));
			}
			else{
				throw new Exception("Cannot append place.");
			}
		}

		/**
		 * Performs a update place request
		 *
		 * Required parameters in ($this->args): name, lat (latitude), lon (longitude), is_public
		 * Optional parameter: desc
		 * @return string[] String array with a "status" element
		 * @throws InvalidArgumentException If one of the required parameters is undefined
		 * @throws Exception If an unknown error occurs
		 */
		protected function update(){
			$this->requireParameters(array(
					self::KEY_NAME => "/^(.*){1,64}$/",
					self::KEY_LAT => self::COORD_REGEX,
					self::KEY_LON => self::COORD_REGEX,
					self::KEY_IS_PUBLIC => "/^[0-1]$/",
					self::KEY_COORD_ID => "/^[0-9]+$/"
			));

			$this->verifyOptionalParameters(array(
					self::KEY_DESC => "/^(.*){1,256}$/",
					self::KEY_OTHER_ACCOUNT => "/[0-9]+/"
			));

			$session = $this->requireLogin();
			$this->assignOptionalParameter(self::KEY_DESC, null);
			$this->assignOptionalParameter(self::KEY_OTHER_ACCOUNT, null);

			$accId;

			if($this->args[SELF::KEY_OTHER_ACCOUNT] != null){
				// The user wants to act as administrator
				$accId = $this->switchUser($session, $session->getAccountId());
			}
			else{
				$accId = $session->getAccountId();
				if(!CoordinateManager::isOwnerOfPlace($this->dbh, $accId, $this->args[self::KEY_COORD_ID])){
					throw new InvalidArgumentException("Access denied: You don't have the permission to update this coordinate.");
				}
			}

			$value = CoordinateManager::updatePlace($this->dbh, $accId, $this->args[self::KEY_COORD_ID], $this->args[self::KEY_NAME],
													$this->args[self::KEY_LAT], $this->args[self::KEY_LON],
													$this->args[self::KEY_DESC], $this->args[self::KEY_IS_PUBLIC]);

			if($value){
				return self::buildResponse(true);
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
		private static function switchUser($session, $toUser){
			if(AccountManager::isAdministrator($this->dbh, $session->getAccountId())){
				return $toUser;
			}
			else{
				throw InvalidArgumentException("Access denied: You don't have the permission to act as adminsitrator.");
			}
		}

		/**
		 * Performs a get places request which returns the places of an user
		 *
		 * Required parameters (in $this->args): limit, offset
		 * Optional parameter: filter
		 * @return Coordinate[] Array of coordinates with a maximum length of $limit
		 * @throws InvalidArgumentException If one of the required parameters is undefined
		 */
		protected function get(){
			$this->verifyOptionalParameters(array(
					self::KEY_LIMIT => "/^[0-9]{1,3}$/",
					self::KEY_OFFSET => "/^[0-9]+$/"
			));

			$this->assignOptionalParameter(self::KEY_LIMIT, 20);
			$this->assignOptionalParameter(self::KEY_OFFSET, 0);

			$session = $this->requireLogin();

			return CoordinateManager::getPlacesByAccountId(	$this->dbh, $session->getAccountId(), intval($this->args[self::KEY_LIMIT]),
															intval($this->args[self::KEY_OFFSET]), $this->getFilter());
		}

		/**
		 * Performs a get public places request which returns a part of the public places list
		 *
		 * Required parameters (in $this->args): limit, offset
		 * Optional parameter: filter
		 * @return Coordinate[] Array of coordinates with a maximum length of $limit
		 * @throws InvalidArgumentException If one of the required parameters is undefined
		 */
		protected function get_public(){
			$this->verifyOptionalParameters(array(
					self::KEY_LIMIT => "/^[0-9]{1,3}$/",
					self::KEY_OFFSET => "/^[0-9]+$/"
			));

			$this->assignOptionalParameter(self::KEY_LIMIT, 20);
			$this->assignOptionalParameter(self::KEY_OFFSET, 0);

			return CoordinateManager::getPublicPlaces($this->dbh, intval($this->args[self::KEY_LIMIT]), intval($this->args[self::KEY_OFFSET]), $this->getFilter());
		}

		/**
		 * Counts all places that are assigned to this account
		 * @throws MissingSessionException If the user is not signed in
		 * @return array ('status' => 'ok', 'count' => '?')
		 */
		protected function count(){
			$session = $this->requireLogin();
			return self::buildResponse(true, array("count" => CoordinateManager::countPlacesOfAccount($this->dbh, $session->getAccountId(), $this->getFilter())));
		}

		/**
		 * Counts all public places
		 * @return array ('status' => 'ok', 'count' => '?')
		 */
		protected function count_public(){
			return self::buildResponse(true, array("count" => CoordinateManager::countPublicPlaces($this->dbh, $this->getFilter())));
		}

		/**
		 * Performs a remove place request
		 *
		 * Required parameters (in $this->args): coord_id
		 *
		 * @return string[] String array with two elements: "status" and "msg"
		 * @throws InvalidArgumentException If $this->args["coord_id"] is undefined
		 */
		protected function remove(){
			$this->requireParameters(array(
					self::KEY_COORD_ID => "/^[0-9]+$/"
			));

			$this->verifyOptionalParameters(array(self::KEY_OTHER_ACCOUNT => "/^[0-9]+$/"));
			$this->assignOptionalParameter(self::KEY_OTHER_ACCOUNT, null);

			$session = $this->requireLogin();

			$accId;

			if($this->args[SELF::KEY_OTHER_ACCOUNT] != null){
				// The user wants to act as administrator
				$accId = $this->switchUser($session, $session->getAccountId());
			}
			else{
				$accId = $session->getAccountId();
				if(!CoordinateManager::isOwnerOfPlace($this->dbh, $accId, $this->args[self::KEY_COORD_ID])){
					throw new InvalidArgumentException("Access denied: You don't have the permission to remove this place.");
				}
			}

			$result = CoordinateManager::removePlace($this->dbh, $accId, $this->args[self::KEY_COORD_ID]);

			return self::buildResponse($result == 1);
		}

		/**
		 * Returns the current destination list of the user
		 * @return array
		 * @throws MissingSessionException If the user is not signed in
		 */
		protected function nav_get(){
			$session = $this->requireLogin();
			return CoordinateManager::getDestinationList($this->dbh, $session->getAccountId());
		}

		/**
		 * Performs a add destination request which appends an existing coordinate to your current navigation list
		 *
		 * Required parameters (in $this->args): coord_id
		 * @return string[] String array with two elements: "status" and "msg"
		 * @throws InvalidArgumentException If $this->args["coord_id"] is undefined
		 */
		protected function nav_add(){
			$this->requireParameters(array(
					self::KEY_COORD_ID => "/^[0-9]+$/"
			));

			$session = $this->requireLogin();

			$result = CoordinateManager::addCoordinateToDestinationList($this->dbh, $session->getAccountId(), $this->args[self::KEY_COORD_ID]);
			return self::buildResponse($result);
		}

		/**
		 * Performs a add destination request which appends a new coordinate to your current navigation list
		 *
		 * Required parameters (in $this->args): name, lat (latitude), lon (longitude)
		 * Optional parameter: desc
		 * @return string[] String array with three elements: "status", "msg" and "coord_id"
		 * @throws InvalidArgumentException If one of the required parameters is undefined
		 */
		protected function nav_create(){
			$this->requireParameters(array(
					self::KEY_NAME => "/^(.*){1,64}$/",
					self::KEY_LAT => self::COORD_REGEX,
					self::KEY_LON => self::COORD_REGEX
			));

			$this->verifyOptionalParameters(array(
					self::KEY_DESC => "/^(.*){1,256}$/"
			));

			$this->assignOptionalParameter(self::KEY_DESC, null);
			$this->assignOptionalParameter(self::KEY_IS_PUBLIC, 0);

			$session = $this->requireLogin();

			$coordId = CoordinateManager::createCoordinate($this->dbh, $this->args[self::KEY_NAME], $this->args[self::KEY_LAT], $this->args[self::KEY_LON],
														   $this->args[self::KEY_DESC]);

			if($coordId == -1){throw new Exception("Cannot create coordinate.");}

			$result = CoordinateManager::addCoordinateToDestinationList($this->dbh, $session->getAccountId(), $coordId);
			if($result){
				return self::buildResponse($result, array(self::KEY_COORD_ID => $coordId));
			}
			else{
				// Something went wrong - delete this coodinate
				CoordinateManager::tryToRemoveCooridate($this->dbh, $coordId);
				return self::buildResponse(false, array(msg => "An error occured. Please try again later."));
			}
		}

		/**
		 * Performs a remove destination request which removes a coordinate from your current navigation list
		 * Required parameters (in $this->args): coord_id
		 * @return string[] String array with a "status" element
		 * @throws InvalidArgumentException If $this->args["coord_id"] is undefined
		 */
		protected function nav_remove(){
			$this->requireParameters(array(
					self::KEY_COORD_ID => "/^[0-9]+$/"
			));

			$session = $this->requireLogin();

			$result = CoordinateManager::removeCoordinateFromDestinationList($this->dbh, $session->getAccountId(), $this->args[self::KEY_COORD_ID]);
			return self::buildResponse($result == 1);
		}

		/**
		 * Extracts the filter from the request parameters and appends wildcards at the beginning and the end of the string
		 * Warning: Do not execute this methode twice - otherwise the filter value will be html encoded twice
		 * @return string The filter with "%" as SQL wildcard: %FILTER%
		 */
		private function getFilter(){
			$this->verifyOptionalParameters(array(
					"filter" => "/^(.*){0,256}$/"
			));

			return (array_key_exists("filter", $this->args) ? ("%" . $this->args["filter"] . "%") : null);
		}

	}

	$placeHandler = new PlacesHTTPRequestHandler($_POST, DBTools::connectToDatabase());
	$placeHandler->handleRequest();
?>
