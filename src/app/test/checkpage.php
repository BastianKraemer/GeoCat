<?php
/* GeoCat - Geocaching and -tracking application
 * Copyright (C) 2016 Bastian Kraemer, Raphael Harzer
 *
 * checkpage.php
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
 * GeoCat Testpage
 */

/**
 * The following lines allows you to display the checkpage in your browser
 */
require_once __DIR__ . "/../GeoCat.php";
$output = array_key_exists("argv", $GLOBALS) ? CheckPage::OUTPUT_TEXT : CheckPage::OUTPUT_HTML;
CheckPage::runCheck($output);

/**
 * Perform some checks to verify your GeoCat installation
 */
class CheckPage {

	const OUTPUT_TEXT = 0;
	const OUTPUT_HTML = 1;

	/**
	 * Run the check of your installation and print out the result
	 * @param mixed $output_type The output type: Possible values: CheckPage::OUTPUT_TEXT or CheckPage::OUTPUT_HTML
	 */
	public static function runCheck($output_type = self::OUTPUT_TEXT){
		$result = self::performCheck(GeoCat::getConfig());

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

	/**
	 * Run the check of your installation
	 * @param array $config
	 * @return CheckResult[]
	 */
	public static function performCheck($config){
		$checkResult = array();

		$checkResult["config_check"] = self::checkConfig($config);
		$checkResult["database_check"] = self::databaseCheck();
		$checkResult["php_mods"] = self::phpModuleCheck();

		return $checkResult;
	}

	/**
	 * Check GeoCat configuration
	 * @param array $config
	 * @return CheckResult The result of the check
	 */
	public static function checkConfig($config){

		$checkList = array(
				"app.name" => null,
				"database.host" => null,
				"database.port" => "/^[0-9]+$/",
				"database.type" => "/^(mysql|pgsql)$/",
				"database.username" => null,
				"database.password" =>  null,
				"database.name" => null,
				"policy.imprint" => null,
				"policy.data_privacy_statement" => null
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

	/**
	 * Check a signle configuration parameter
	 * @param array $config Current GeoCat configuration
	 * @param string $key Configuration key
	 * @param string $regExPattern (Optional) RegEx pattern for the value check
	 * @return CheckResult The result of the check
	 */
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

	/**
	 * Run a database check
	 * @return CheckResult The result of the check
	 */
	public static function databaseCheck(){
		require_once __DIR__ . "/../DBTools.php";
		$dbh;

		try{
			$dbh = DBTools::connectToDatabase();
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

	/**
	 * Check that all required phpModules are available
	 * @return CheckResult The result of the check
	 */
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

/**
 * This class represents the result of a test
 */
class CheckResult {
	const FAILED = 0;
	const WARNING = -1;
	const OK = 1;

	/**
	 * The test result: Can be CheckResult::OK, CheckResult::WARNING or CheckResult::FAILED
	 * @var mixed
	 */
	public $result;

	/**
	 * An optional message (this value my be null)
	 * @var string
	 */
	public $msg;

	/**
	 * Create a new CheckResult
	 * @param mixed $state The check result: Can be CheckResult::OK, CheckResult::WARNING or CheckResult::FAILED
	 * @param string $msg AN optional message
	 */
	public function __construct($state, $msg){
		$this->result = $state;
		$this->msg = $msg;
	}

	/**
	 * Creates a SUCESS result
	 * @return CheckResult
	 */
	public static function success(){
		return new CheckResult(CheckResult::OK, null);
	}

	/**
	 * Creates a FAILED result
	 * @param string $msg (optional) message
	 * @return CheckResult
	 */
	public static function failed($msg = null){
		return new CheckResult(CheckResult::FAILED, $msg);
	}

	/**
	 * Creates a WARNING result
	 * @param string $msg (optional) message´
	 * @return CheckResult
	 */
	public static function warning($msg = null){
		return new CheckResult(CheckResult::WARNING, $msg);
	}
}
?>
