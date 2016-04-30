<?php

/**
 * RESTful service for GeoCat to perfrom login and logout operations
 * @package query
 */


require_once(__DIR__ . "/../app/RequestInterface.php");
require_once(__DIR__ . "/../app/DBTools.php");
require_once(__DIR__ . "/../app/AccountManager.php");
require_once(__DIR__ . "/../app/SessionManager.php");
require_once(__DIR__ . "/../app/JSONLocale.php");

/**
 * This class provides an REST interface to sign in into GeoCat
 *
 * To interact wih this class you have to send a HTTP request to '/query/login.php'
 */
class LoginHTTPRequestHandler extends RequestInterface {

	/**
	 * Database handler
	 * @var PDO
	 */
    private $dbh;

    /**
     * Create a LoginHTTPRequestHandler instance
     * @param String[] HTTP parameters (most likely those of $_POST)
     * @param PDO $dbh Database handler
     */
    public function __construct($parameters, $dbh){
        parent::__construct($parameters, JSONLocale::withBrowserLanguage());
        $this->dbh = $dbh;
    }

    /**
     * Handles the request by using the value from the 'task' parameter
     */
    public function handleRequest(){
        $this->handleAndSendResponseByArgsKey("task");
    }

    /**
     * Task: 'login'
     *
     * Login into GeoCat using your username and your password
     *
     * Required HTTP parameters:
     * - <b>user</b>
     * - <b>password</b>
     *
     * Optional parameters:
     * - <b>keep_signed_in</b> Create a token to keep signed in across multiple sessions
     */
    protected function login(){
        $this->requireParameters(array(
			"user" => self::defaultTextRegEx(1, 64),
			"password" => self::defaultTextRegEx(1, 64)
		));

		$this->verifyOptionalParameters(array(
			"keep_signed_in" => "/^(true|false)$/"
		));

        $accId = AccountManager::getAccountIdByUserName($this->dbh, $this->args['user']);
        if($accId > 0){
            $session = new SessionManager();
            if($session->login($this->dbh, $accId, $this->args['password'])){
                $session->deleteCookie($this->dbh);

                if($this->hasParameter("keep_signed_in")){
                    if($this->args["keep_signed_in"] == "true"){
                        $session->createLoginToken($this->dbh);
                    }
                }
                return self::buildResponse(true, array('username' => $this->args['user']));
            }
        }
        return self::buildResponse(false);
    }

    /**
     * Task: 'login_cookie'
     *
     * Login to GeoCat using your "keep_signed_in" token
     */
    protected function login_cookie(){
        $session = new SessionManager();
        if($session->verifyCookie($this->dbh, $this->args['cookie'])){
            return self::buildResponse(true, array('username' => $session->getUsername()));
        }
        return self::buildResponse(false);
    }

    /**
     * Task: 'logout'
     *
     * Logout from GeoCat. To delete your "keep_signed_in" token as well, sign in WITHOUT the "keep_signed_in" again.
     */
	protected function logout(){
		$session = $this->requireLogin();
		$session->logout();
		return self::buildResponse(true);
	}
}

$loginHandler = new LoginHTTPRequestHandler($_POST, DBTools::connectToDatabase());
$loginHandler->handleRequest();

?>
