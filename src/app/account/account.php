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

	$config = require("../../config/config.php");

	require_once "../jsonlocale.php";
	require_once("../dbtools.php");
	require_once("./accountmanager.php");

	$locale = JSONLocale::withBrowserLanguage($config);
	$dbh = DBTools::connectToDatabase($config);

	$jsonAccManager = new JSONAccountManager($dbh, $locale);
	header("Content-Type: text/json; charset=utf-8");
	if(array_key_exists("cmd", $_POST) && array_key_exists("type", $_POST) && array_key_exists("data", $_POST)){
		print($jsonAccManager->handleRequest($_POST["cmd"], $_POST["type"], $_POST["data"]));
	}
	else{
		print("Invalid request format.");
	}

	/**
	 * This class provides an interface to create new accounts.
	 * Therefore you have to send HTTP post requests to this file.
	 * A valid request has to look like this:
	 *
	 * <code>account.php?cmd=check&tye=json&data={"username":"[username]","email":"[email]"}</code>
	 *
	 * Possible command values:
	 * <ul>
	 * <li><b>check</b>: Checks if a username is available.<br />
	 * Required parameters in <b>data</b>: <i>username</i>, <i>email</i></li>
	 * <li><b>create</b>: Creates an new account.<br />
	 * Required parameters in <b>data</b>: <i>username</i>, <i>password</i>, <i>email</i>, <i>email</i><br />
	 * Optional parameters in <b>data</b>: <i>firstname</i>, <i>lastname</i>, <i>public_email [default=false]</i></li>
	 * </ul>
	 *
	 * You can also test this class using cURL:
	 * <code>curl -s --data "cmd=check&type=json&data={\"username\":\"[Username]\",\"email\":\"[email]\"}" https://geocat.server/app/account/account.php</code>
	 */
	class JSONAccountManager {

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
		public function handleRequest($cmd, $type, $data){

			$arr;
			if(strtolower($type) == "json"){
				$arr = (array) json_decode($data);
			}
			else{
				return self::createResponse("false", "Error: Invalid data type.");
			}

			if(!(array_key_exists("username", $arr) && array_key_exists("email", $arr))){
				return self::createResponse("false", "Error: Invalid parameters.");
			}

			if($cmd == "check"){
				return self::checkAccountData($arr["username"], $arr["email"]);
			}
			else if($cmd == "create"){
				if(!array_key_exists("password", $arr)){self::createResponse("false", "Error: Parameter 'Password' is not defined.");}
				return self::createAccount($arr["username"], $arr["password"], $arr["email"], false, $arr);
			}
			else{
				return self::createResponse("false", "Error: Invalid command.");
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
				return self::createResponse("true", "OK");
			}
			else if($value == 2){
				return self::createResponse("true", $this->locale->get("createaccount.notification.email_already_in_use"));
			}
			else if($value == 0){
				return self::createResponse("false", $this->locale->get("createaccount.denied.username_already_in_use"));
			}
			else{
				return self::createResponse("false", $this->locale->get("createaccount.invalid_user_or_email"));
			}
		}

		/**
		 * Creates a new account using the the request data
		 * @param string $username
		 * @param string $password
		 * @param string $email
		 * @param boolean  $adminRights
		 * @param array $details
		 */
		private function createAccount($username, $password, $email, $adminRights, $details){

			$val = AccountManager::accountExists($this->dbh, $username, $email);
			if($val > 0){
				$success = AccountManager::createAccount($this->dbh, $username, $password, $email, $adminRights, $details);

				if($success){
					return self::createResponse("true", $val == 1 ? "OK" : $this->locale->get("createaccount.notification.email_already_in_use"));
				}
				else{
					return self::createResponse("false", $this->locale->get("createaccount.failed"));
				}
			}
			else if($val == 0){
				return self::createResponse("false", $this->locale->get("createaccount.denied.username_already_in_use"));
			}
			else{
				return self::createResponse("false", $this->locale->get("createaccount.invalid_user_or_email"));
			}
		}

		/**
		 * Creates a JSON object which is returned to the sender
		 * @param string $result Value if the operation was successfull, should be "true" or "false"
		 * @param string $msg A message which is asigned to the request
		 * @return string A JSON object string that can be returned to the sender
		 */
		private static function createResponse($result, $msg){
			return json_encode(array("result" => $result, "msg" => $msg));
		}
	}
?>
