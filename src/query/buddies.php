<?php
/* GeoCat - Geocaching and -tracking application
 * Copyright (C) 2016 Raphael Harzer
 *
 * buddies.php
 *
 * This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

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

/**
 * This class provides an REST interface to add and remove buddies
 *
 * To interact wih this class you have to send a HTTP request '/query/buddies.php'
 *
 * Note: You have to be signed in for every interaction with this service.
 */
class BuddyHTTPRequestHandler extends RequestInterface {

	/**
	 * Database handler
	 * @var PDO
	 */
    private $dbh;

    /**
     * Create a BuddyHTTPRequestHandler instance
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
     * Task: 'add_buddy'
     *
     * Add a buddy to your account.
     *
     * You have to be signed in to use this feature.
     *
     * Required HTTP parameters:
     * - <b>username</b>
     */
    protected function add_buddy(){
      $this->requireParameters(array(
        "username" => null
      ));

      $session = $this->requireLogin();

      if(AccountManager::isValidUsername($this->args['username'])){
        $buddyAccId = AccountManager::getAccountIdByUserName($this->dbh, $this->args['username']);

        if($buddyAccId == -1){
          return self::buildResponse(false, array("msg" => $this->locale->get("buddies.unusedusername")));
        }

        AccountManager::addBuddyToAccount($this->dbh, $session->getAccountId(), $buddyAccId);

        return self::buildResponse(true, array("msg" => $this->locale->get("buddies.added")));
      }
      return self::buildResponse(false, array("msg" => $this->locale->get("buddies.InvalidUsername")));
    }

    /**
     * Task: 'remove_buddy'
     *
     * Remove a buddy from your account.
     *
     * You have to be signed in to use this feature.
     *
     * Required HTTP parameters:
     * - <b>username</b>
     */
    protected function remove_buddy(){
      $this->requireParameters(array(
        "username" => null
      ));

      $session = $this->requireLogin();

      if(AccountManager::isValidUsername($this->args['username'])){
        $buddyAccId = AccountManager::getAccountIdByUserName($this->dbh, $this->args['username']);
        if($buddyAccId == -1){
          return self::buildResponse(false, array("msg" => $this->locale->get("buddies.unusedusername")));
        }
        AccountManager::removeBuddyFromAccount($this->dbh, $session->getAccountId(), $buddyAccId);
        return self::buildResponse(true, array("msg" => $this->locale->get("buddies.removed")));
      }
      return self::buildResponse(false, array("msg" => $this->locale->get("buddies.InvalidUsername")));
    }

	/**
	 * Task: 'buddylist'
	 *
	 * Get the list of your buddies from the server
	 */
	protected function buddylist(){
		$session = $this->requireLogin();
		return self::buildResponse(true, AccountManager::getBuddyInformation($this->dbh, $session->getAccountId()));
	}

  /**
   * Task: 'upload_position'
   *
   * Update your Account Information with your current GPS position
   *
   * Required HTTP parameters:
   * - <b>lat</b> Your latitude
   * - <b>lon</b> Your longitude
   */
  protected function upload_position(){
    $session = self::requireLogin();

    $this->requireParameters(array(
      "lat" => "/-?[0-9]{1,9}[,\.][0-9]+/",
      "lon" => "/-?[0-9]{1,9}[,\.][0-9]+/"
    ));

    $lat = $this->args['lat'];
    $long = $this->args['lon'];

    $name = $session->getUsername();

    $myPosition = AccountManager::getMyPosition($this->dbh, $session->getAccountId());
    $desc = "Position of user '" . $session->getUsername() . "'";
    if($myPosition == null || $myPosition <= 0){
      if(($myPosition = CoordinateManager::createCoordinate($this->dbh, $name, $lat, $long, $desc))  == -1){
        return self::buildResponse(false, array("msg" => $this->locale->get("buddies.InvalidPosition")));
      }
      AccountManager::updateMyPosition($this->dbh, $session->getAccountId(), $myPosition);
    } else {
      CoordinateManager::updateCoordinate($this->dbh, $myPosition, $name, $lat, $long, $desc);
    }
    AccountManager::updateTimestamp($this->dbh, $session->getAccountId());
    return self::buildResponse(true, array("msg" => $this->locale->get("buddies.position_ok")));
  }

	/**
	 * Task: 'clear_position'
	 *
	 * Removes your position from the server
	 */
	protected function clear_position(){
		$session = $this->requireLogin();
		$status = AccountManager::clearPosition($this->dbh, $session->getAccountId());

		return self::buildResponse(true, array("msg" => $this->locale->get($status == 1 ? "tracking.cleared" : "tracking.already_cleared")));
	}

	/**
	 * Task: 'get_buddy_positions'
	 *
	 * Receive the GPS positions of your buddies
	 *
	 * List of the respone data (=index):
	 * - name            username
	 * - desc            descripton of position
	 * - lat             latitude value
	 * - lon             longitude value
	 * - timestamp   timestamp of position
	 */
  protected function get_buddy_positions(){
    $session = self::requireLogin();
    $buddylist = AccountManager::getBuddyList($this->dbh, $session->getAccountId());
    $coordsList = null;

	foreach ($buddylist as $friend => $datalist) {
		if(AccountManager::isFriendOf($this->dbh, $session->getAccountId(), $datalist["friend_id"])){
			$coordId = AccountManager::getMyPosition($this->dbh, $datalist['friend_id']);
			$coords = CoordinateManager::getCoordinateById($this->dbh, $coordId);
			if($coords != null){
				unset($coords->coord_id);
				$coords->timestamp = $datalist['pos_timestamp'];
				$coordsList[] = $coords;
			}
		}
	}

    if(!empty($coordsList)){
      return self::buildResponse(true, array("coords" => $coordsList));
    }
    return self::buildResponse(false, array("msg" => $this->locale->get("buddies.no_data_available")));
  }

  /**
   * Task: 'search_buddy'
   *
   * Search for buddies
   *
   * method to search for username, firstname or lastname by a specific search text
   * searching options:
   * - asterisk at beginning or end means 'zero or more unknown characters'
   * - asterisk within a string means 'one unknown character'
   *
   * Response:
   * The response which contains the username and a flag 'isFriend' (bool) which tells if
   * a user is a friend of the current user
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
      $sourcetext = substr_replace($sourcetext, "%", $pos, 1);
      $pos++;
    }

    $result = AccountManager::find_buddy($this->dbh, $sourcetext);

    foreach ($result as $user => $arraydata) {
      if($result[$user]['username'] == $session->getUsername()){
        unset($result[$user]);
        continue;
      }

      $isFriend = AccountManager::isFriendOf($this->dbh, AccountManager::getAccountIdByUserName($this->dbh, $result[$user]['username']), $session->getAccountId());

      $result[$user]['isFriend'] = $isFriend;
    }
    $result = array_values($result);

    if(!empty($result)){
      return self::buildResponse(true, array("matches" => $result));
    }
    return self::buildResponse(false, array("msg" => $this->locale->get("buddies.no_data_available")));
  }

}

$requestHandler = new BuddyHTTPRequestHandler($_REQUEST, DBTools::connectToDatabase());
$requestHandler->handleRequest();

?>
