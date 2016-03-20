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
	 * RESTful service for GeoCat to deal with accounts
	 * @package query
	 */

	require_once(__DIR__ . "/../app/RequestInterface.php");
	require_once(__DIR__ . "/../app/DBTools.php");
	require_once(__DIR__ . "/../app/JSONLocale.php");
	require_once(__DIR__ . "/../app/AccountManager.php");
	require_once(__DIR__ . "/../app/SessionManager.php");

	/**
	 * This class provides an interface to create new accounts.
	 * Therefore you have to send HTTP post requests to this file.
	 * A valid request could look like this:
	 *
	 * <code>account.php?task=check&username=[username]&email=[email]"}</code>
	 *
	 * Possible command values:
	 * <ul>
	 * <li><b>check</b>: Checks if a username is available.<br />
	 * Required parameters: <i>username</i>, <i>email</i></li>
	 * <li><b>create</b>: Creates an new account.<br />
	 * Required parameters: <i>username</i>, <i>password</i>, <i>email</i><br />
	 * </ul>
	 *
	 * You can also test this class using cURL:
	 * <code>curl -s --data "task=check&username=[Username]&email=[email]" https://geocat.server/query/account.php</code>
	 */
	class AccountHTTPRequestHandler extends RequestInterface {

		/**
		 * Database handler
		 * @var PDO
		 */
		private $dbh;

		/**
		 * Create an AccountHTTPRequestHandler instance
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
		 * This function can be used to check or create an GeoCat account.
		 *
		 * The behaviour of the method is defined by the 'justCheck' parameter.
		 * The data is taken from the HTTP request data
		 *
		 * @param boolean $justCheck
		 */
		private function checkOrCreateAccount($justCheck){
			$this->requireParameters(array(
					"username" => null,
					"email" => null
			));

			$val = AccountManager::accountExists($this->dbh, $this->args["username"], $this->args["email"]);

			if($val == AccountStatus::AccountDoesNotExist){
				if($justCheck){
					// Check
					return self::buildResponse(true);
				}
				else{
					// Create Account
					$this->requireParameters(array("password" => null));
					try{
						$accId = AccountManager::createAccount($this->dbh, $this->args["username"], $this->args["password"], $this->args["email"], false, $this->args);
						$session = new SessionManager();
						$session->login($this->dbh, $accId, $this->args["password"]);

						return self::buildResponse(true);
					}
					catch(InvalidArgumentException $e){
						return self::buildResponse(false, array("msg" => $this->locale->get("createaccount.failed") . "\\n\\nDetails: " . $e->getMessage()));
					}
				}
			}
			else if($val == AccountStatus::UsernameAlreadyInUse){
				return self::buildResponse(false, array("msg" => $this->locale->get("createaccount.denied.username_already_in_use")));
			}
			else if($val == AccountStatus::EMailAddressAlreadyInUse){
				return self::buildResponse(false, array("msg" => $this->locale->get("createaccount.denied.email_already_in_use")));
			}
			else{
				return self::buildResponse(false, array("msg" => $this->locale->get("createaccount.invalid_user_or_email")));
			}
		}

		/**
		 * Task: 'check'
		 *
		 * Checks if an account already exists
		 *
		 *  Required HTTP parameters for 'check':
		 * - <b>username</b>
		 * - <b>email</b>
		 */
		protected function check(){
			return $this->checkOrCreateAccount(true);
		}

		/**
		 * Task: 'create'
		 *
		 * Creates a new GeoCat account using the request data
		 *
		 *  Required HTTP parameters for 'check':
		 * - <b>username</b>
		 * - <b>email</b>
		 */
		protected function create(){
			$this->requireParameters(array(
					"password" => null
			));
			return $this->checkOrCreateAccount(false);
		}
	}

	$config = require(__DIR__ . "/../config/config.php");
	$accountHandler = new AccountHTTPRequestHandler($_POST, DBTools::connectToDatabase($config));
	header("Content-Type: application/json; charset=utf-8");
	$accountHandler->handleRequest();
?>
