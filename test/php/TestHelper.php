<?php

require_once(__DIR__ . "/../../src/app/GeoCat.php");
require_once(__DIR__ . "/../../src/app/DBTools.php");

function load($file){
	require_once(__DIR__ . "/../../src/" . $file);
}

GeoCat::setConfigPathRelativeToAppDirectory("../../test/testconfig.php");
load("app/AccountManager.php");

class TestHelper {
	private static $dbh = null;
	private static $globalStore = array();
	public static function getDBH(){
		if(self::$dbh == null){
			self::$dbh = DBTools::connectToDatabase();
		}
		return self::$dbh;
	}

	public static function createAccount($name, $pw, $email, $details){
		return AccountManager::createAccount(TestHelper::getDBH(), $name, $pw, $email, false, $details);
	}

	private static $testUsers = array(
		array("username" => "Foo", "email" => "foo@bar.com", "password" => "foobar", "firstname" => "Foo", "lastname" => "Bar"),
		array("username" => "Alpha", "email" => "alpha@example.com", "password" => "beta", "firstname" => "Alpha", "lastname" => "Beta"),
		array("username" => "doe", "email" => "johndoe@example.com", "password" => "xy123", "firstname" => "John", "lastname" => "Doe")
	);

	public static function getTestAccountId($n){
		$usr = self::$testUsers[$n]["username"];
		$email = self::$testUsers[$n]["email"];

		if(AccountManager::accountExists(self::getDBH(), $usr, $email) == AccountStatus::AccountDoesNotExist){
			return self::createAccount($usr, self::$testUsers[$n]["password"], $email, self::$testUsers[$n]);
		}
		else{
			return AccountManager::getAccountIdByUserName(self::getDBH(), $usr);
		}
	}

	public static function storeVal($key, $value){
		self::$globalStore[$key] = $value;
	}

	public static function hasVal($key){
		return array_key_exists($key, self::$globalStore);
	}

	public static function getVal($key){
		if(array_key_exists($key, self::$globalStore)){
			return self::$globalStore[$key];
		}
		else{
			return null;
		}
	}
}

