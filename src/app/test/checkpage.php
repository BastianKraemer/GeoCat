<?php

$output = array_key_exists("argv", $GLOBALS) ? CheckPage::OUTPUT_TEXT : CheckPage::OUTPUT_HTML;
CheckPage::runCheck($output);

class CheckPage {

	const OUTPUT_TEXT = 0;
	const OUTPUT_HTML = 1;

	public static function runCheck($output_type = self::OUTPUT_TEXT){
		$config = require __DIR__ . "/../../config/config.php";

		$result = self::performCheck($config);

		if($output_type == self::OUTPUT_TEXT){
			print "Running checks...\n\n";

			foreach($result as $key => $value){
				$l = strlen($key);
				$res = ($value->result == CheckResult::OK ? "[   OK   ]" : ( $value->result == CheckResult::FAILED ? "[ FAILED ]" : "[ WARNING ]"));
				printf("%s%" . (37 - $l) . "s%s\n", $key, "", $res);
				print "------------------------------------------------\n";
				if($value->msg != null){
					print $value->msg . "\n";
				}
				print "\n";
			}
		}
		else{
			print "<!DOCTYPE HTML>\n";
			print "<html>\n";
			print "<body>\n";
			print "<table>\n";
			print "\t<tr><th>Check</th><th>Status</th><th>Message</th>\n";
			foreach($result as $key => $value){
				print "\t<tr>\n";
				print "\t\t<td>" . $key . "</td>\n";

				$status;
				switch($value->result){
					case CheckResult::OK:
						print "\t\t<td style=\"background-color: lime;\">OK</td>\n";
						break;
					case CheckResult::FAILED:
						print "\t\t<td style=\"background-color: red;\">FAILED</td>\n";
						break;
					case CheckResult::WARNING:
						print "\t\t<td style=\"background-color: yellow;\">WARNING</td>\n";
						break;
				}
				print "\t\t<td>" . ($value->msg == null ? "" : $value->msg) . "</td>\n";
				print "\t</tr>\n";
			}
			print "</table>\n</body>\n</html>\n";
		}
	}

	public static function performCheck($config){
		$checkResult = array();

		$checkResult["config_check"] = self::checkConfig($config);
		$checkResult["database_check"] = self::databaseCheck($config);
		$checkResult["php_mods"] = self::phpModuleCheck();

		return $checkResult;
	}

	public static function checkConfig($config){

		$checkList = array(
				"app.name" => null,
				"app.contextroot" => null,
				"database.host" => null,
				"database.port" => "/^[0-9]+$/",
				"database.type" => "/^(mysql|pgsql)$/",
				"database.username" => null,
				"database.password" =>  null,
				"database.name" => null
		);

		$returnState = CheckResult::OK;
		$msg = "";
		foreach($checkList as $key => $value){
			$res = self::checkConfigParam($config, $key, $value);
			if($res->result < $returnState){
				$returnState = $res->result;
			}
			if($res->msg != null){
				$msg = $msg . $res->msg ."\n";
			}
		}
		return new CheckResult($returnState, $msg);
	}

	private static function checkConfigParam($config, $key, $regExPattern = null){
		if(array_key_exists($key, $config)){
			if($regExPattern != null){
				if(!preg_match($regExPattern, $config[$key])){
					return CheckResult::warning(sprintf("Invalid value for config parameter '%s'", $key));
				}
			}
			return CheckResult::success();
		}
		else{
			return CheckResult::failed(sprintf("Config parameter '%s' is not defined.", $key));
		}
	}

	public static function databaseCheck($config){
		require_once __DIR__ . "/../DBTools.php";
		require_once __DIR__ . "/../GeoCat.php";
		$dbh;

		try{
			$dbh = DBTools::connectToDatabase($config);
		}
		catch(PDOException $pdoex){
			return CheckResult::failed("Unable to establish database connection: " . $pdoex->getMessage());
		}

		try{
			$dbVersion = DBTools::fetchNum($dbh, "SELECT db_version FROM GeoCat LIMIT 1")[0];

			if(strcmp($dbVersion, GeoCat::DB_VERSION) == 0){
				return CheckResult::success();
			}
			else{
				return CheckResult::warning(sprintf(
					"GeoCat database version does not match with latest db version.\n".
					"CURRENT_VERSION: %s; LATEST_VERSION: %s", $dbVersion, GeoCat::DB_VERSION));
			}

		}
		catch(PDOException $pdoex){
			return CheckResult::failed("Unable to find out GeoCat database version: " . $pdoex->getMessage());
		}
	}

	private static function phpModuleCheck(){
		require_once __DIR__ . "/../AccountManager.php";
		try{
			$pw = AccountManager::getPBKDF2Hash("password");
			return CheckResult::success();
		}
		catch(Exception $e){
			return CheckResult::failed("PHP module 'mcrypt' is not available.");
		}
	}
}

class CheckResult {
	const FAILED = 0;
	const WARNING = -1;
	const OK = 1;

	public $result;
	public $msg;

	public function __construct($state, $msg){
		$this->result = $state;
		$this->msg = $msg;
	}

	public static function success(){
		return new CheckResult(CheckResult::OK, null);
	}

	public static function failed($msg = null){
		return new CheckResult(CheckResult::FAILED, $msg);
	}

	public static function warning($msg = null){
		return new CheckResult(CheckResult::WARNING, $msg);
	}
}
?>
