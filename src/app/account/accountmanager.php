<?php
	/**	GeoCat - Geocaching and -Tracking platform
		Copyright (C) 2015 Bastian Kraemer

		accountmanager.js

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

	class AccountManager {

		/**
		 * Checks if username and email address are valid and if this username is already in use.
		 *
		 * <u>Possible return values:</u><br />
		 * Return value > 0: Username and email address are valid<br />
		 * Return value < 0: Username or email address is invalid<br />
		 * <ul>
		 * <li>2 = OK: E-mail adress is already assigned to anther user</li>
		 * <li>1 = OK: Everything okay</li>
		 * <li>0 = Username is already in use</li>
		 * <li>-1 = Username or email address is invalid</li>
		 * <li>-2 = Username or email address is empty</li>
		 * </ul>
		 * @param PDO $dbh Database handler
		 * @param string $username The username
		 * @param string $email The users email address
		 * @return integer Value > 0 if the check was sucessfull, <= 0 if not
		 */
		public static function accountExists($dbh, $username, $email){
			if(empty($username) || empty($email)){return -2;}
			if(!self::isValidUsername($username) || !self::isValidEMailAddr($email)){return -1;}

			$result = DBTools::fetchAll($dbh, "SELECT account_id FROM Account WHERE username = :user", array(":user" => $username));
			$retval = false;
			if(empty($result)){
				$result = DBTools::fetchAll($dbh, "SELECT account_id FROM Account WHERE email = :email", array(":email" => $email));

				if(empty($result)){
					return 1;
				}
				else{
					return 2;
				}
			}
			else{
				return 0;
			}
		}

		private static function getOrDefault($key, $arr, $default){
			return array_key_exists($key, $arr) ? $arr[$key] : $default;
		}

		/**
		 * Creates a new account
		 * @param PDO $dbh Database handler
		 * @param string $username
		 * @param string $password
		 * @param string $email
		 * @param boolean $isAdmin
		 * @param array $details This array should contain information like "firstname" or "lastname"
		 * @return boolean <code>true</code> if the account has been created, <code>false</code> if not
		 */
		public static function createAccount($dbh, $username, $password, $email, $isAdmin, $details){
			// Verify parameters
			if(empty($password)){return false;}
			if(self::accountExists($dbh, $username, $email) <= 0){return false;}
			$lastname = self::getOrDefault("lastname", $details, null);
			$firstname = self::getOrDefault("firstname", $details, null);
			$publicemail = self::getOrDefault("public_email", $details, 0);

			if(strlen($lastname) > 63 || strlen($firstname) > 63 || !is_int($publicemail)){
				// To many characters in first name or last name or $publicemail is not an integer
				return false;
			}

			if($publicemail != 0 && $publicemail != 1){
				// $publicemail is not 0 or 1
				return false;
			}

			$hash = self::getPBKDF2Hash($password);
			$result = DBTools::query($dbh, "INSERT INTO Account (account_id, username, password, salt, email, is_administrator) VALUES (NULL, :user, :pw, :salt, :email, :admin)",
									 array("user" => $username, "pw" => $hash[0], "salt" => $hash[1], "email" => $email, "admin" => $isAdmin ? 1 : 0));

			if(!$result){
				error_log("Couldn't create new Account: Insert into 'Account' failed!\nDatabase returned '" . $result . "'");
				return false;
			}
			else{
				$accId = self::getAccountIdByUserName($dbh, $username);
				if($accId == -1){
					error_log("Couldn't create new Account: account_id is '-1' (unable to find recently created account).");
				}

				$result = DBTools::query($dbh, "INSERT INTO AccountInformation (account_id, lastname, firstname, show_email_addr) VALUES (:accid, :lastname, :firstname, :publicemail)",
										 array("accid" => $accId, "lastname" => $lastname, "firstname" => $firstname, "publicemail" => $publicemail));

				if(!$result){
					error_log("Couldn't create new Account: Insert into 'AccountInformation' failed!\nDatabase returned '" . $result . "'");
				}
				return $result ? true : false;
			}
		}

		/**
		 * Returns the accountid which is assigned to the username
		 * @param PDO $dbh Database handler
		 * @param string $username
		 * @return integer The accountid or '-1' if the username does not exist
		 */
		public static function getAccountIdByUserName($dbh, $username){
			$result = DBTools::fetchAll($dbh, "SELECT account_id FROM Account WHERE username = :user", array(":user" => $username));
			if(empty($result) || count($result) != 1){return -1;}
			return $result[0]["account_id"];
		}

		/**
		 * Check the password of a user
		 *
		 * <u>Possible return values:</u><br>
		 * <ul>
		 * <li>1 = Password is correct</li>
		 * <li>0 = Password is not correct</li>
		 * <li>-1 = Error: For example if the accountid does not exist</li>
		 * </ul>
		 * @param PDO $dbh Database handler
		 * @param integer $accountid The user's account id
		 * @param string $password Its password
		 * @return integer
		 */
		public static function checkPassword($dbh, $accountid, $password){
			$result = DBTools::fetchAll($dbh, "SELECT password, salt FROM Account WHERE account_id = :accid", array(":accid" => $accountid));
			if(empty($result) || count($result) != 1){return -1;}
			return (self::getPBKDF2Hash($password, base64_decode($result[0]["salt"]))[0] == $result[0]["password"] ? 1 : 0);
		}

		/**
		 * Calculates a PBKDF2 hash with the AES-256 algorithmn
		 * @param string $password
		 * @param string $salt (optional) If not defined a new salt will be generated
		 * @return array An array with two strings: [0] -> Hashed password, [1] -> Password salt (BASE64 encoded)
		 */
		public static function getPBKDF2Hash($password, $salt = null){
			if($salt == null){
				$salt = mcrypt_create_iv(8, MCRYPT_DEV_URANDOM);
			}
			return array(hash_pbkdf2("sha256", $password, $salt, 2048, 32), base64_encode($salt));
		}

		/**
		 * Checks if a username is valid.
		 * Conditions therfore are: Only A-Z, a-z, 0-9 and "_" as characters and a length less than 64.
		 * @param string $username
		 * @return boolean
		 */
		public static function isValidUsername($username){
			return preg_match("/^[A-Za-z0-9_]{2,63}$/", $username);
		}

		/**
		 * Checks if a email is valid. (An email adress has to be shorter than 64 characters)
		 * @param string $email
		 * @return boolean
		 */
		public static function isValidEMailAddr($email){
			return (filter_var($email, FILTER_VALIDATE_EMAIL) && strlen($email) < 64) ? 1 : 0;
		}
	}
?>
