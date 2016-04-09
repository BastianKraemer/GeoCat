<?php

/**
 * RESTful service for GeoCat to perfrom buddy request operations
 * @package query
 */


require_once(__DIR__ . "/../app/RequestInterface.php");
require_once(__DIR__ . "/../app/DBTools.php");
require_once(__DIR__ . "/../app/AccountManager.php");
require_once(__DIR__ . "/../app/SessionManager.php");
require_once(__DIR__ . "/../app/JSONLocale.php");

class BuddyHTTPRequestHandler extends RequestInterface {

    private $dbh;

    public function __construct($parameters, $dbh){
        parent::__construct($parameters, JSONLocale::withBrowserLanguage());
        $this->dbh = $dbh;
    }

    public function handleRequest(){
        $this->handleAndSendResponseByArgsKey("task");
    }

    protected function add_buddy(){
      $this->requireParameters(array(
        "myUsername" => null,
        "buddyUsername" => null
      ));

      if(AccountManager::isValidUsername($this->args['myUsername']) && AccountManager::isValidUsername($this->args['buddyUsername'])){
        $myAccId = AccountManager::getAccountIdByUserName($this->dbh, $this->args['myUsername']);
        $buddyAccId = AccountManager::getAccountIdByUserName($this->dbh, $this->args['buddyUsername']);
        if($myAccId == -1 || $buddyAccId == -1){
          return self::buildResponse(false, array("msg" => $locale->get("buddies.unusedusername")));
        }
        AccountManager::addBuddyToAccount($this->dbh, $myAccId, $buddyAccId);
        return self::buildResponse(true, array("msg" => $locale->get("buddies.added")));
      }
      return self::buildResponse(false, array("msg" => $locale->get("buddies.InvalidUsername")));
    }

    protected function remove_buddy(){
      $this->requireParameters(array(
        "myUsername" => null,
        "buddyUsername" => null
      ));

      if(AccountManager::isValidUsername($this->args['myUsername']) && AccountManager::isValidUsername($this->args['buddyUsername'])){
        $myAccId = AccountManager::getAccountIdByUserName($this->dbh, $this->args['myUsername']);
        $buddyAccId = AccountManager::getAccountIdByUserName($this->dbh, $this->args['buddyUsername']);
        if($myAccId == -1 || $buddyAccId == -1){
          return self::buildResponse(false, array("msg" => $locale->get("buddies.unusedusername")));
        }
        AccountManager::removeBuddyFromAccount($this->dbh, $myAccId, $buddyAccId);
        return self::buildResponse(true, array("msg" => $locale->get("buddies.removed")));
      }
      return self::buildResponse(false, array("msg" => $locale->get("buddies.InvalidUsername")));
    }

	protected function buddylist(){
		$session = $this->requireLogin();

		return AccountManager::getBuddyList($this->dbh, $session->getAccountId());
		$buddyList;
    }
}

$config = require("../config/config.php");
$requestHandler = new BuddyHTTPRequestHandler($_REQUEST, DBTools::connectToDatabase($config));
$requestHandler->handleRequest();

?>
