<?php
/* GeoCat - Geocaching and -tracking application
 * Copyright (C) 2016 Raphael Harzer, Bastian Kraemer
 *
 * SessionManager.php
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
 * PHP file for the GeoCat 'SessionManager'
 * @package app
 */

	/**
	 * This class is designed to handle all interaction with the PHP session
	 */
	class SessionManager {

		/**
		 * Start the session
		 * @throws Exception If the PHP Environment is unable to start the session
		 */
		public function __construct() {
			if(!session_start()){
				throw new Exception("Unable to start session.");
			}

			require_once(__DIR__ . "/AccountManager.php");
			require_once(__DIR__ . "/DBTools.php");
		}

		/**
		 * This function can be used to login a user.
		 * The login will be performed if the password is correct
		 * @param PDO $dbh Database handler
		 * @param integer $accountid The account id
		 * @param string $password The user's password
		 * @return boolean <code>true</code> if the user has been logged in, <code>false</code> if not
		 */
		public function login($dbh, $accountid, $password){
			if(AccountManager::checkPassword($dbh, $accountid, $password) == 1){
				$username = AccountManager::getUserNameByAccountId($dbh, $accountid);
				$this->performLogin($dbh, $accountid, $username);
				return true;
			}
			else{
				return false;
			}
		}

		/**
		 * Performs a login for a specific user
		 * @param PDO $dbh Database handler
		 * @param integer $accountid The account id
		 * @param string $username The username that belongs to this account id
		 */
		private function performLogin($dbh, $accountid, $username){
			$this->logout();
			$_SESSION["username"] = $username;
			$_SESSION["accountid"] = $accountid;
			$res = DBTools::query($dbh, "UPDATE AccountInformation SET last_login = CURRENT_TIMESTAMP WHERE account_id = :accid", array("accid" => $accountid));
			if(!$res){
				error_log("Error: Unable to update 'last_login' attribute of user '" . $username . " (Accountid: " . $accountid . ").");
			}
		}

		/**
		 * Returns <code>true</code> if the user is signed in, <code>false</code> if not
		 * @return boolean
		 */
		public function isSignedIn(){
			return array_key_exists("accountid", $_SESSION);
		}

		/**
		 * Returns the username if the user is signed in
		 * @return string The username or <code>-1</code> if the user is not signed in
		 */
		public function getUsername(){
			return array_key_exists("username", $_SESSION) ? $_SESSION["username"] : "";
		}

		/**
		 *  Returns the account id if the user is signed in
		 * @return integer The account id or "" if the user is not signed in
		 */
		public function getAccountId(){
			return array_key_exists("accountid", $_SESSION) ? $_SESSION["accountid"] : "-1";
		}

		/**
		 * Perform a logout
		 */
		public function logout(){
			unset($_SESSION["username"]);
			unset($_SESSION["accountid"]);
		}

		/**
		 * Prints out the current login status as JSON object
		 */
		public function printLoginStatusAsJSON(){
			print "{isSignedIn: " . ($this->isSignedIn() ? "true" : "false") . ", username: \"" . $this->getUsername() . "\"}";
		}

		/**
		 * Create new cookie with json encoded content
		 * @param string $name			name of cookie
		 * @param string $data			content of cookie
		 * @param boolean $jsonencode	(Optional)
		 * @param int $expire			(Optional) lifetime of cookie, default: expires at end of session
		 * @param string $path			(Optional) available domain-level (and below), default: entire domain
		 */
		public function createCookie($name, $data, $jsonencode = true, $expire = 0, $path = "/"){
			return setcookie($name, ($jsonencode ? json_encode($data) : $data), ($expire > 0 ? time()+$expire : $expire), $path);
		}

		/**
		 * Create a new login token
		 * @param PDO $dbh
		 * @param boolean $setcookie
		 * @throws InvalidArgumentException If the user is not signed in
		 */
		public function createLoginToken($dbh, $setcookie = true){
			if(!self::isSignedIn()){
				throw new InvalidArgumentException('an error occured while creating login-token');
			}
			while(true){
				$accId = self::getAccountId();
				$token = base64_encode(mcrypt_create_iv(30, MCRYPT_DEV_URANDOM));
				// check if new token already exists
				$res = DBTools::fetch($dbh, "SELECT count(*) " .
									  "FROM LoginToken " .
									  "WHERE token = :token ", array("token" => $token), PDO::FETCH_NUM);
				if($res[0] > 0){ continue; }
				// check if user already has a login-token
				unset($res);
				$res = DBTools::fetch($dbh,	"SELECT count(*) " .
									  "FROM LoginToken " .
									  "WHERE account_id = :accid", array("accid" => $accId), PDO::FETCH_NUM);
				if($res[0] > 0){
					DBTools::query($dbh, "DELETE FROM LoginToken WHERE account_id = :accid;", array("accid" => $accId));
				}
				DBTools::query($dbh, "INSERT INTO LoginToken (account_id, token) VALUES (:accid, :token); ", array("accid" => $accId, "token" => $token));
				break;
			}
			if($setcookie){
				// expires in 30 days
				self::createCookie("GEOCAT_LOGIN", $token, false, (30 * 24 * 60 * 60));
			}
		}

		/**
		 * Verifies the users login token
		 * @param PDO $dbh Database handler
		 * @param string $data The users login token
		 */
		public function verifyCookie($dbh, $data){
			$decodedCookie = urldecode(str_replace("%22", "", $data));
			$res = DBTools::fetchAssoc($dbh, "SELECT * FROM LoginToken WHERE token = :token", array("token" => $decodedCookie));
			if($res > 0) {
				$username = AccountManager::getUserNameByAccountId($dbh, $res['account_id']);
				self::performLogin($dbh, $res['account_id'], $username);
			}
			if(self::isSignedIn()){
				return true;
			}
			return false;
		}

		/**
		 * Delete a login token from the database
		 * @param PDO $dbh Database handler
		 */
		public function deleteCookie($dbh){
			if(self::isSignedIn()){
				$accId = self::getAccountId();
				DBTools::query($dbh, "DELETE FROM LoginToken WHERE account_id = :accid;", array("accid" => $accId));
			}
		}

	}

	/**
	 * This exception can be thrown if there is no active session and the user has to be signed in to use this feature
	 */
	class MissingSessionException extends Exception
	{
		/**
		 * Create a new MissingSessionException object
		 * @param string $message (optional) Exception message
		 * @param number $code
		 * @param Exception $previous
		 */
		public function __construct($message = "A login is required to use this feature", $code = 0, Exception $previous = null) {
			parent::__construct($message, $code, $previous);
		}

		/**
		 * Returns a string which contains the most information of this exception
		 * @see Exception::__toString()
		 */
		public function __toString() {
			return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
		}
	}
?>
