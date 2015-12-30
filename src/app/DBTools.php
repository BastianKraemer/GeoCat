<?php
	/**
	 * File DBTools.php
	 */

	/**
	 * This class contains some (static) methods for the database handling
	 * For example there is a method to create a PDO object by using the application configuration.
	 */
	class DBTools {

		/**
		 * Create a PDO database object by using the default configuration
		 * @param array $config Application configuration (see "config/config.php")
		 * @throws PDOException If the connection couldn't be established
		 */
		public static function connectToDatabase($config){
			$port = ($config["database.port"] != "" ? ";port=" . $config["database.port"] : "");
			// Establish connection to database
			$dbh = new PDO($config["database.type"] . ":host=" . $config["database.host"] . $port . ";dbname=" . $config["database.name"] . ";charset=utf8", $config["database.username"], $config["database.password"]);
			$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			return $dbh;
		}

		/**
		 * Fetchs all data from a SQL statement
		 *
		 * Example:<br>
		 * <code>
		 * // $config = require("/config/config.php");<br>
		 * // require_once("/app/DBTools.php");<br>
		 * $dbh = DBTools::connectToDatabase($config);<br>
		 * $ret = DBTools::fetchAll($dbh, "SELECT * FROM Account WHERE email = :email", array(":email" => "master@example.com"));
		 * </code>
		 * @param PDO $dbh PDO database connection
		 * @param string $sql SQL statement
		 * @param array $values (optional) Values for the SQL statement
		 * @throws PDOException If the SQL statement is invalid or contains at least on undefined parameter
		 * @throws Exception If the database returned an error
		 */
		public static function fetchAll($dbh, $sql, $values = null){
			$stmt = $dbh->prepare($sql);

			$res = ($values == null ? $stmt->execute() : $stmt->execute($values));
			if($res){
				return $stmt->fetchAll();
			}
			else{
				throw new Exception("Error while excuting SQL statement, database returned '" . $res . "'");
			}
		}

		/**
		 * Executes a SQL statement
		 * @param PDO $dbh
		 * @param string $sql
		 * @param array $values
		 * @throws PDOException If the SQL statement is invalid or contains at least on undefined parameter
		 */
		public static function query($dbh, $sql, $values = null){
			$stmt = $dbh->prepare($sql);

			$res = ($values == null ? $stmt->execute() : $stmt->execute($values));
			return $res;
		}
	}
?>
