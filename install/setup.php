<?php
//ini_set('memory_limit', '5120M');
set_time_limit ( 0 );

/**
 * This setup is based on "sql_parse.php" from "The phpBB Group" (see below)
 */

/***************************************************************************
*                             sql_parse.php
*                              -------------------
*     begin                : Thu May 31, 2001
*     copyright            : (C) 2001 The phpBB Group
*     email                : support@phpbb.com
*
*     $Id: sql_parse.php,v 1.8 2002/03/18 23:53:12 psotfx Exp $
*
****************************************************************************/

/***************************************************************************
 *
 *   This program is free software; you can redistribute it and/or modify
 *   it under the terms of the GNU General Public License as published by
 *   the Free Software Foundation; either version 2 of the License, or
 *   (at your option) any later version.
 *
 ***************************************************************************/

/***************************************************************************
*
*   These functions are mainly for use in the db_utilities under the admin
*   however in order to make these functions available elsewhere, specifically
*   in the installation phase of phpBB I have seperated out a couple of
*   functions into this file.  JLH
*
\***************************************************************************/

//
// remove_comments will strip the sql comment lines out of an uploaded sql file
// specifically for mssql and postgres type files in the install....
//
function remove_comments(&$output)
{
   $lines = explode("\n", $output);
   $output = "";

   // try to keep mem. use down
   $linecount = count($lines);

   $in_comment = false;
   for($i = 0; $i < $linecount; $i++)
   {
      $currentLineIsAComent = false;
      if( preg_match("/^\/\*/", preg_quote($lines[$i])) )
      {
         $in_comment = true;
      }

      if( preg_match("/^\-\- /", $lines[$i]) )
      {
         $currentLineIsAComent = true;
      }

      if( !$in_comment && !$currentLineIsAComent)
      {
         $output .= $lines[$i] . "\n";
      }

      if( preg_match("/\*\/$/", preg_quote($lines[$i])) )
      {
         $in_comment = false;
      }
   }

   unset($lines);
   return $output;
}

//
// remove_remarks will strip the sql comment lines out of an uploaded sql file
//
function remove_remarks($sql)
{
   $lines = explode("\n", $sql);

   // try to keep mem. use down
   $sql = "";

   $linecount = count($lines);
   $output = "";

   for ($i = 0; $i < $linecount; $i++)
   {
      if (($i != ($linecount - 1)) || (strlen($lines[$i]) > 0))
      {
         if (isset($lines[$i][0]) && $lines[$i][0] != "#")
         {
            $output .= $lines[$i] . "\n";
         }
         else
         {
            $output .= "\n";
         }
         // Trading a bit of speed for lower mem. use here.
         $lines[$i] = "";
      }
   }

   return $output;

}

