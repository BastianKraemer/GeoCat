<?php

	/**
	 * File places.php
	 */

	require_once(__DIR__ . "/../app/CoordinateManager.php");
	require_once(__DIR__ . "/../app/session.php");
	require_once(__DIR__ . "/../app/jsonlocale.php");
	require_once(__DIR__ . "/../app/dbtools.php");
	require_once(__DIR__ . "/../app/DefaultRequestHandler.php");
	$config = require(__DIR__ . "/../config/config.php");

	try{
		$dbh = DBTools::connectToDatabase($config);
		$locale = JSONLocale::withBrowserLanguage($config);

		$session = new SessionController();
		$placeHandler = new AJAXPlaceHandler($dbh, $session, $locale);

		print($placeHandler->handleRequest($_POST));
	}
	catch(Exception $e){
		print("Invalid request format.");
	}

	class AJAXPlaceHandler {

		/**
		 * @var PDO Database handler
		 */
		private $dbh;

		/**
		 * @var SessionController Session handler
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

		public function __construct($databaseHandler, $session, $translations){
			$this->dbh = $databaseHandler;
			$this->session = $session;
			$this->locale = $translations;
		}

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
					$ret = $this->appendPlace($req->data);
				}
				else if($cmd == "get"){

					if(!$this->session->isSignedIn()){throw new MissingSessionException();}
					$ret = $this->getPlaces($req->data);
				}
				else if($cmd == "count"){

					if(!$this->session->isSignedIn()){throw new MissingSessionException();}
					$ret = array("count" => CoordinateManager::countPlacesOfAccount($this->dbh, $this->session->getAccountId()));
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

					$ret = array("count" => CoordinateManager::countPublicPlaces($this->dbh));
				}
				else{
					return "Unsupported command.";
				}

				return $req->prepareResponse($ret);
			}
			catch(InvalidArgumentException $e){
				return $req->prepareResponse(self::createDefaultResponse(false, "Invalid request: " . $e));
			}
			catch(MissingSessionException $e){
				return $req->prepareResponse(self::createDefaultResponse(false, "Access denied. Please sign in at first."));
			}
			catch(Exception $e){
				return $req->prepareResponse(array("status" => "error", "msg" => "Internal server error: " . $e->getMessage()));
			}
		}

		private static function createDefaultResponse($successful, $msg){
			return array("status" => $successful ? "ok" : "failed", "msg" => $msg);
		}

		private function appendPlace($data){
			if(!self::requireValues($data, array(self::KEY_NAME, self::KEY_LAT, self::KEY_LON, self::KEY_IS_PUBLIC))){
				throw new InvalidArgumentException("One or more required parameters are undefined.\n".
												   "Please define '" . self::KEY_NAME . "', '" . self::KEY_LAT . "', '" . self::KEY_LON . "' and '" . self::KEY_IS_PUBLIC . "'.");
			}

			$accId = $this->session->getAccountId();
			$coordId = CoordinateManager::createCoordinate($this->dbh, $data[self::KEY_NAME], floatval($data[self::KEY_LAT]), floatval($data[self::KEY_LON]), array_key_exists(self::KEY_DESC, $data) ? $data[self::KEY_DESC] : null);

			if($coordId == -1){
				throw new Exception("Cannot store coordinate.");
			}

			if(CoordinateManager::addPlace($this->dbh, $accId, $coordId, $data[self::KEY_IS_PUBLIC])){
				$ret = self::createDefaultResponse(true, "");
				$ret[self::KEY_COORD_ID] = $coordId;
				return $ret;
			}
			else{
				throw new Exception("Cannot append place.");
			}
		}

		private function updatePlace($data){
			if(!self::requireValues($data, array(self::KEY_NAME, self::KEY_LAT, self::KEY_LON, self::KEY_IS_PUBLIC, self::KEY_COORD_ID))){
				throw new InvalidArgumentException("One or more required parameters are undefined.\n".
												   "Please define '" . self::KEY_NAME . "', '" . self::KEY_LAT . "', '" . self::KEY_LON . "', '" . self::KEY_IS_PUBLIC . "' and '" . self::KEY_COORD_ID . "'.");
			}

			$accId = $this->session->getAccountId();

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

		private function getPlaces($data){
			if(self::requireValues($data, array(self::KEY_LIMIT, self::KEY_OFFSET))){
				$accId = $this->session->getAccountId();
				return CoordinateManager::getPlacesByAccountId($this->dbh, $accId, intval($data[self::KEY_LIMIT]), intval($data[self::KEY_OFFSET]));
			}
			else{
				throw new InvalidArgumentException("Required parameter '" . self::KEY_LIMIT . "' or '" . self::KEY_OFFSET . "' is undefined.");
			}
		}

		private function getPublicPlaces($data){
			if(self::requireValues($data, array(self::KEY_LIMIT, self::KEY_OFFSET))){
				return CoordinateManager::getPublicPlaces($this->dbh, intval($data[self::KEY_LIMIT]), intval($data[self::KEY_OFFSET]));
			}
			else{
				throw new InvalidArgumentException("Required parameter '" . self::KEY_LIMIT . "' or '" . self::KEY_OFFSET . "' is undefined.");
			}
		}

		private function removePlace($data){
			if(self::requireValues($data, array(self::KEY_COORD_ID))){
				$accId = $this->session->getAccountId();
				$result = CoordinateManager::removePlace($this->dbh, $accId, $data[self::KEY_COORD_ID]);

				return self::createDefaultResponse($result == 1, "");
			}
			else{
				throw new InvalidArgumentException("Required parameter '" . self::KEY_COORD_ID . "' is undefined.");
			}
		}

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