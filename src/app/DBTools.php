<?php
/* GeoCat - Geocaching and -tracking application
 * Copyright (C) 2016 Bastian Kraemer, Raphael Harzer
 *
 * DBTools.php
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
 * File DBTools.php
 * @package app
 */

	/**
	 * This class contains some (static) methods for the database handling
	 * For example there is a method to create a PDO object by using the application configuration.
	 */
	class DBTools {

		/**
		 * Database handler
		 * @var PDO
		 */
		private static $dbh = null;

		/**
		 * Create a PDO database object by using the default configuration
		 * @param array $config Application configuration (see "config/config.php")
		 * @throws PDOException If the connection couldn't be established
		 */
		public static function connectToDatabase(){
			require_once(__DIR__ . "/GeoCat.php");
			return self::connect(GeoCat::getConfig(), false, false);
		}

		/**
		 * Create a PDO database object by using the default configuration
		 * @param array $config Application configuration (see "config/config.php")
		 * @param boolean $verbose
		 * @param boolean $withoutDatabaseName
		 *
		 * @throws PDOException If the connection couldn't be established
		 */
		public static function connect($config, $verbose, $withoutDatabaseName){
			$port = ($config["database.port"] != "" ? ";port=" . $config["database.port"] : "");
			$dbName = ($withoutDatabaseName ? "" : ";dbname=" . $config["database.name"]);
			$charsetOption = ($config["database.type"] == "mysql" ? ";charset=utf8" : "");

			if($verbose){
				printf("Connecting to %s://%s@%s%s%s...\n", $config["database.type"], $config["database.username"], $config["database.host"],
						($config["database.port"] != "" ? ":" . $config["database.port"] : ""),
						($withoutDatabaseName ||($config["database.name"] == "") ? "" : " (database: " . $config["database.name"] . ")"));
			}

			// Establish connection to database
			$dbh = new PDO($config["database.type"] . ":host=" . $config["database.host"] . $port . $dbName . $charsetOption, $config["database.username"], $config["database.password"]);
			$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			if($config["database.type"] != 'mysql'){
				self::query($dbh, "SET CLIENT_ENCODING TO 'UTF8';");
			}

			return $dbh;
		}

		/**
		 * Creates a PDO database object and stores it for further usage.
		 * You can access the database handler by calling this function again.
		 * @param array $config Application configuration (see "config/config.php")
		 * @throws PDOException If the connection couldn't be established
		 */
		public static function getDatabaseHandler($config){
			if(self::$dbh == null){self::$dbh = connectToDatabase($config);}
			return self::$dbh;
		}

		/**
		 * Fetches all data from a SQL statement
		 *
		 * Example:<br>
		 * <code>
		 * // require_once("/app/DBTools.php");<br>
		 * $dbh = DBTools::connectToDatabase();<br>
		 * $ret = DBTools::fetchAll($dbh, "SELECT * FROM Account WHERE email = :email", array(":email" => "master@example.com"));
		 * </code>
		 * @param PDO $dbh PDO database connection
		 * @param string $sql SQL statement
		 * @param array $values (optional) Values for the SQL statement
		 * @param PDOFetchStyle $fetchStyle (Optional) PDO fetch style
		 * @return array The fetched data
		 * @throws PDOException If the SQL statement is invalid or contains at least on undefined parameter
		 * @throws Exception If the database returned an error
		 */
		public static function fetchAll($dbh, $sql, $values = null, $fetchStyle = PDO::FETCH_BOTH){
			$stmt = $dbh->prepare($sql);

			$res = ($values == null ? $stmt->execute() : $stmt->execute($values));
			if($res){
				return $stmt->fetchAll($fetchStyle);
			}
			else{
				throw new Exception("Error while excuting SQL statement, database returned '" . $res . "'");
			}
		}

		/**
		 * Fetches the first row from an SQL statement
		 * @param PDO $dbh PDO database connection
		 * @param string $sql SQL statement
		 * @param array $values (optional) Values for the SQL statement
		 * @param PDOFetchStyle $fetchStyle (Optional) PDO fetch style
		 * @return array The fetched row
		 * @throws PDOException If the SQL statement is invalid or contains at least on undefined parameter
		 * @throws Exception If the database returned an error
		 */
		public static function fetch($dbh, $sql, $values = null, $fetchStyle = PDO::FETCH_BOTH){
			$stmt = $dbh->prepare($sql);

			$res = ($values == null ? $stmt->execute() : $stmt->execute($values));
			if($res){
				return $stmt->fetch($fetchStyle);
			}
			else{
				throw new Exception("Error while excuting SQL statement, database returned '" . $res . "'");
			}
		}

		/**
		 * Fetches the first row from an SQL statement without indexes
		 * @param PDO $dbh PDO database connection
		 * @param string $sql SQL statement
		 * @param array $values (optional) Values for the SQL statement
		 * @return array The fetched row
		 * @throws PDOException If the SQL statement is invalid or contains at least on undefined parameter
		 * @throws Exception If the database returned an error
		 */
		public static function fetchAssoc($dbh, $sql, $values = null){
			return self::fetch($dbh, $sql, $values, PDO::FETCH_ASSOC);
		}

		/**
		 * Fetches the first row from an SQL statement with indexes as column names
		 * @param PDO $dbh PDO database connection
		 * @param string $sql SQL statement
		 * @param array $values (optional) Values for the SQL statement
		 * @return array The fetched row
		 * @throws PDOException If the SQL statement is invalid or contains at least on undefined parameter
		 * @throws Exception If the database returned an error
		 */
		public static function fetchNum($dbh, $sql, $values = null){
			return self::fetch($dbh, $sql, $values, PDO::FETCH_NUM);
		}

		/**
		 * Executes a SQL statement
		 * @param PDO $dbh
		 * @param string $sql
		 * @param array $values
		 * @return integer Return value of the database
		 * @throws PDOException If the SQL statement is invalid or contains at least on undefined parameter
		 */
		public static function query($dbh, $sql, $values = null){
			$stmt = $dbh->prepare($sql);

			$res = ($values == null ? $stmt->execute() : $stmt->execute($values));
			return $res;
		}
	}
?>