//
// split_sql_file will split an uploaded sql file into single sql statements.
// Note: expects trim() to have already been run on $sql.
//
function split_sql_file($sql, $delimiter)
{
   // Split up our string into "possible" SQL statements.
   $tokens = explode($delimiter, $sql);

   // try to save mem.
   $sql = "";
   $output = array();

   // we don't actually care about the matches preg gives us.
   $matches = array();

   // this is faster than calling count($oktens) every time thru the loop.
   $token_count = count($tokens);
   for ($i = 0; $i < $token_count; $i++)
   {
      // Don't wanna add an empty string as the last thing in the array.
      if (($i != ($token_count - 1)) || (strlen($tokens[$i] > 0)))
      {
         // This is the total number of single quotes in the token.
         $total_quotes = preg_match_all("/'/", $tokens[$i], $matches);
         // Counts single quotes that are preceded by an odd number of backslashes,
         // which means they're escaped quotes.
         $escaped_quotes = preg_match_all("/(?<!\\\\)(\\\\\\\\)*\\\\'/", $tokens[$i], $matches);

         $unescaped_quotes = $total_quotes - $escaped_quotes;

         // If the number of unescaped quotes is even, then the delimiter did NOT occur inside a string literal.
         if (($unescaped_quotes % 2) == 0)
         {
            // It's a complete sql statement.
            $output[] = $tokens[$i];
            // save memory.
            $tokens[$i] = "";
         }
         else
         {
            // incomplete sql statement. keep adding tokens until we have a complete one.
            // $temp will hold what we have so far.
            $temp = $tokens[$i] . $delimiter;
            // save memory..
            $tokens[$i] = "";

            // Do we have a complete statement yet?
            $complete_stmt = false;

            for ($j = $i + 1; (!$complete_stmt && ($j < $token_count)); $j++)
            {
               // This is the total number of single quotes in the token.
               $total_quotes = preg_match_all("/'/", $tokens[$j], $matches);
               // Counts single quotes that are preceded by an odd number of backslashes,
               // which means they're escaped quotes.
               $escaped_quotes = preg_match_all("/(?<!\\\\)(\\\\\\\\)*\\\\'/", $tokens[$j], $matches);

               $unescaped_quotes = $total_quotes - $escaped_quotes;

               if (($unescaped_quotes % 2) == 1)
               {
                  // odd number of unescaped quotes. In combination with the previous incomplete
                  // statement(s), we now have a complete statement. (2 odds always make an even)
                  $output[] = $temp . $tokens[$j];

                  // save memory.
                  $tokens[$j] = "";
                  $temp = "";

                  // exit the loop.
                  $complete_stmt = true;
                  // make sure the outer loop continues at the right point.
                  $i = $j;
               }
               else
               {
                  // even number of unescaped quotes. We still don't have a complete statement.
                  // (1 odd and 1 even always make an odd)
                  $temp .= $tokens[$j] . $delimiter;
                  // save memory.
                  $tokens[$j] = "";
               }

            } // for..
         } // else
      }
   }

   return $output;
}

/*
 * ============================================================================
 * Begin of setup part
 * ============================================================================
 */

function createDatabase($dbh, $dbtype, $database_name){

	if($dbtype == "mysql"){
		$sql = 	"CREATE DATABASE IF NOT EXISTS `" . $database_name . "`\n" .
				"	DEFAULT CHARACTER SET utf8\n" .
				"	DEFAULT COLLATE utf8_general_ci;";
	}
	else if($dbtype == "pgsql"){
		$sql = "CREATE DATABASE " . $database_name . " ENCODING 'UTF8';";
	}
	else{
		die("Cannot create database for database type '" . $dbtype . "' (not supported)");
	}

	printf("Creating database '%s'...", $database_name);

	$query = $dbh->prepare($sql);
	$res = $query->execute();
	if ($res) {
		print (" done.\n(Closing connection to database)\n\n");
	}
	else{
		print("\n\nERROR - Operation failed. Unable to create database:\n" .$sql . "\n");
		print("\nPDOStatement::errorInfo():\n");
		$arr = $query->errorInfo();
		print_r($arr);
    print("\n");
	}
}

function dropDatabase($dbh, $dbtype, $database_name){
	if($dbtype == "mysql"){
		$sql = 	"DROP DATABASE IF EXISTS `" . $database_name . "`";
	}
	else if($dbtype == "pgsql"){
		$sql = "DROP DATABASE IF EXISTS " . $database_name;
	}
	else{
		die("Cannot delete database (dbtype: '" . $dbtype . " is not supported).");
	}

	printf("Deleting database '%s'...", $database_name);

	$query = $dbh->prepare($sql);
	$res = $query->execute();
	if ($res) {
		print (" done.\n");
	}
	else{
		print("\n\nERROR - Operation failed. Unable to delete database:\n" .$sql . "\n");
		print("\nPDOStatement::errorInfo():\n");
		$arr = $query->errorInfo();
		print_r($arr);
    print("\n");
	}
}

// Install section

