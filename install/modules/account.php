<?php
class AccountCommand {
	//const LIST_ACCOUNTS = 0;
	const CREATE = 1;
	//const DELETE = 2;
	//const UPDATE = 3;
}

class GeoCatCLI_Account extends GeoCatCLI_Command {

	private $accCmd = null;

	private $usrName = null;
	private $email = null;
	private $pw = null;
	private $firstname = null;
	private $lastname = null;
	private $adminFlag = false;
	private $publicEmailFlag = false;

	public function getName(){return "account";}
	public function getDescription(){return "Account Management";}

	public function printHelp(){
		$out1 = array(
			"--create" => "Create a new user"
		);

		$out2 = array(
			"--username, -u [username]" => "Set username",
			"--email, -e [e-mail address][" => "Set e-mail address)",
			"--password, -pw [password]" => "Set password",
			"--first-name, -f [port]" => "Set the user's first name",
			"--last-name, -l [user]" => "Set the user's last name",
			"--public-email, -p" => "Make the user's email address public",
			"--administrator, -a" => "Set the user as administrator"
		);

		$out3 = array(
			"-r [username] [email addr] [pw]" => "Set all required fields at once",
			"-name [first name] [last name]" => "Set the user's name"
		);

			print "This tool allows you to create GeoCat accounts.\n";

			print "\nList of all operations:\n";
			GeoCatCLI::printList($out1);

			print "\nPossible parameters:\n";
			GeoCatCLI::printList($out2);

			print "\nShort alternatves:\n";
			GeoCatCLI::printList($out3);

			print "\nExamples:\n";
			print "  geocat.php account --create -u test -e test@example.com *****\n";
			print "  geocat.php account --create -r test test@example.com ***** -a -p\n";
	}

	public function parseArgs($args, $startIndex){
		for($i = $startIndex; $i < count($args); $i++){

			switch ($args[$i]) {
				/*case "--list":
					$this->accCmd = AccountCommand::LIST_ACCOUNTS;
					break;*/
				case "--create":
					$this->accCmd = AccountCommand::CREATE;
					break;

				case "--guest":
					$this->accCmd = AccountCommand::CREATE_GUEST;
					break;
				/*case "--delete":
					$this->accCmd = AccountCommand::DELETE;
					break;
				case "--update":
					$this->accCmd = AccountCommand::UPDATE;
					break;*/

				case "-r": //-required
					$this->usrName = $args[++$i];
					$this->email = $args[++$i];
					$this->pw = $args[++$i];
					break;

				case "--username":
				case "-user":
				case "-u":
					$this->usrName = $args[++$i];
					break;

				case "--email-address":
				case "-email":
				case "-e":
					$this->email = $args[++$i];
					break;

				case "--password":
				case "-pw":
				case "--pw":
					$this->pw = $args[++$i];
					break;

				case "--first-name":
				case "-f":
					$this->firstname = $args[++$i];
					break;

				case "--last-name":
				case "-l":
					$this->lastname = $args[++$i];
					break;

				case "--name":
				case "-name":
					$this->firstname = $args[++$i];
					$this->lastname = $args[++$i];
					break;

				case "--administrator":
				case "--admin":
				case "-a":
					$this->adminFlag = true;
					break;

				case "--public-email":
				case "--public":
				case "-p":
					$this->publicEmailFlag = true;
					break;

				default:
					$i += $this->cli->lookupGlobalCmd($args, $i);
			}
		}
	}

	public function exec(){

		if($this->accCmd == null){
			throw new InvalidArgumentException("Invalid command line arguments. " .
											   "Use 'geocat.php help " . $this->getName() . "' for more information.");
		}

		require_once $this->cli->home . "/app/AccountManager.php";
		require_once $this->cli->home . "/app/DBTools.php";

		$config = GeoCat::getConfig();

		$dbh = DBTools::connect($config, $this->cli->verbose, false);

		switch($this->accCmd){
			case AccountCommand::CREATE:
				if($this->usrName != null && $this->email != null && $this->pw != null){
					$accId = AccountManager::createAccount($dbh, $this->usrName, $this->pw, $this->email, $this->adminFlag, array(
							"firstname" => $this->firstname,
							"lastname" => $this->lastname,
							"public_email" => $this->publicEmailFlag ? 1 : 0
					));

					printf("Account successfully created.\n\t -> Account id: %d", $accId);
				}
				else{
					throw new InvalidArgumentException("Unable to create an account: Username, email address or password are undefined.");
				}
				break;
		}
	}
}

return new GeoCatCLI_Account();
?>
