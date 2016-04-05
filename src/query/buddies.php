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

class Buddy extends RequestInterface {

    private $dbh;

    public function __construct($parameters, $dbh){
        parent::__construct($parameters, JSONLocale::withBrowserLanguage());
        $this->dbh = $dbh;
    }

    public function handleRequest(){
        $this->handleAndSendResponseByArgsKey("task");
    }

    protected function addBuddy(){
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

    protected function removeBuddy(){
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

    protected function getBuddyList(){
      $this->requireParameters("myUsername" => null);

      if(AccountManager::isValidUsername($this->args['myUsername'])){
        $myAccId = AccountManager::getAccountIdByUserName($this->dbh, $this->args['myUsername']);
        if($myAccId == -1){
          return self::buildResponse(false, array("msg" => $locale->get("buddies.unusedusername")));
        }
        $buddyList = AccountManager::getBuddyList($this->dbh, $myAccId);
        foreach ($buddyList as $key => $value) {
          $buddyList[$key] = AccountManager::getUserNameByAccountId($this->dbh, $value);
        }
        return $buddyList['friend_id'];
      }
      return self::buildResponse(false, array("msg" => $locale->get("buddies.InvalidUsername")));
    }

}

$config = require("../config/config.php");
$loginHandler = new Login($_REQUEST, DBTools::connectToDatabase($config));
$loginHandler->handleRequest();

?>
