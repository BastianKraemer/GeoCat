<?php
/* GeoCat - Geocaching and -tracking application
 * Copyright (C) 2015-2016 Raphael Harzer, Bastian Kraemer
 *
 * AccountManager.php
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
 * File AccountManager.php
 * @package app
 */

	require_once(__DIR__ . "/DBTools.php");

	/**
	 * This class can be used to deal with accounts
	 */
	class AccountManager {

		/**
		 * Name of the "Account" table
		 * @var string
		 */
		const TABLE_ACCOUNT = "Account";

		/**
		 * Name of the "Account" table
		 * @var string
		 */
		const TABLE_ACCOUNTINFO = "AccountInformation";

		/**
		 * Checks if username and email address are valid and if this username is already in use.
		 * @param PDO $dbh Database handler
		 * @param string $username The username
		 * @param string $email The users email address
		 * @return AccountStatus
		 */
		public static function accountExists($dbh, $username, $email){
			if(empty($username) || !self::isValidUsername($username)){return AccountStatus::InvalidUsername;}
			if(empty($email) || !self::isValidEMailAddr($email)){return AccountStatus::InvalidEMailAddress;}

			$result = DBTools::fetchAll($dbh, "SELECT account_id FROM " . self::TABLE_ACCOUNT . " WHERE username = :user", array(":user" => $username));
			$retval = false;
			if(empty($result)){
				if(self::isEMailAddressAlreadyInUse($dbh, $email)){
					return AccountStatus::EMailAddressAlreadyInUse;
				}
				else{
					return AccountStatus::AccountDoesNotExist;
				}
			}
			else{
				return AccountStatus::UsernameAlreadyInUse;
			}
		}

		/**
		 * Check is an email address is already used by another account
		 * @param PDO $dbh databse handler
		 * @param string $email the email address
		 * @return boolean
		 */
		public static function isEMailAddressAlreadyInUse($dbh, $email){
			$result = DBTools::fetchNum($dbh, "SELECT account_id FROM " . self::TABLE_ACCOUNT . " WHERE email = :email", array(":email" => $email));
			return !(empty($result));
		}

		/**
		 * Returns the value of an array or a default value
		 * @param string $key
		 * @param array $arr
		 * @param mixed $default
		 */
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
		 * @return integer The account_id of the new account
		 * @throws InvalidArgumentException
		 * @throws Exception
		 */
		public static function createAccount($dbh, $username, $password, $email, $isAdmin, $details){
			// Verify parameters
			if(empty($password)){throw new InvalidArgumentException("Password is empty");}

			$accountCheck = self::accountExists($dbh, $username, $email);
			if($accountCheck == AccountStatus::UsernameAlreadyInUse){
				throw new InvalidArgumentException("An account with this username already exists.");
			}

			if($accountCheck == AccountStatus::EMailAddressAlreadyInUse){
				throw new InvalidArgumentException("An account with this email address already exists.");
			}

			if($accountCheck == AccountStatus::InvalidUsername || $accountCheck == AccountStatus::InvalidEMailAddress){
				throw new InvalidArgumentException("E-mail address or username are invalid.");
			}

			$lastname = self::getOrDefault("lastname", $details, null);
			$firstname = self::getOrDefault("firstname", $details, null);
			$publicemail = self::getOrDefault("public_email", $details, 0);

			if(!self::isValidRealName($lastname) || !self::isValidRealName($firstname)){
				// Invalid first name or last name or $publicemail is not an integer
				throw new InvalidArgumentException("The values for 'first name' or 'last name' are invalid.");
			}

			if(!is_int($publicemail)){$publicemail = intval($publicemail);}

			if($publicemail != 0 && $publicemail != 1){
				// $publicemail is not 0 or 1
				throw new InvalidArgumentException("Invalid value for 'public_email'.");
			}

			$hash = self::getPBKDF2Hash($password);
			$result = DBTools::query($dbh, "INSERT INTO " . self::TABLE_ACCOUNT . " (account_id, username, password, salt, email, is_administrator) VALUES (DEFAULT, :user, :pw, :salt, :email, :admin)",
									 array("user" => $username, "pw" => $hash[0], "salt" => $hash[1], "email" => $email, "admin" => $isAdmin ? 1 : 0));

			if(!$result){
				error_log("Couldn't create new account: Insert into '" . self::TABLE_ACCOUNT . "' failed!\nDatabase returned '" . $result . "'");
				throw new Exception("Unable to access database.");
			}
			else{
				$accId = self::getAccountIdByUserName($dbh, $username);
				if($accId == -1){
					error_log("Unable to create new account: account_id is '-1' (unable to find recently created account).");
					throw new Exception("Account verification failed.");
				}

				$result = DBTools::query($dbh, "INSERT INTO " . self::TABLE_ACCOUNTINFO . "  (account_id, lastname, firstname, show_email_addr) VALUES (:accid, :lastname, :firstname, :publicemail)",
										 array("accid" => $accId, "lastname" => $lastname, "firstname" => $firstname, "publicemail" => $publicemail));

				if($result){
					return $accId;
				}
				else{
					error_log("Unable to create new account: Insert into '" . self::TABLE_ACCOUNTINFO . " ' failed!\nDatabase returned '" . $result . "'");
					throw new Exception("Unable to access database.");
				}
			}
		}

		/**
		 * Delte a GeoCat account
		 * @param PDO $dbh Database handler
		 * @param integer $accountId
		 */
		public static function deleteAccount($dbh, $accountId){
			require_once(__DIR__ . "/CoordinateManager.php");
			require_once(__DIR__ . "/challenge/TeamManager.php");
			require_once(__DIR__ . "/challenge/ChallengeManager.php");

			$place_coordId = $currnav_coordId = $accinfo_coordId = $challcoord_coordId = array();
			$place_coordId = DBTools::fetchAll($dbh, "SELECT coord_id FROM Place WHERE account_id = :accid", array(":accid" => $accountId), PDO::FETCH_ASSOC);
			DBTools::query($dbh, "DELETE FROM Place WHERE account_id = :accid", array(":accid" => $accountId));
			DBTools::query($dbh, "DELETE FROM LoginToken WHERE account_id = :accid", array(":accid" => $accountId));
			DBTools::query($dbh, "DELETE FROM Friends WHERE account_id = :accid OR friend_id = :accid", array(":accid" => $accountId));
			$currnav_coordId = DBTools::fetchAll($dbh, "SELECT coord_id FROM CurrentNavigation WHERE account_id = :accid", array(":accid" => $accountId), PDO::FETCH_ASSOC);
			DBTools::query($dbh, "DELETE FROM CurrentNavigation WHERE account_id = :accid", array(":accid" => $accountId));

			foreach(ChallengeManager::getChallengesOfUser($dbh, $accountId) as $userChallengeId){
				$userTeamId = TeamManager::getTeamOfUser($dbh, $userChallengeId, $accountId);
				TeamManager::leaveTeam($dbh, $userTeamId, $accountId);
			}

			$accinfo_coordId = DBTools::fetchAll($dbh, "SELECT my_position FROM AccountInformation WHERE account_id = :accid", array(":accid" => $accountId), PDO::FETCH_ASSOC);

			DBTools::query($dbh, "DELETE FROM AccountInformation WHERE account_id = :accid", array(":accid" => $accountId));
			$challengeId = DBTools::fetchAll($dbh, "SELECT challenge_id FROM Challenge WHERE owner = :accid", array(":accid" => $accountId), PDO::FETCH_ASSOC);
			if(!empty($challengeId)){
				foreach ($challengeId as $index => $array) {
					foreach ($array as $key => $challenge_id) {
						DBTools::query($dbh, "DELETE FROM ChallengeStats WHERE challenge_id = $challenge_id");
						$challengeCoordId = DBTools::fetchAll($dbh, "SELECT challenge_coord_id FROM ChallengeCoord WHERE challenge_id = $challenge_id", null, PDO::FETCH_ASSOC);
						if(!empty($challengeCoordId)){
							foreach ($challengeCoordId as $index => $array) {
								foreach ($array as $key => $challenge_coord_id) {
									DBTools::query($dbh, "DELETE FROM ChallengeCheckpoint WHERE challenge_coord_id = $challenge_coord_id");
								}
							}
						}
						$challcoord_coordId = DBTools::fetchAll($dbh, "SELECT coord_id FROM ChallengeCoord WHERE challenge_id = $challenge_id", null, PDO::FETCH_ASSOC);
						DBTools::query($dbh, "DELETE FROM ChallengeCoord WHERE challenge_id = $challenge_id");
						DBTools::query($dbh, "DELETE FROM ChallengeTeam WHERE challenge_id = $challenge_id");
						DBTools::query($dbh, "DELETE FROM Challenge WHERE challenge_id = $challenge_id");
					}
				}
			}
			$coordId = array_merge(
				array_values((array) $place_coordId),
				array_values((array) $currnav_coordId),
				array_values((array) $accinfo_coordId),
				array_values((array) $challcoord_coordId)
			);
			if(!empty($coordId)){
				foreach ($coordId as $index => $array) {
					foreach ($array as $key => $coord) {
						if($coord != null){
							CoordinateManager::tryToRemoveCooridate($dbh, $coord);
						}
					}
				}
			}
			DBTools::query($dbh, "DELETE FROM Account WHERE account_id = :accid", array(":accid" => $accountId));
		}

		/**
		 * Returns the account id which is assigned to the username
		 * @param PDO $dbh Database handler
		 * @param string $username
		 * @return integer The account id or '-1' if the username does not exist
		 */
		public static function getAccountIdByUserName($dbh, $username){
			$result = DBTools::fetchAll($dbh, "SELECT account_id FROM " . self::TABLE_ACCOUNT . " WHERE username = :user", array(":user" => $username));
			if(empty($result) || count($result) != 1){return -1;}
			return $result[0]["account_id"];
		}

		/**
		 * Returns the account id which is assigned to the email address
		 * @param PDO $dbh Database handler
		 * @param string $email
		 * @return integer The account id or '-1' if the email address is not known
		 */
		public static function getAccountIdByEmailAddress($dbh, $email){
			$result = DBTools::fetchNum($dbh, "SELECT account_id FROM " . self::TABLE_ACCOUNT . " WHERE email = :email", array(":email" => $email));
			if(empty($result)){return -1;}
			return $result[0];
		}

		/**
		 * Returns the username that is assigned to the account id
		 * @param PDO $dbh Database handler
		 * @param string $accountId
		 * @return string The username
		 * @throws InvalidArgumentException If the account id is undefined
		 */
		public static function getUserNameByAccountId($dbh, $accountId){
			$result = DBTools::fetchNum($dbh, "SELECT username FROM Account WHERE account_id = :accid", array(":accid" => $accountId));
			if(empty($result)){throw InvalidArgumentException("Undefined account id.");}
			return $result[0];
		}

		/**
		 * Checks if a username is already in use
		 * @param PDO $dbh Database handler
		 * @param string $username The username
		 * @boolean
		 */
		public static function isUsernameInUse($dbh, $username){
			$result = DBTools::fetchAll($dbh, "SELECT account_id FROM " . self::TABLE_ACCOUNT . " WHERE username = :user", array(":user" => $username));
			if(empty($result)){
				return false;
			}
			return true;
		}

		/**
		 * Set new username for an account
		 * @param PDO $dbh Database handler
		 * @param integer $accountId The account id
		 * @param string $username The account username
		 */
		public static function setNewUsernameForAccountId($dbh, $accountId, $username){
			$result = DBTools::query($dbh, "UPDATE Account SET username = :username WHERE account_id = :accid", array(":username" => $username, ":accid" => $accountId));
			return $result;
		}

		/**
		 * Returns the email adress of an user
		 * @param PDO $dbh Database handler
		 * @param integer $accountId The account id
		 * @return string The email-adress of a user
		 */
		public static function getEmailAdressByAccountId($dbh, $accountId){
			$result = DBTools::fetchAll($dbh, "SELECT email FROM Account WHERE account_id = :accid", array(":accid" => $accountId));
			if(empty($result) || count($result) != 1){throw InvalidArgumentException("Undefined account id.");}
			return $result[0]['email'];
		}

		/**
		 * Sets a new email adress for an account
		 * @param PDO $dbh Database handler
		 * @param integer $accountId the account id
		 * @param string $email he new email address
		 * @return integer The result of the SQL query
		 */
		public static function setNewEmailAdressForAccountId($dbh, $accountId, $email){
			$result = DBTools::query($dbh, "UPDATE Account SET email = :email WHERE account_id = :accid", array(":email" => $email, ":accid" => $accountId));
			return $result;
		}

		/**
		 * Returns the 'real' name of an user
		 * @param PDO $dbh Database handler
		 * @param integer $accountId
		 * @return string the real name
		 */
		public static function getRealNameByAccountId($dbh, $accountId){
			$result = DBTools::fetchAll($dbh, "SELECT lastname, firstname FROM AccountInformation WHERE account_id = :accid", array(":accid" => $accountId));
			if(empty($result) || count($result) != 1){throw InvalidArgumentException("Undefined account id.");}
			return $result[0];
		}

		/**
		 * Set a new 'real' name of an user
		 * @param PDO $dbh Database handler
		 * @param integer $accountId
		 * @param string $newVal Value for the database row
		 * @param string $column Name of the database column
		 */
		public static function setRealNameByAccountId($dbh, $accountId, $newVal, $column){
			$result = DBTools::query($dbh, "UPDATE AccountInformation SET $column = :newval WHERE account_id = :accid", array(":newval" => $newVal, ":accid" => $accountId));
			return $result;
		}

		/**
		 * Add buddy to account
		 * @param PDO $dbh Database handler
		 * @param string $myAccId
		 * @param string $buddyAccId
		 */
		public static function addBuddyToAccount($dbh, $myAccId, $buddyAccId){
			DBTools::query($dbh, "INSERT INTO Friends (account_id, friend_id) " .
				"VALUES (:myaccid, :buddyaccid);",
				array(":myaccid" => $myAccId, ":buddyaccid" => $buddyAccId));
		}

		/**
		 * Remove buddy from account
		 * @param PDO $dbh Database handler
		 * @param string $myAccId
		 * @param string $buddyAccId
		 */
		public static function removeBuddyFromAccount($dbh, $myAccId, $buddyAccId){
			DBTools::query($dbh, "DELETE FROM Friends " .
				"WHERE account_id = :myaccid AND friend_id = :buddyaccid",
				array(":myaccid" => $myAccId, ":buddyaccid" => $buddyAccId));
		}

		/**
		 * Get buddy list from account
		 * @param PDO $dbh Database handler
		 * @param string $myAccId
		 */
		public static function getBuddyList($dbh, $myAccId){
			$result = DBTools::fetchAll($dbh,	"SELECT Friends.friend_id, Account.username, AccountInformation.firstname, AccountInformation.lastname, " .
													"AccountInformation.my_position_timestamp AS pos_timestamp " .
												"FROM Friends, Account, AccountInformation " .
												"WHERE Friends.account_id = :myaccid AND Account.account_id = Friends.friend_id AND AccountInformation.account_id = Friends.friend_id",
												array(":myaccid" => $myAccId), PDO::FETCH_ASSOC);
			return $result;
		}

		/**
		 * Returns a list of accounts which added a specific account as buddy
		 * @param PDO $dbh Database handler
		 * @param integer $myAccId
		 * @return array
		 */
		public static function getAccountsWhichAddedMeAsFriend($dbh, $myAccId){
			$res = DBTools::fetchAll($dbh,	"SELECT Friends.account_id, Account.username, AccountInformation.firstname, AccountInformation.lastname, " .
											"AccountInformation.my_position_timestamp AS pos_timestamp " .
											"FROM Friends, Account, AccountInformation " .
											"WHERE Friends.friend_id = :accId AND Account.account_id = Friends.account_id AND AccountInformation.account_id = Friends.account_id",
										array("accId" => $myAccId), PDO::FETCH_ASSOC);
			return $res;
		}

		/**
		 * Checks if a user added another user as buddy
		 * @param PDO $dbh Database handler
		 * @param integer $buddy The account id of the buddy
		 * @param integer $ofAccount the account id od the user who has '$buddy' as buddy
		 * @return boolean
		 */
		public static function isFriendOf($dbh, $buddy, $ofAccount){
			$result = DBTools::fetchAll($dbh, "SELECT Friends.friend_id FROM Friends WHERE Friends.account_id = :accId AND Friends.friend_id = :buddyId",
										array("buddyId" => $buddy, "accId" => $ofAccount), PDO::FETCH_ASSOC);
			return !empty($result);
		}

		/**
		 * Returns detailed information of the buddies of an account and possible friend requests
		 * @param PDO $dbh Database handler
		 * @param integer $myAccId the account id
		 * @return array
		 */
		public static function getBuddyInformation($dbh, $myAccId){
			$list = self::getBuddyList($dbh, $myAccId);
			$others = self::getAccountsWhichAddedMeAsFriend($dbh, $myAccId);

			$buddyIdList = array();

			for($i = 0; $i < count($list); $i++){
				$list[$i]["confirmed"] = self::isFriendOf($dbh, $myAccId, $list[$i]["friend_id"]) ? "yes" : "no";
				$buddyIdList[] = $list[$i]["friend_id"];
			}

			$buddyReq = array();

			for($i = 0; $i < count($others); $i++){
				if(!in_array($others[$i]["account_id"], $buddyIdList)){
					$buddyReq[] = $others[$i];
				}
			}

			return array("buddies" => $list, "requests" => $buddyReq);
		}

		/**
		 * Links the position of a user with a coordinate
		 * @param PDO $dbh Database handler
		 * @param integer $myAccId The account id
		 * @param integer $coordId The coordinate id
		 */
		public static function updateMyPosition($dbh, $myAccId, $coordId){
			DBTools::query($dbh, "UPDATE AccountInformation SET my_position = :coordid WHERE account_id = :accid", array(":coordid" => $coordId, ":accid" => $myAccId));
		}

		/**
		 * Updates the timestamp of a user position
		 * @see self::updateMyPosition()
		 * @param PDO $dbh Database handler
		 * @param intger $myAccId The account id
		 */
		public static function updateTimestamp($dbh, $myAccId){
			DBTools::query($dbh, "UPDATE AccountInformation SET my_position_timestamp = CURRENT_TIMESTAMP WHERE account_id = :accid", array(":accid" => $myAccId));
		}

		/**
		 * Returns the coordinate id that has been assigned to an account
		 * @param PDO $dbh Database handler
		 * @param integer $myAccId The account id
		 */
		public static function getMyPosition($dbh, $myAccId){
			$result = DBTools::fetchAll($dbh,
																	"SELECT AccountInformation.my_position " .
																	"FROM AccountInformation " .
																	"WHERE account_id = :accid",
																	array(":accid" => $myAccId), PDO::FETCH_ASSOC);
			if(empty($result) || count($result) > 1){ return -1; } else { return $result[0]['my_position']; }
		}

		/**
		 * Removes the position information from an account
		 * @param PDO $dbh Database handler
		 * @param integer $accountId Zhe account id
		 * @return boolean <code>true</code> if the position has been cleared, <code>false</code> if no position has been assigned to the accoount
		 */
		public static function clearPosition($dbh, $accountId){

			$coordId = self::getMyPosition($dbh, $accountId);
			if($coordId > 0){
				CoordinateManager::tryToRemoveCooridate($dbh, $coordId);
			}

			$result = DBTools::query($dbh,	"UPDATE AccountInformation SET my_position = NULL, my_position_timestamp = NULL " .
											"WHERE account_id = :accid",
											array(":accid" => $accountId));

			return ($coordId > 0);
		}

		/**
		 * Finds a buddy
		 * @param PDO $dbh Database handler
		 * @param string $searchtext
		 * @return array The matched serach results
		 */
		public static function find_buddy($dbh, $searchtext){
			// Note: dbType is a workaround:
			require_once(__DIR__ . "/GeoCat.php");
			$likeStm = (GeoCat::getConfigKey("database.type") == "pgsql" ? "ILIKE" : "LIKE");

			$result = DBTools::fetchAll($dbh,
																	"SELECT Account.username, AccountInformation.firstname, AccountInformation.lastname " .
																	"FROM Account JOIN AccountInformation ON (Account.account_id = AccountInformation.account_id) " .
																	"WHERE Account.username ". $likeStm . " :text " .
																	"OR AccountInformation.firstname ". $likeStm . " :text " .
																	"OR AccountInformation.lastname ". $likeStm . " :text ",
																	array(":text" => $searchtext), PDO::FETCH_ASSOC);
			return $result;
		}

		/**
		 * Checks if a user is an administrator
		 * @param PDO $dbh Database handler
		 * @param integer $accountId
		 * @return boolean
		 * @throws InvalidArgumentException if the account id is undefined
		 */
		public static function isAdministrator($dbh, $accountId){
			$result = DBTools::fetchAll($dbh, "SELECT is_administrator FROM Account WHERE account_id = :accid", array(":accid" => $accountId));
			if(empty($result) || count($result) != 1){throw InvalidArgumentException("Undefined account id.");}
			return $result[0][0] == 1;
		}

		/**
		 * Checks the password of an user
		 *
		 * <u>Possible return values:</u><br>
		 * <ul>
		 * <li>1 = Password is correct</li>
		 * <li>0 = Password is not correct</li>
		 * <li>-1 = Error: For example if the account id does not exist</li>
		 * </ul>
		 * @param PDO $dbh Database handler
		 * @param integer $accountid The user's account id
		 * @param string $password Its password
		 * @return integer
		 */
		public static function checkPassword($dbh, $accountid, $password){
			$result = DBTools::fetchAll($dbh, "SELECT password, salt FROM " . self::TABLE_ACCOUNT . " WHERE account_id = :accid", array(":accid" => $accountid));
			if(empty($result) || count($result) != 1){return -1;}
			return (self::getPBKDF2Hash($password, base64_decode($result[0]["salt"]))[0] == $result[0]["password"] ? 1 : 0);
		}

		/**
		 * Sets a new password for an account
		 * @param PDO $dbh Database handler
		 * @param integer $accountid The user's account id
		 * @param string $newPassword The new password
		 */
		public static function setNewPassword($dbh, $accountid, $newPassword){
			$hash = self::getPBKDF2Hash($newPassword);
			$result = DBTools::query($dbh, "UPDATE Account SET password = :pw, salt = :salt WHERE account_id = :accid", array(":pw" => $hash[0], ":salt" => $hash[1], ":accid" => $accountid));
			return $result;
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
			return preg_match("/^[A-Za-z0-9][A-Za-z0-9_]{1,63}$/", $username);
		}

		/**
		 * Checks if an email is valid. (An email adress has to be shorter than 64 characters)
		 * @param string $email
		 * @return boolean
		 */
		public static function isValidEMailAddr($email){
			return (filter_var($email, FILTER_VALIDATE_EMAIL) && strlen($email) < 64) ? 1 : 0;
		}

		/**
		 * Checks if a first- or last name is valid.
		 * Conditions therfore are: Only A-Z, a-z, " " as characters and a length less than 64.
		 * @param string $name
		 * @return boolean
		 */
		public static function isValidRealName($name){
			if($name == null || $name == ""){return true;}
			return preg_match("/^[A-Za-zÄäÖöÜüß \-]{1,63}$/", $name);
		}
	}

	/**
	 * Account status enumeration
	 */
	abstract class AccountStatus
	{
		const AccountDoesNotExist = 1;
		const UsernameAlreadyInUse = 0;
		const EMailAddressAlreadyInUse = -1;
		const InvalidUsername = -2;
		const InvalidEMailAddress = -3;
	}
?>
