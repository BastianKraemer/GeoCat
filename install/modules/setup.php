<?php
	class DBSetupCommand {
		const INSTALL = 0;
		const DELETE = 1;
		const UPDATE = 2;
		const RESET = 3;
		const INFO = 4;
	}

	class GeoCatCLI_DBSetup extends GeoCatCLI_Command {

		CONST SQL_CREATE_DATABASE = "create";
		CONST SQL_SETUP = "setup";
		CONST SQL_CLEANUP = "cleanup";
		CONST SQL_DATA_PRESET = "data_preset";

		private $sqlFiles = array(
				self::SQL_CREATE_DATABASE => "",
				self::SQL_SETUP => "",
				self::SQL_CLEANUP => "",
				self::SQL_DATA_PRESET => "");

		private $dbSetupCmd = DBSetupCommand::INFO;
		private $configOverwrite = array();

		private function overwriteConfig($key, $value){
			$this->configOverwrite[$key] = $value;
		}

		public function getName(){return "setup";}
		public function getDescription(){return "Database setup";}

		public function printHelp(){
			$out1 = array(
				"--install" => "Create a new database and all tables for GeoCat",
				"--reset" => "Reset an existing database\n(This will delete all data)",
				"--delete" => "Delete an existing database",
				"--info" => "Print some information about the GeoCat database"
			);

			$out2 = array(
				"--dbtype, -t [mysql|pgsql]" => "Set database type",
				"--dbname, -db [name][" => "Set the database name)",
				"--host, -h [host]" => "Set database host",
				"--port, -p [port]" => "Set database port",
				"--user, -l [user]" => "Username for the database connection",
				"--password, -pw [password]" => "Password for the database connection"
			);

			print "This setup tool allows you to create, reset or remove a database for GeoCat.\n" .
				  "Therefore your configuration is taken from your GeoCat config file.\n\n";

			print "\nRequired options:\n";
			GeoCatCLI::printList($out1);

			print "\nOptional parameters:\n";
			GeoCatCLI::printList($out2);

			print "\nExamples:\n";
			print "  geocat.php setup --install\n";
			print "  geocat.php setup --reset -db GeoCatDB\n";
		}

		public function parseArgs($args, $startIndex){
			for($i = $startIndex; $i < count($args); $i++){

				switch ($args[$i]) {
					case "--install":
						$this->dbSetupCmd = DBSetupCommand::INSTALL;
						break;
					case "--reset":
						$this->dbSetupCmd = DBSetupCommand::RESET;
						break;
					case "--delete":
						$this->dbSetupCmd = DBSetupCommand::DELETE;
						break;
					case "--info":
						$this->dbSetupCmd = DBSetupCommand::INFO;
						break;

					case "--dbtype":
					case "-t":
						$this->overwriteConfig("database.type", strtolower($args[++$i]));
						break;

					case "--dbname":
					case "-db":
						$this->overwriteConfig("database.name", $args[++$i]);
						break;

					case "--host":
					case "-h":
						$this->overwriteConfig("database.host", $args[++$i]);
						break;

					case "--port":
					case "-p":
						$this->overwriteConfig("database.port", $args[++$i]);
						break;

					case "--user":
					case "-l":
						$this->overwriteConfig("database.username", $args[++$i]);
						break;

					case "--password":
					case "-pw":
					case "--pw":
						$this->overwriteConfig("database.password", $args[++$i]);
						break;

					default:
						$i += $this->cli->lookupGlobalCmd($args, $i);
				}
			}
		}

		public function exec(){
			require_once $this->cli->home . "/app/DBTools.php";
			require_once __DIR__ . "/../lib/sql_parse.php";
			$config = GeoCat::getConfig();

			foreach($this->configOverwrite as $key => $value){
				$config[$key] = $value;
			}

			$this->loadSQLFilePaths($config["database.type"]);

			switch ($this->dbSetupCmd){
				case DBSetupCommand::INSTALL:
					$this->createDatabase($config, $config["database.name"]);
					$this->installDatabase($config, false);
					break;

				case DBSetupCommand::RESET:
					$this->installDatabase($config, true);
					break;

				case DBSetupCommand::DELETE:
					$this->deleteDatabase($config, $config["database.name"]);
					break;

				case DBSetupCommand::INFO:
					$this->printDatabaseInfo($config);
					break;
			}
		}

		private function loadSQLFilePaths($db_type){

			$this->sqlFiles[self::SQL_CREATE_DATABASE] = __DIR__ . "/../sql/" . $db_type . ".create.sql";
			$this->sqlFiles[self::SQL_SETUP] = __DIR__ . "/../sql/" . $db_type . ".setup.sql";
			$this->sqlFiles[self::SQL_CLEANUP] = __DIR__ ."/../sql/" . $db_type . ".cleanup.sql";
			$this->sqlFiles[self::SQL_DATA_PRESET] = __DIR__ . "/../sql/generic.default_data.sql";

			foreach($this->sqlFiles as $key => $value){
				if(!file_exists($value)){
					throw new Exception(sprintf("Cannot find required '%s' sql file.", $key));
				}
			}
		}

		private function createDatabase($config, $dbName){
			$dbh = DBTools::connect($config, true, true);

			printf("\nCreating database '%s'...", $dbName);
			$this->installSQL_File($dbh, $this->sqlFiles[self::SQL_CREATE_DATABASE], array("%DATABASE_NAME%" => $dbName));

			printf("Closing connection to database... \n\n");

		}

		private function installDatabase($config, $clear){
			$dbh = DBTools::connect($config, true, false);

			if($clear){
				print("\nRunning 'cleanup'");
				$this->installSQL_File($dbh, $this->sqlFiles[self::SQL_CLEANUP]);
			}

			print("Creating tables");
			$this->installSQL_File($dbh, $this->sqlFiles[self::SQL_SETUP]);

			print("Setting up default values");
			$this->installSQL_File($dbh, $this->sqlFiles[self::SQL_DATA_PRESET],
									array("%DB_VERSION%" => GeoCat::DB_VERSION, "%DB_REVISION%" => GeoCat::DB_REVISION));
		}

		private function deleteDatabase($config, $dbName){
			$dbh = DBTools::connect($config, true, true);
			printf("Deleting database '%s'... ", $dbName);
			DBTools::query($dbh, "DROP DATABASE IF EXISTS " . $dbName);
			printf("done.\n");
		}

		private function installSQL_File($dbh, $filename, $vars = null){
			$sql_query = @fread(@fopen($filename, 'r'), @filesize($filename)) or die("\n\nCannot read file '" . $filename . "'.\n");

			if($vars != null){
				foreach($vars as $key => $value){
					$sql_query = str_replace($key, $value, $sql_query);
				}
			}

			$sql_query = remove_comments($sql_query);
			$sql_query = remove_remarks($sql_query);
			$sql_query = split_sql_file($sql_query, ';');

			foreach($sql_query as $sql){
				$query = $dbh->prepare($sql);
				$res = $query->execute();
				if($res) {
					print (".");
				}
				else{
					print("\n\nERROR while executing sql statement:\n" .$sql . "\n");
					print("\nPDOStatement::errorInfo():\n");
					$arr = $query->errorInfo();
					print_r($arr);
					print("\n");
					throw new Exception("Database error.");
				}
			}

			print (" done.\n");
		}

		private function printDatabaseInfo($config){
			try{
				$dbh = DBTools::connect($config, true, false);

				printf("\nCurrent database version:\t%s\n", $this->readDatabaseVersion($dbh, false));
				printf("Latest version:\t\t\t%s\n", GeoCat::DB_VERSION);
			}
			catch(PDOException $e){
				printf("\nUnable to get GeoCat database version...\nError: %s", $e->getMessage());
				die();
			}
		}

		private function readDatabaseVersion($dbh, $getRevision){
			$index = $getRevision ? 1 : 0;
			return DBTools::fetchNum($dbh, "SELECT db_version, db_revision FROM GeoCat LIMIT 1")[$index];
		}
	}

	return new GeoCatCLI_DBSetup();
?>
