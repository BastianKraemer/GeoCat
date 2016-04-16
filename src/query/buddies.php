<?php

/**
 * RESTful service for GeoCat to perfrom buddy request operations
 * @package query
 */


require_once(__DIR__ . "/../app/RequestInterface.php");
require_once(__DIR__ . "/../app/DBTools.php");
require_once(__DIR__ . "/../app/AccountManager.php");
require_once(__DIR__ . "/../app/SessionManager.php");
require_once(__DIR__ . "/../app/CoordinateManager.php");
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
          return self::buildResponse(false, array("msg" => $this->locale->get("buddies.unusedusername")));
        }
        AccountManager::addBuddyToAccount($this->dbh, $myAccId, $buddyAccId);
        return self::buildResponse(true, array("msg" => $this->locale->get("buddies.added")));
      }
      return self::buildResponse(false, array("msg" => $this->locale->get("buddies.InvalidUsername")));
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
          return self::buildResponse(false, array("msg" => $this->locale->get("buddies.unusedusername")));
        }
        AccountManager::removeBuddyFromAccount($this->dbh, $myAccId, $buddyAccId);
        return self::buildResponse(true, array("msg" => $this->locale->get("buddies.removed")));
      }
      return self::buildResponse(false, array("msg" => $this->locale->get("buddies.InvalidUsername")));
    }

	protected function buddylist(){
		$session = $this->requireLogin();

		return AccountManager::getBuddyList($this->dbh, $session->getAccountId());
  }

  /**
   * updates accountinformation with geoposition of user
   * @param float lat   geoposition latitude
   * @param float long  geoposition longitude
   * @return bool       true if update was successful
   */
  protected function upload_position(){
    $session = self::requireLogin();

    $this->requireParameters(array(
      "lat" => null,
      "long" => null
    ));

    $lat = $this->args['lat'];
    $long = $this->args['long'];

    $name = $session->getUsername();

    $myPosition = AccountManager::getMyPosition($this->dbh, $session->getAccountId());
    if($myPosition == null || $myPosition <= 0){
      if(($myPosition = CoordinateManager::createCoordinate($this->dbh, $name, $lat, $long, "position of " . $session->getUsername()))  == -1){
        return self::buildResponse(false, array("msg" => $this->locale->get("buddies.InvalidPosition")));
      }
      AccountManager::updateMyPosition($this->dbh, $session->getAccountId(), $myPosition);
    } else {
      CoordinateManager::updateCoordinate($this->dbh, $myPosition, $name, $lat, $long, "position of " . $session->getUsername());
    }
    AccountManager::updateTimestamp($this->dbh, $session->getAccountId());
    return self::buildResponse(true, array("msg" => $this->locale->get("buddies.position_ok")));
  }

  /**
   * returns coordinates of current user's friends
   * complete list of data (=index):
   *  - name            username
   *  - desc            descripton of position
   *  - lat             latitude value
   *  - lon             longitude value
   *  - pos_timestamp   timestamp of position
   * @return array $coordsList with latest available data
   */
  protected function show_pos_buddies(){
    $session = self::requireLogin();
    $buddylist = AccountManager::getBuddyList($this->dbh, $session->getAccountId());
    $coordsList = null;
    foreach ($buddylist as $friend => $datalist) {
      $coordId = AccountManager::getMyPosition($this->dbh, $datalist['friend_id']);
      $coords = CoordinateManager::getCoordinateById($this->dbh, $coordId);
      if($coords != null){
        unset($coords->coord_id);
        $coords->pos_timestamp = $datalist['pos_timestamp'];
        $coordsList[] = $coords;
      }
    }

    if(!empty($coordsList)){
      return self::buildResponse(true, $coordsList);
    }
    return self::buildResponse(false, array("msg" => $this->locale->get("buddies.no_data_available")));
  }

  /**
   * method to search for username, firstname or lastname by a specific search text
   * searching options:
   * - asterisk at beginning or end means 'zero or more unknown characters'
   * - asterisk within a string means 'one unknown character'
   * @param String  searchtext      search pattern
   * @return array $result which contains username and flag 'isFriend' (bool) which tells if
   * a user is a friend of current user
   */
  protected function search_buddy(){
    $session = self::requireLogin();
    $this->requireParameters(array(
      "searchtext" => self::defaultTextRegEx(1, 64)
    ));
    $sourcetext = $this->args['searchtext'];
    $sourcelength = strlen($sourcetext);
    $pos = 0;

    if($sourcetext[0] === "*"){
      $sourcetext = substr_replace($sourcetext, "%", 0, 1);
    }

    if ($sourcetext[$sourcelength - 1] === "*"){
      $sourcetext = substr_replace($sourcetext, "%", $sourcelength - 1, 1);
    }

    while(($pos = strpos($sourcetext, "_", $pos)) !== false){
      $sourcetext = substr_replace($sourcetext, "\_", $pos, 1);
      $pos += 2;
    }

    $pos = 0;
    while(($pos = strpos($sourcetext, "*", $pos)) !== false){
      $sourcetext = substr_replace($sourcetext, "_", $pos, 1);
      $pos++;
    }

    $result = AccountManager::find_buddy($this->dbh, $sourcetext);

    foreach ($result as $user => $arraydata) {
      if($result[$user]['username'] == $session->getUsername()){
        unset($result[$user]);
        continue;
      }
      $isFriend = AccountManager::check_buddy($this->dbh, $session->getAccountId(), AccountManager::getAccountIdByUserName($this->dbh, $result[$user]['username']));
      $result[$user]['isFriend'] = $isFriend;
    }
    $result = array_values($result);

    if(!empty($result)){
      return self::buildResponse(true, $result);
    }
    return self::buildResponse(false, array("msg" => $this->locale->get("buddies.no_data_available")));
  }

}

$config = require("../config/config.php");
$requestHandler = new BuddyHTTPRequestHandler($_REQUEST, DBTools::connectToDatabase($config));
$requestHandler->handleRequest();

?>
