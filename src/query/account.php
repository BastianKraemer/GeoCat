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
	 * Therefore you have to send HTTP post requests to '/query/account.php'.
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

		/**
		 * Task: 'getUserData'
		 *
		 * Returns your current account details
		 *
		 * You have to be signed in to use this service
		 */
		protected function getUserData(){
			$session = new SessionManager();
			if($session->isSignedIn()){
				$accId = $session->getAccountId();
				$username = AccountManager::getUserNameByAccountId($this->dbh, $accId);
				$email = AccountManager::getEmailAdressByAccountId($this->dbh, $accId);
				$fullname = AccountManager::getRealNameByAccountId($this->dbh, $accId);
				$lname = $fullname['lastname'];
				$fname = $fullname['firstname'];
				return self::buildResponse(true, array("username" => $username, "email" => $email, "lname" => $lname, "fname" => $fname));
			} else {
				return self::buildResponse(false, array("msg" => $this->locale->get("account.err.pleasesignin")));
			}
		}

		/**
		 * Task: 'updateUserData'
		 *
		 * Creates a new GeoCat account using the request data
		 *
		 * You have to be signed in to use this service
		 *
		 *  Required HTTP parameters for 'updateUserData':
		 * - <b>id</b>: This can be "acc-email", "acc-username", "acc-firstname" or "acc-lastname"
		 * - <b>text</b> : The value for the selected account detail
		 */
		protected function updateUserData(){
			$newVal = $this->args['text'];
			$oldVal = "";
			$type = $this->args['id'];
			$session = new SessionManager();
			$response = false;
			$message = "";
			switch($type){
				case "acc-email":
					$oldVal = AccountManager::getEmailAdressByAccountId($this->dbh, $session->getAccountId());
					if($oldVal == $newVal){
						$message = $this->locale->get("account.update.nochange");
						break;
					}
					if(AccountManager::isValidEMailAddr($newVal)){
						if(!AccountManager::isEMailAddressAlreadyInUse($this->dbh, $newVal)){
							if(AccountManager::setNewEmailAdressForAccountId($this->dbh, $session->getAccountId(), $newVal)){
								$response = true;
								$message = $this->locale->get("account.update.success");
								break;
							}
							$message = $this->locale->get("account.update.error");
							break;
						}
						$message = $this->locale->get("account.update.emailinuse");
						break;
					}
					$message = $this->locale->get("account.update.invalidemail");
					break;
				case "acc-username":
					$oldVal = AccountManager::getUserNameByAccountId($this->dbh, $session->getAccountId());
					if($oldVal == $newVal){
						$message = $this->locale->get("account.update.nochange");
						break;
					}
					if(AccountManager::isValidUsername($newVal)){
						if(!AccountManager::isUsernameInUse($this->dbh, $newVal)){
							if(AccountManager::setNewUsernameForAccountId($this->dbh, $session->getAccountId(), $newVal)){
								$_SESSION["username"] = $newVal;
								$response = true;
								$message = $this->locale->get("account.update.success");
								break;
							}
							$message = $this->locale->get("account.update.error");
							break;
						}
						$message = $this->locale->get("account.update.usernameinuse");
						break;
					}
					$message = $this->locale->get("account.update.invalidusername");
					break;
				case "acc-firstname":
					$oldVal = AccountManager::getRealNameByAccountId($this->dbh, $session->getAccountId())['firstname'];
					if($oldVal == $newVal){
						$message = $this->locale->get("account.update.nochange");
						break;
					}
					if(AccountManager::isValidRealName($newVal)){
						if(AccountManager::setRealNameByAccountId($this->dbh, $session->getAccountId(), $newVal, "firstname")){
							$response = true;
							$message = $this->locale->get("account.update.success");
							break;
						}
						$message = $this->locale->get("account.update.error");
						break;
					}
					$message = $this->locale->get("account.update.invalidrealname");
					break;
				case "acc-lastname":
					$oldVal = AccountManager::getRealNameByAccountId($this->dbh, $session->getAccountId())['lastname'];
					if($oldVal == $newVal){
						$message = $this->locale->get("account.update.nochange");
						break;
					}
					if(AccountManager::isValidRealName($newVal)){
						if(AccountManager::setRealNameByAccountId($this->dbh, $session->getAccountId(), $newVal, "lastname")){
							$response = true;
							$message = $this->locale->get("account.update.success");
							break;
						}
						$message = $this->locale->get("account.update.error");
						break;
					}
					$message = $this->locale->get("account.update.invalidrealname");
					break;
				default:
					// should never happen
					return self::buildResponse(false, array("msg" => "data-id does not match with predefined types"));
			}
			return self::buildResponse($response, array("msg" => $message));
		}

		/**
		 * Task: 'changePassword'
		 *
		 * Update the password of your GeoCat account
		 *
		 * You have to be signed in to use this service
		 *
		 *  Required HTTP parameters for 'updateUserData':
		 * - <b>oldpw</b>: Old password
		 * - <b>newpw</b>: New password
		 */
		protected function changePassword(){
			$session = new SessionManager();
			$oldPassword = $this->args['oldpw'];
			$newPassword = trim($this->args['newpw']);
			if(strlen($newPassword) > 0){
				if(AccountManager::checkPassword($this->dbh, $session->getAccountId(), $oldPassword) == 1){
					if(AccountManager::setNewPassword($this->dbh, $session->getAccountId(), $newPassword)){
						return self::buildResponse(true, array("msg" => $this->locale->get("account.update.success")));
					}
					return self::buildResponse(false, array("msg" => $this->locale->get("account.update.error")));
				}
				return self::buildResponse(false, array("msg" => $this->locale->get("account.update.wrongpassword")));
			}
			return self::buildResponse(false, array("msg" => $this->locale->get("account.update.pwdempty")));
		}

	}

	$accountHandler = new AccountHTTPRequestHandler($_POST, DBTools::connectToDatabase());
	header("Content-Type: application/json; charset=utf-8");
	$accountHandler->handleRequest();
?>
