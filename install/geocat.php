<?php

$cli = new GeoCatCLI();
$cli->run($argv);

abstract class GeoCatCLI_Command {
	protected $cli;
	public abstract function getName();
	public abstract function getDescription();

	public function init($geoCatCLI){
		$this->cli = $geoCatCLI;
	}
	public abstract function parseArgs($args, $startIndex);
	public abstract function exec();

	public abstract function printHelp();
}

class GeoCatCLI {

	public $commands = array();
	public $verbose = false;
	public $home = null;
	private $geoCatClassLoaded = false;

	public function __construct(){
		$this->includeModules();
		$this->findHomePath();
	}

	private function findHomePath(){
		$defaultPaths = array(
				__DIR__, __DIR__ . "/../src", "/src", ".", ".."
		);

		foreach($defaultPaths as $path){
			if($this->checkHomePath($path)){
				printf("Found GeoCat application in '%s'.\n", str_replace("\\", "/", $path));
				$this->setHomePath($path);
				return;
			}
		}
	}

	private function setHomePath($path){
		$this->home = $path;
		$this->loadGeoCatCore();
	}

	private function checkHomePath($path){
		if($path == null){return false;}
		return (file_exists($path . "/index.php") &&
				file_exists($path . "/config/config.php") &&
				file_exists($path . "/app/GeoCat.php") &&
				file_exists($path . "/app/DBTools.php"));
	}

	private function loadGeoCatCore(){
		if(!$this->geoCatClassLoaded){
			printf("Using GeoCat core class '%s'.\n\n", str_replace("\\", "/", $this->home . "/app/GeoCat.php"));
			require_once $this->home . "/app/GeoCat.php";
			$this->geoCatClassLoaded = true;
		}
	}

	public function includeModules(){
		foreach (glob(__DIR__ . "/modules/*.php") as $filename){
			$cmd = include $filename;

			if($cmd instanceof GeoCatCLI_Command){
				printf("Loading module '%s'...\n", $cmd->getName());
				$this->commands[$cmd->getName()] = $cmd;
				$cmd->init($this);
			}
		}

		$helpCmd = new GeoCatCLI_Help();
		$helpCmd->init($this);
		$this->commands[$helpCmd->getName()] = $helpCmd;
	}

	public function run($args){
		if(count($args) < 2){
			printf("Invalid command line option. Use '--help' for more information.\n");
			exit(1);
		}

		$cmd = $this->parseFirstArgument($args);

		if($cmd == null){

			$this->lookupGlobalCmd($args, 1);

			printf("Invalid command line option. Use '--help' for more information.\n");
			exit(1);
		}

		if(!$this->checkHomePath($this->home)){
			printf("Unable to find GeoCat installation path.\n");
			exit(1);
		}

		try{
			$this->commands[$cmd]->parseArgs($args, 2);
			$this->commands[$cmd]->exec();
		}
		catch(InvalidArgumentException $e){
			printf($e->getMessage());
		}
	}

	private function parseFirstArgument($args){
		foreach($this->commands as $key => $value){
			if(strcasecmp($key, $args[1]) == 0){
				return $key;
			}
		}
		return null;
	}

	public function lookupGlobalCmd($args, $index){
		switch($args[$index]){
			case "--verbose":
			case "-v":
				$this->verbose = true;
				return 1;
			case "--help":
			case "-?":
				$this->printHelp();
				exit(0);
			case "--home":
			case "-h":
				$this->setHomePath($args[$index + 1]);
				return 2;
			case "--config":
			case "-c":
				GeoCat::setConfigPath($args[$index + 1]);
				return 1;
		}
		throw new InvalidArgumentException(sprintf("Unknown command line option '%s'.", $args[$index]));
	}

	private function printHelp(){
		print("GeoCat command line interface\n\n");

		print("Available commands:\n");
		foreach($this->commands as $key => $value){
			$l = strlen($key);
			printf("  %s%" . (16 - $l) . "s%s\n", $key, "", $value->getDescription());
		}

		print("\n  Use 'geoccat.php help [command]' to display more information about a command.\n");

		print("\nGlobal options:\n");
		printf("  --verbose, -v \t%s\n", "Be more verbose");
		printf("  --home, -h [path]\t%s\n", "Set custom home path");
		printf("  --help, -? \t\t%s\n", "Print this help.");
	}

	public static function printList($data){
		$maxLength = 0;
		foreach($data as $key => $value){
			$l = strlen($key);
			if($l > $maxLength){$maxLength = $l;}
		}

		$maxLength += 2;

		foreach($data as $key => $value){
			$l = strlen($key);
			$val = str_replace("\n", sprintf("\n  %" . $maxLength . "s", ""), $value);
			printf("  %s%" . ($maxLength - $l) . "s%s\n", $key, "", $val);
		}
	}
}

class GeoCatCLI_Help extends GeoCatCLI_Command {

	private $helpCmd = null;
	public function getName(){return "help";}
	public function getDescription(){return "Display more information about a command";}

	public function init($geoCatCLI){
		$this->cli = $geoCatCLI;
	}
	public function parseArgs($args, $startIndex){

		for($i = $startIndex; $i < count($args); $i++){
			if(array_key_exists($args[$i], $this->cli->commands)){
				if($this->helpCmd == null){
					$this->helpCmd = $args[$i];
				}
				else{
					throw new InvalidArgumentException("Invalid usage of 'geocat.php help'.");
				}
			}
			else{
				$i += $this->cli->lookupGlobalCmd($args, $i);
			}
		}

		if($this->helpCmd == null){
			throw new InvalidArgumentException("Cannot display help: No command specified.");
		}
	}
	public function exec(){
		printf("GeoCat command line interface\n\nHelp for command '%s':\n", $this->helpCmd);
		$this->cli->commands[$this->helpCmd]->printHelp();
	}

	public function printHelp(){
		print(	"Usage of 'geocat.php help':\n" .
				"  geocat.php help [command]\n\n" .
				"Example:\n  geocat.php help setup\n");
	}
}

?>
