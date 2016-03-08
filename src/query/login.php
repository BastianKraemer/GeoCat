<?php

require_once(__DIR__ . "/../app/RequestInterface.php");
require_once(__DIR__ . "/../app/DBTools.php");
require_once(__DIR__ . "/../app/AccountManager.php");
require_once(__DIR__ . "/../app/SessionManager.php");
require_once(__DIR__ . "/../app/JSONLocale.php");

class Login extends RequestInterface {
    
    private $dbh;
    
    public function __construct($parameters, $dbh){
        parent::__construct($parameters, JSONLocale::withBrowserLanguage());
        $this->dbh = $dbh; 
    }
    
    public function handleRequest(){
        $this->handleAndSendResponseByArgsKey("task");
    }
    
    protected function login(){
        $this->requireParameters(array(
				"user" => self::defaultTextRegEx(1, 64),
                "password" => self::defaultTextRegEx(1, 64)
			));
        $accId = AccountManager::getAccountIdByUserName($this->dbh, $this->args['user']);
        if($accId > 0){
            $session = new SessionManager();
            if($session->login($this->dbh, $accId, $this->args['password'])){
                $session->deleteCookie($this->dbh);
                if($this->args['checkbox'] == "true"){
                    $session->createLoginToken($this->dbh);
                }
                return self::buildResponse(true, array('username' => $this->args['user'])); 
            }
        }
        return self::buildResponse(false);
    }
    
    protected function login_cookie(){
        $session = new SessionManager();
        if($session->verifyCookie($this->dbh, $this->args['cookie'])){
            return self::buildResponse(true, array('username' => $session->getUsername()));
        }
        return self::buildResponse(false);
    }
    
}

$config = require("../config/config.php");
$loginHandler = new Login($_REQUEST, DBTools::connectToDatabase($config));
$loginHandler->handleRequest();

?>