function installSQL_File($filename, $dbh){
	$sql_query = @fread(@fopen($filename, 'r'), @filesize($filename)) or die("Cannot read file '" . $filename . "'.");
	$sql_query = remove_comments($sql_query);
	$sql_query = remove_remarks($sql_query);
	$sql_query = split_sql_file($sql_query, ';');

	foreach($sql_query as $sql){

		$query = $dbh->prepare($sql);
		$res = $query->execute();
		if ($res) {
			print (".");
		}
		else{
			print("\n\nERROR while executing sql statement:\n" .$sql . "\n");
			print("\nPDOStatement::errorInfo():\n");
			$arr = $query->errorInfo();
			print_r($arr);
      print("\n");
			die;
		}
	}

	print (" done.\n");
}

function connectToDatabase($dbtype, $host, $port, $dbname, $username, $password){
	try{
		printf("Connecting to %s://%s@%s%s%s...\n\n", $dbtype, $username, $host,
				($port != "" ? ":" . $port : ""),
				($dbname != "" ? " (database: " . $dbname . ")" : ""));
		return new PDO($dbtype . ":host=" . $host . ($port != "" ? ";port=" . $port : "") . ($dbname != "" ? ";dbname=" . $dbname : ""), $username, $password);

	} catch (PDOException $e) {
		die("Connection to database failed: " . $e->getMessage());
	}
}

function createAdminAccount($dbh, $user, $pw, $email){
	if($user != null){
		require_once(__DIR__ . "/../src/app/AccountManager.php");
		print("Creating adminsitrator account... ");
		try{
			$accId = AccountManager::createAccount($dbh, $user, $pw, $email, true, array());
			print("done. (account_id='" . $accId . "')\n");
		}
		catch(InvalidArgumentException $e){
			print("failed.\nUnable to create adminsitrator account: " . $e->getMessage(). "\n");
		}
		catch(Exception $e){
			print("failed.\nUnable to create adminsitrator account: " . $e->getMessage(). "\n");
		}
	}
}

// Parse command line arguments

$prefix = "";
$db_type = "";
$db_host = "localhost";
$db_port = "";
$db_user = "root";
$db_paswd = "";
$db_name = "geocat";
$admin_user = null;
$admin_pw = null;
$admin_email = null;
$create_database = false;

for($i = 1; $i < count($argv); $i++){

	switch ($argv[$i]) {
		case "--install":
		case "-i":
			$prefix = "setup";
			break;

		case "--uninstall":
		case "-u":
			$prefix = "cleanup";
			break;

		case "--delete":
		case "-d":
			$prefix = "delete";
			break;

		case "--create-admin":
		case "--admin":
			if($prefix == ""){$prefix = "create_admin";}
			if(count($argv) <= $i + 3){
				print("Invalid usage of '--create-admin'. For more information please use the '--help' switch.\n");
				exit(0);
			}
			$admin_user = $argv[++$i];
			$admin_pw = $argv[++$i];
			$admin_email = $argv[++$i];
			break;

		case "--dbtype":
		case "-t":
	        $db_type = strtolower($argv[++$i]);
			break;

		case "--create-database":
		case "-c":
			$db_name = $argv[++$i];
			$create_database = true;
			break;

		case "--dbname":
		case "-db":
			$db_name = $argv[++$i];
			break;

		case "--host":
		case "-h":
			$db_host = $argv[++$i];
			break;

		case "--port":
		case "-p":
			$db_port = $argv[++$i];
			break;

		case "--user":
		case "-l":
			$db_user = $argv[++$i];
			break;

		case "--password":
		case "-pw":
		case "--pw":
			$db_paswd = $argv[++$i];
			break;

		case "--help":
		case "-?":
			print("\nUsage: php setup.php [--install|--uninstall|--delete] [options]\n\n" .
					"Supported options:\n" .
					"--dbtype; -t <type>	(possible values: 'mysql' or 'pgsql')\n" .
					"--host; -h <host>	(by default this is 'localhost')\n" .
					"--port; -p <port>\n" .
					"--user; -l <user>\n" .
					"--password; -pw <password>\n" .
					"--create-database; -c <database name>	(create a new database)\n" .
					"--dbname; -db <database name>		(by default this is 'geocat')\n\n" .
					"It's also possible to use '-i' and '-u' instead of '--install' or '--uninstall'\n\n\n" .
					"This setup can be used to create administrator accounts too:\n" .
					"   php setup.php [options] --create-admin <username> <password> <email_address>\n\n".
					"You can use this parameter in combination with the install command too.\n");
			exit(0);

		default:
			die("Unknown command line option: " . $argv[$i]);
	}
}

