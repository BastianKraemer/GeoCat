<?php
	/**
	 * File session.php
	 */

	/**
	 * This class is designed to handle all interaction with the PHP session
	 */
	class SessionController {

		/**
		 * Start the session
		 * @throws Exception If the PHP Environment is unable to start the session
		 */
		public function __construct() {
			if(!session_start()){
				throw new Exception("Unable to start session.");
			}

			require_once("./account/accountmanager.php");
			require_once("./dbtools.php");
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
			$res = DBTools::query($dbh, "UPDATE AccountInformation SET last_login = CURRENT_TIMESTAMP WHERE account_id = :accid", array("accid" => $accountid));#
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
	}
?>
