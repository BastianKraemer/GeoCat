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

function createSchema($dbh, $dbtype, $schemaname){

	if($dbtype == "mysql"){
		$sql = 	"CREATE DATABASE IF NOT EXISTS `" . $schemaname . "`\n" .
				"	DEFAULT CHARACTER SET utf8\n" .
				"	DEFAULT COLLATE utf8_general_ci;";
	}
	else if($dbtype == "pgsql"){
		$sql = "CREATE DATABASE " . $schemaname . " ENCODING 'UTF8';";
	}
	else{
		die("Cannot create schema for database type '" . $dbtype . "' (not supported)");
	}

	printf("Creating schema '%s'...", $schemaname);

	$query = $dbh->prepare($sql);
	$res = $query->execute();
	if ($res) {
		print (" done.\n(Closing connection to database)\n\n");
	}
	else{
		print("\n\nERROR - Creating schma failed:\n" .$sql . "\n");
		print("\nPDOStatement::errorInfo():\n");
		$arr = $query->errorInfo();
		print_r($arr);
	}
}

function dropSchema($dbh, $dbtype, $schemaname){
	if($dbtype == "mysql"){
		$sql = 	"DROP DATABASE IF EXISTS `" . $schemaname . "`\n";
	}
	else if($dbtype == "pgsql"){
		$sql = "DROP DATABASE IF EXISTS " . $$schemaname;
	}
	else{
		die("Cannot delete schema for database type '" . $dbtype . "' (not supported)");
	}

	printf("Deleting schema '%s'...", $schemaname);

	$query = $dbh->prepare($sql);
	$res = $query->execute();
	if ($res) {
		print (" done.\n");
	}
	else{
		print("\n\nERROR - Deleting schma failed:\n" .$sql . "\n");
		print("\nPDOStatement::errorInfo():\n");
		$arr = $query->errorInfo();
		print_r($arr);
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
			die;
		}
	}

	print (" done.\n");
}

function connectToDatabase($dbtype, $host, $port, $dbname, $username, $password){
	try{
		printf("Connecting to %s://%s@%s%s%s...\n\n", $dbtype, $username, $host,
				($port != "" ? ":" . $port : ""),
				($dbname != "" ? " (Schame name: " . $dbname . ")" : ""));
		return new PDO($dbtype . ":host=" . $host . ($port != "" ? ";port=" . $port : "") . ($dbname != "" ? ";dbname=" . $dbname : ""), $username, $password);

	} catch (PDOException $e) {
		die("Connection to database failed: " . $e->getMessage());
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
$create_schema = false;

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

		case "--dbtype":
		case "-t":
	        $db_type = strtolower($argv[++$i]);
			break;

		case "--create-database":
		case "--create-schema":
		case "-cs":
			$db_name = $argv[++$i];
			$create_schema = true;
			break;

		case "--dbname":
		case "--schema":
		case "-s":
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
					"--create-schema; -cs <schema name>	(create a new schema)\n" .
					"--schema; -s <schema name>		(by default this is 'geocat')\n\n" .
					"It's also possible to use '-i' and '-u' instead of '--install' or '--uninstall'");
			exit(0);

		default:
			die("Unkown command line option: " . $argv[$i]);
	}
}

if(!($db_type == "mysql" || $db_type == "pgsql")){
	die("Database type is not defined. Use '--db [mysql|pgsql]' to define this.");
}

if($prefix == "" || ($prefix != "setup" && $prefix != "cleanup" && $prefix != "delete")){
	die("Please use one of the following parameters '--install', '--uninstall' or '--delete'.");
}

if($db_host == ""){
	die("Error: Hostname cannot be empty!");
}

if($db_user == ""){
	die("Error: Username cannot be empty!");
}

if($db_name == ""){
	die("Error: database name cannot be empty!");
}


if($prefix == "setup"){

	/* ========================================================================
	 * Install database
	 * ======================================================================== */

	$setupFile = "./sql/" . $db_type . ".setup.sql";
	$cleanupFile = "./sql/" . $db_type . ".cleanup.sql";

	if(file_exists($setupFile) && file_exists($cleanupFile)){
		// All sql files are available

		if($create_schema){
			$dbh = connectToDatabase($db_type, $db_host, $db_port, "", $db_user, $db_paswd);
			createSchema($dbh, $db_type, $db_name);
		}

		$dbh = connectToDatabase($db_type, $db_host, $db_port, $db_name, $db_user, $db_paswd);

		if(!$create_schema){
			print("Running 'cleanup'");
			installSQL_File($cleanupFile, $dbh);
		}

		print("Creating database");
		installSQL_File($setupFile, $dbh);
		print("\nSetup finished successful.\n");
	}
	else{
		die("Cannot find required sql files: '" . $setupFile . "' and '" . $cleanupFile . "'.");
	}
}
else if($prefix == "cleanup"){

	/* ========================================================================
	 * Remove alle tables
	 * ======================================================================== */

	$cleanupFile = "./sql/" . $db_type . ".cleanup.sql";

	if(file_exists($cleanupFile)){
		// All sql files are available

		$dbh = connectToDatabase($db_type, $db_host, $db_port, $db_name, $db_user, $db_paswd);

		print("Running 'cleanup'");
		installSQL_File($cleanupFile, $dbh);
		print("\nSetup finished successful, all tables has been removed.\n");
	}
	else{
		die("Cannot find required sql file: '" . $cleanupFile . "'.");
	}
}
else if($prefix == "delete"){

	/* ========================================================================
	 * Remove database (schema)
	 * ======================================================================== */

	$dbh = connectToDatabase($db_type, $db_host, $db_port, "", $db_user, $db_paswd);
	dropSchema($dbh, $db_type, $db_name);
	print("\nDatabase successfully deleted.\n");
}
?>
