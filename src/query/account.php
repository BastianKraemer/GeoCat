<?php
	/*	GeoCat - Geocaching and -Tracking platform
	 Copyright (C) 2015 Bastian Kraemer

	 account.php

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

	/**
	 * File account.php
	 */

	$config = require(__DIR__ . "/../config/config.php");

	require_once(__DIR__ . "/../app/JSONLocale.php");
	require_once(__DIR__ . "/../app/DBTools.php");
	require_once(__DIR__ . "/../app/AccountManager.php");
	require_once(__DIR__ . "/../app/DefaultRequestHandler.php");
	require_once(__DIR__ . "/../app/SessionManager.php");

	try{
		$locale = JSONLocale::withBrowserLanguage($config);
		$dbh = DBTools::connectToDatabase($config);

		$accountHandler = new AJAXAccountHandler($dbh, $locale);
		header("Content-Type: text/json; charset=utf-8");

		print($accountHandler->handleRequest($_POST));
	}
	catch(Exception $e){
		print("Invalid request format.");
	}

	/**
	 * This class provides an interface to create new accounts.
	 * Therefore you have to send HTTP post requests to this file.
	 * A valid request could look like this:
	 *
	 * <code>account.php?cmd=check&tye=json&data={"username":"[username]","email":"[email]"}</code>
	 *
	 * Possible command values:
	 * <ul>
	 * <li><b>check</b>: Checks if a username is available.<br />
	 * Required parameters in <b>data</b>: <i>username</i>, <i>email</i></li>
	 * <li><b>create</b>: Creates an new account.<br />
	 * Required parameters in <b>data</b>: <i>username</i>, <i>password</i>, <i>email</i><br />
	 * Optional parameters in <b>data</b>: <i>firstname</i>, <i>lastname</i>, <i>public_email [default=false]</i></li>
	 * </ul>
	 *
	 * You can also test this class using cURL:
	 * <code>curl -s --data "cmd=check&username=[Username]&email=[email]" https://geocat.server/query/account.php</code>
	 */
	class AJAXAccountHandler {

		/**
		 * Database handler
		 * @var PDO
		 */
		private $dbh;

		/**
		 * The JSONLocale object is used to localize messages
		 * @var JSONLocale
		 */
		private $locale;

		/**
		 * Creates a new JSONAccount Manager object
		 * @param PDO $databaseHandler
		 * @param JSONLocale $translations
		 */
		public function __construct($databaseHandler, $translations){
			$this->locale = $translations;
			$this->dbh = $databaseHandler;
		}

		/**
		 * Handles a request
		 * @param string $cmd
		 * @param string $type
		 * @param array $data
		 */
		public function handleRequest($requestParameters){

			$req = new DefaultRequestHandler($requestParameters);

			try{
				if(!array_key_exists("cmd", $req->data)){
					throw new InvalidArgumentException("Parameter 'cmd' is not defined.");
				}

				$cmd = $req->data["cmd"];
				$ret;

				if(!array_key_exists("username", $req->data)){
					$ret = self::createDefaultResponse("false", "Error: Parameter 'username' is not defined." . $req->data["username"]);
				}
				else if(!array_key_exists("email", $req->data)){
					$ret = self::createDefaultResponse("false", "Error: Parameter 'email' is not defined.");
				}
				else if($cmd == "check"){
					$ret = self::checkAccountData($req->data["username"], $req->data["email"]);
				}
				else if($cmd == "create"){
					$ret = self::createAccount($req->data);
				}
				else{
					$ret = self::createDefaultResponse("false", "Error: Invalid command.");
				}

				return $req->prepareResponse($ret);
			}
			catch(InvalidArgumentException $e){
				return $req->prepareResponse(self::createDefaultResponse(false, "Invalid request: " . $e->getMessage()));
			}
			catch(Exception $e){
				return $req->prepareResponse(array("status" => "error", "msg" => "Internal server error: " . $e->getMessage()));
			}
		}

		/**
		 * Checks if an account already exists
		 * @param string $username
		 * @param string $email
		 */
		private function checkAccountData($username, $email){

			$value = AccountManager::accountExists($this->dbh, $username, $email);

			if($value == 1){
				return self::createDefaultResponse("true", "OK");
			}
			else if($value == 2){
				return self::createDefaultResponse("true", $this->locale->get("createaccount.notification.email_already_in_use"));
			}
			else if($value == 0){
				return self::createDefaultResponse("false", $this->locale->get("createaccount.denied.username_already_in_use"));
			}
			else{
				return self::createDefaultResponse("false", $this->locale->get("createaccount.invalid_user_or_email"));
			}
		}

		/**
		 * Creates a new account using the request data
		 * @param string $username
		 * @param string $password
		 * @param string $email
		 * @param boolean  $adminRights
		 * @param array $details
		 */
		private function createAccount($data){

			$username = $data["username"];
			$email = $data["email"];

			if(!array_key_exists("password", $data)){throw InvalidArgumentException("Parameter 'password' is not defined.");}
			$pw = $data["password"];

			$val = AccountManager::accountExists($this->dbh, $username, $email);
			if($val > 0){

				try{
					if($val == 1):
						$accId = AccountManager::createAccount($this->dbh, $username, $pw, $email, false, $data);
						$session = new SessionManager();
						$session->login($this->dbh, $accId, $pw);
					endif;
					return ($val == 1 ? self::createDefaultResponse("true", "OK") : self::createDefaultResponse("false", $this->locale->get("createaccount.notification.email_already_in_use")));
				}
				catch(InvalidArgumentException $e){
					return self::createDefaultResponse("false", $this->locale->get("createaccount.failed") . "\\n\\nDetails: " . $e->getMessage());
				}
			}
			else if($val == 0){
				return self::createDefaultResponse("false", $this->locale->get("createaccount.denied.username_already_in_use"));
			}
			else{
				return self::createDefaultResponse("false", $this->locale->get("createaccount.invalid_user_or_email"));
			}
		}

		/**
		 * Creates a JSON object which is returned to the sender
		 * @param string $result Value if the operation was successfull, should be "true" or "false"
		 * @param string $msg A message which is asigned to the request
		 * @return string A JSON object string that can be returned to the sender
		 */
		private static function createDefaultResponse($result, $msg){
			return array("result" => $result, "msg" => $msg);
		}
	}
?>