if(!($db_type == "mysql" || $db_type == "pgsql")){
	die("Database type is not defined. Please use the '--dbtype' switch to do this.");
}

if($prefix == "" || ($prefix != "setup" && $prefix != "cleanup" && $prefix != "delete" && $prefix != "create_admin")){
	die("Please use one of the following parameters '--install', '--uninstall' or '--delete'.");
}

if($db_host == ""){
	die("Error: Hostname is not defined.");
}

if($db_user == ""){
	die("Error: Username is not defined.");
}

if($db_name == ""){
	die("Error: No database name defined.");
}

if($prefix == "setup"){

	/* ========================================================================
	 * Install database
	 * ======================================================================== */

	$setupFile = __DIR__ . "/sql/" . $db_type . ".setup.sql";
	$cleanupFile = __DIR__ ."./sql/" . $db_type . ".cleanup.sql";
	$defaultDataFile = __DIR__ . "./sql/generic.default_data.sql";

	if(file_exists($setupFile) && file_exists($cleanupFile)){
		// All sql files are available

		if($create_database){
			$dbh = connectToDatabase($db_type, $db_host, $db_port, "", $db_user, $db_paswd);
			createDatabase($dbh, $db_type, $db_name);
		}

		$dbh = connectToDatabase($db_type, $db_host, $db_port, $db_name, $db_user, $db_paswd);

		if(!$create_database){
			print("Running 'cleanup'");
			installSQL_File($cleanupFile, $dbh);
		}

		print("Creating tables");
		installSQL_File($setupFile, $dbh);

		print("Setting up default values");
		installSQL_File($defaultDataFile, $dbh);

		createAdminAccount($dbh, $admin_user, $admin_pw, $admin_email);

		print("\nSetup finished successfully.\n");
	}
	else{
		die("Cannot find required sql files: '" . $setupFile . "' and '" . $cleanupFile . "'.");
	}
}
else if($prefix == "cleanup"){

	/* ========================================================================
	 * Remove all tables
	 * ======================================================================== */

	$cleanupFile = "./sql/" . $db_type . ".cleanup.sql";

	if(file_exists($cleanupFile)){
		// All sql files are available

		$dbh = connectToDatabase($db_type, $db_host, $db_port, $db_name, $db_user, $db_paswd);

		print("Running 'cleanup'");
		installSQL_File($cleanupFile, $dbh);
		print("\nSetup finished successfully, all tables has been removed.\n");
	}
	else{
		die("Cannot find required sql file: '" . $cleanupFile . "'.");
	}
}
else if($prefix == "delete"){

	/* ========================================================================
	 * Remove database
	 * ======================================================================== */

	$dbh = connectToDatabase($db_type, $db_host, $db_port, "", $db_user, $db_paswd);
	dropDatabase($dbh, $db_type, $db_name);
	print("\nDatabase successfully deleted.\n");
}
else if($prefix == "create_admin"){
	// Maybe just create another adminsitrator account?
	$dbh = connectToDatabase($db_type, $db_host, $db_port, $db_name, $db_user, $db_paswd);
	createAdminAccount($dbh, $admin_user, $admin_pw, $admin_email);
}
?>
