<?php

load("app/AccountManager.php");

class AccountManagerTest extends PHPUnit_Framework_TestCase{

	public function testCreateMultipleAccounts(){
		$ids = array(10);

		for($i = 0; $i < 10; $i++){
			$username = "testuser" . $i;
			$email = "testuser$i@example.com";
			if(AccountManager::accountExists(TestHelper::getDBH(), $username, $email) == AccountStatus::AccountDoesNotExist){
				$ids[$i] = $this->createAccount($username, "topsecret", $email, array());
			}
			else{
				$res = AccountManager::accountExists(TestHelper::getDBH(), $username, $email);
				if($res == AccountStatus::EMailAddressAlreadyInUse){
					$ids[$i] = AccountManager::getAccountIdByUserName(TestHelper::getDBH(), $username);
				}
				else if($res == AccountStatus::UsernameAlreadyInUse){
					$ids[$i] = AccountManager::getAccountIdByEmailAddress(TestHelper::getDBH(), $email);
				}
				else{
					$this->fail("Valid username or email address is not accepted.");
				}
			}

			// Now there hase to be an account id in all array fields
			$this->assertGreaterThan(0, $ids[$i]);

			// ..and accountExist will always be true. Note: it is "asertFalse" and "NotExist"
			$this->assertFalse(
				AccountManager::accountExists(TestHelper::getDBH(), $username, $email) == AccountStatus::AccountDoesNotExist
			);
		}

		for($i = 0; $i < 10; $i++){
			AccountManager::deleteAccount(TestHelper::getDBH(), $ids[$i]);
			$this->assertTrue(
				AccountManager::accountExists(TestHelper::getDBH(), "testuser" . $i, "testuser$i@example.com") == AccountStatus::AccountDoesNotExist
			);
		}
	}

	public function testAccountManagerFunctions(){

		$usrname = "testuser";
		$pw = "topsecret";
		$email = "testuser@example.com";

		// Account should not exist
		 $this->assertTrue(
		 		AccountManager::accountExists(TestHelper::getDBH(), $usrname, $email) == AccountStatus::AccountDoesNotExist,
		 "Note: Try to reset the database to avoid this error.");

		 // Create Account
		$id = $this->createAccount($usrname, $pw, $email, array("firstname" => "test", "lastname" => "user"));
		$this->assertGreaterThanOrEqual(0, $id);


		// Test that an account cannot be created twice
		$this->assertTrue(
			AccountManager::accountExists(TestHelper::getDBH(), $usrname, $email) == AccountStatus::UsernameAlreadyInUse
		);

		$this->assertTrue(
			AccountManager::accountExists(TestHelper::getDBH(), "some_other_user", $email) == AccountStatus::EMailAddressAlreadyInUse
		);

		// Test 'getAccountIdByUserName()'
		$this->assertEquals($id, AccountManager::getAccountIdByUserName(TestHelper::getDBH(), $usrname));

		// Test 'checkPassword()' -> Password okay
		$this->assertEquals(1, AccountManager::checkPassword(TestHelper::getDBH(), $id, $pw));

		// Test 'checkPassword()' -> wrong Password
		$this->assertEquals(0, AccountManager::checkPassword(TestHelper::getDBH(), $id, "wrong_pw"));

		// Test 'checkPassword()' -> Invalid user Id
		$this->assertEquals(-1, AccountManager::checkPassword(TestHelper::getDBH(), PHP_INT_MAX, $pw));

		AccountManager::deleteAccount(TestHelper::getDBH(), $id);

		// Account should not exist anymore
		$this->assertTrue(
			AccountManager::accountExists(TestHelper::getDBH(), $usrname, $email) == AccountStatus::AccountDoesNotExist,
		"Testaccount could't be deleted");
	}

	/**
	 * @expectedException InvalidArgumentException
	 */
	public function testCreateAccountInvalidEMail(){
		$this->createAccount("testuser", "topsecret", "testuser@example", array());
	}

	/**
	 * @expectedException InvalidArgumentException
	 */
	public function testInvalidUsername(){
		$this->createAccount("test++user", "topsecret", "test++user@example.de", array());
	}


	private function createAccount($name, $pw, $email, $details){
		return AccountManager::createAccount(TestHelper::getDBH(), $name, $pw, $email, false, $details);
	}
}