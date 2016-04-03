<?php
	/*	GeoCat - Geocaching and -Tracking platform
	 Copyright (C) 2016 Bastian Kraemer

	 challenge.php

	 This program is free software: you can redistribute it and/or modify
	 it under the terms of the GNU General Public License as published by
	 the Free Software Foundation, either version 3 of the License, or
	 (at your option) any later version.

	 This program is distributed in the hope that it will be useful,
	 but WITHOUT ANY WARRANTY; without even the implied warranty of
	 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	 GNU General Public License for more details.

	 You should have received a copy of the GNU General Public License
	 along with this program.  If not, see <http://www.gnu.org/licenses/>.
	 */

	/**
	 * RESTful service for GeoCat to deal with challenges and teams
	 * @package query
	 */

	require_once(__DIR__ . "/../app/RequestInterface.php");
	require_once(__DIR__ . "/../app/DBTools.php");
	require_once(__DIR__ . "/../app/challenge/ChallengeManager.php");
	require_once(__DIR__ . "/../app/challenge/TeamManager.php");
	require_once(__DIR__ . "/../app/JSONLocale.php");

	/**
	 * This class provides an REST interface to interact with challenges
	 *
	 * To interact wih this class you have to send a HTTP request to this file
	 */
	class ChallengeHTTPRequestHandler extends RequestInterface {

		/**
		 * Database handler
		 * @var PDO
		 */
		private $dbh;

		/**
		 * Create a ChallengeHTTPRequestHandler instance
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
		 * Task: 'create_challenge'
		 *
		 * Required HTTP parameters:
		 * - <b>name</b>
		 *
		 * Optional parameters:
		 * - <b>desc</b> Challenge description
		 * - <b>type</b> ('default' or 'ctf')
		 * - <b>is_public</b> ('0': no, 1: yes)
		 * - <b>predefined_teams</b> ('0': no, 1: yes)
		 * - <b>max_teams</b> (use -1 for no limit)
		 * - <b>max_team_members</b>
		 */
		protected function create_challenge(){

			$session = $this->requireLogin();
			$this->requireParameters(array(
				"name" => self::defaultTextRegEx(1, 64),
			));

			$this->verifyOptionalParameters(array(
				"desc" => self::defaultTextRegEx(0, 512),
				"type" => "/^(default|ctf)$/i",
				"is_public" => "/[0-1]/",
				"predefined_teams" =>  "/[0-1]/",
				"max_teams" => "/-?[0-9]+/",
				"max_team_members" => "/[0-9]+/"
			));

			$this->assignOptionalParameter("desc", "");
			$this->assignOptionalParameter("type", "default");
			$this->assignOptionalParameter("is_public", 0);
			$this->assignOptionalParameter("predefined_teams", 0);
			$this->assignOptionalParameter("max_teams", 4);
			$this->assignOptionalParameter("max_team_members", 4);

			$challengeType = ChallengeType::DefaultChallenge;
			if($this->args["type"] == "ctf"){$challengeType = ChallengeType::CaptureTheFlag;}

			$id = ChallengeManager::createChallenge($this->dbh, $this->args["name"], $challengeType, $session->getAccountId(), $this->args["desc"],
													$this->args["is_public"], $this->args["predefined_teams"], $this->args["max_teams"],  $this->args["max_team_members"]);

			return self::buildResponse(true, array("sessionkey" => $id));
		}

		/*
		 * ====================================================================
		 * 	Challenge information page
		 * ====================================================================
		 */

		/**
		 * Task: 'modify'
		 *
		 * This will modify any information of a challenge
		 *
		 * Required HTTP parameters:
		 * - <b>challenge</b> The session key of the challenge
		 *
		 * Optional parameters:
		 * - <b>name</b> Challenge description
		 * - <b>description</b> ('default' or 'ctf')
		 * - <b>is_public</b> ('0': no, 1: yes)
		 * - <b>start_time</b>
		 * - <b>end_time</b>
		 * - <b>predefined_teams</b> ('0': no, 1: yes)
		 * - <b>max_teams</b> (use -1 for no limit)
		 * - <b>max_team_members</b>
		 */
		protected function modify(){
			$session = $this->requireLogin();

			$this->requireParameters(array(
					"challenge" => "/^[A-Za-z0-9]{4,16}$/"
			));

			$challengeId = ChallengeManager::getChallengeIdBySessionKey($this->dbh, $this->args["challenge"]);
			$this->requireChallengeOwner($challengeId, $session);

			// This parameters can be modified at any time
			$optionalArgs1 = array(
				"name" => self::defaultTextRegEx(1, 64),
				"description" => self::defaultTextRegEx(0, 512, true),
				"is_public" => "/^[0-1]$/",
			);

			// This parameters can be modified before a challenge is enabled
			$optionalArgs2 = array(
					"type_id" => "/[0-" . ChallengeManager::CHALLENGE_TYPE_ID_MAX . "]/i",
					"start_time" => $this::defaultTimeRegEx(),
					"end_time" => null,
					"predefined_teams" =>  "/[0-1]/",
					"max_teams" => "/-?[0-9]+/",
					"max_team_members" => "/[0-9]+/"
			);

			$this->verifyOptionalParameters($optionalArgs1);
			$this->verifyOptionalParameters($optionalArgs2);

			if($this->hasParameter("end_time")){
				if($this->args["end_time"] != null){
					if(!preg_match($this::defaultTimeRegEx(), $this->args["end_time"])){
						return self::buildResponse(false, array(msg => sprintf($this->locale->get("query.generic.invalid_value"), "end_time")));
					}
				}
			}

			$isEnabled = ChallengeManager::isChallengeEnabled($this->dbh, $challengeId);

			foreach ($optionalArgs1 as $key => $value){
				if($this->hasParameter($key)){
					ChallengeManager::updateSingleValue($this->dbh, $challengeId, $key, $this->args[$key]);
				}
			}

			foreach ($optionalArgs2 as $key => $value){
				if($this->hasParameter($key)){
					if($isEnabled){
						return self::buildResponse(false, array(msg => "You cannot update the following values on an enabled challenge: ". $key));
					}
					else{
						if($key == "type_id"){
							ChallengeManager::updateSingleValue($this->dbh, $challengeId, "challenge_type_id", $this->args[$key]);
						}
						else{
							ChallengeManager::updateSingleValue($this->dbh, $challengeId, $key, $this->args[$key] == null ? null : $this->args[$key]);
						}
					}
				}
			}

			return self::buildResponse(true);
		}

		/**
		 * Task: 'update_cache
		 *
		 * This will create or update a cache
		 *
		 * Required HTTP parameters:
		 * - <b>challenge</b> The session key of the challenge
		 * - <b>ccid</b> The 'challenge coord id'
		 * - <b>name</b> Name of the chache
		 * - <b>lat</b> Latitude
		 * - <b>lon</b>
		 * - <b>priority</b> A number between 0 and 999
		 * - <b>hint</b>
		 * - <b>code</b>
		 */
		protected function update_cache(){
			$session = $this->requireLogin();

			$this->requireParameters(array(
				"challenge" => "/^[A-Za-z0-9]{4,16}$/",
				"ccid" => null,
				"name" => $this->defaultTextRegEx(1, 64),
				"lat" => "/-?[0-9]{1,9}[,\.][0-9]+/",
				"lon" => "/-?[0-9]{1,9}[,\.][0-9]+/",
				"priority" => "/[0-9]{1,3}/",
				"hint" => $this->defaultTextRegEx(0, 256),
				"code" => $this->defaultTextRegEx(0, 256),
			));

			$challengeId = ChallengeManager::getChallengeIdBySessionKey($this->dbh, $this->args["challenge"]);
			$this->requireChallengeOwner($challengeId, $session);

			require_once(__DIR__ . "/../app/CoordinateManager.php");
			require_once(__DIR__ . "/../app/challenge/ChallengeCoord.php");

			if($this->args["priority"] == 0){
				if(ChallengeCoord::countCoordsOfChallenge($this->dbh, $challengeId, true) > 0){
					if($this->args["ccid"] == null || ChallengeCoord::getPriority0Coord($this->dbh, $challengeId)[0] != $this->args["ccid"]){
						return self::buildResponse(false, array("msg" => "You cannot define multiple coordinates with a priority of '0'."));
					}
				}
			}

			$this->setNullIfEmpty("code");
			$this->setNullIfEmpty("hint");

			if($this->args["ccid"] == null){
				// New coordinate
				$coordId = CoordinateManager::createCoordinate($this->dbh, $this->args["name"], $this->args["lat"], $this->args["lon"], "");

				if($coordId == -1){
					return self::buildResponse(false, array("msg" => "Internal server error: Unable to create coordinate."));
				}
				$ccid = ChallengeCoord::create($this->dbh, $challengeId, $coordId, $this->args["priority"], $this->args["hint"], $this->args["code"]);
			}
			else{
				// Update existing coord
				if(!preg_match("/[0-9]+/", $this->args["ccid"])){
					return self::buildResponse(false, array("msg" => "Parameter 'ccid' is not a valid integer."));
				}

				// Verify that the coordinate belongs to the challenge
				$cIdOfCoord = ChallengeCoord::getChallengeOfCoordinate($this->dbh, $this->args["ccid"]);
				$coordId = ChallengeCoord::getCoordinate($this->dbh, $this->args["ccid"]);

				if($cIdOfCoord == -1 || $coordId == -1){
					return self::buildResponse(false, array("msg" => "Error: Coordinate does not exist."));
				}
				else if($cIdOfCoord != $challengeId){
					return self::buildResponse(false, array("msg" => "The coordinate is not part this challenge."));
				}

				CoordinateManager::updateCoordinate($this->dbh, $coordId, $this->args["name"], $this->args["lat"], $this->args["lon"], "");
				ChallengeCoord::update($this->dbh, $this->args["ccid"], $this->args["priority"], $this->args["hint"], $this->args["code"]);
			}
			return self::buildResponse(true);
		}

		/**
		 * Task: 'remove_cache'
		 *
		 * Removes a cache from a challenge
		 *
		 * Required HTTP parameters:
		 * - <b>challenge</b> The session key of the challenge
		 * - <b>ccid</b> The 'challenge coord id'
		 */
		protected function remove_cache(){
			$session = $this->requireLogin();

			$this->requireParameters(array(
					"challenge" => "/^[A-Za-z0-9]{4,16}$/",
					"ccid" => "/[0-9]+/"
			));

			$challengeId = ChallengeManager::getChallengeIdBySessionKey($this->dbh, $this->args["challenge"]);
			$this->requireChallengeOwner($challengeId, $session);

			require_once __DIR__ . "/../app/CoordinateManager.php";
			require_once __DIR__ . "/../app/challenge/ChallengeCoord.php";
			require_once __DIR__ . "/../app/challenge/Checkpoint.php";

			// This should never delete any rows (because by default there can't be a checkpoint for a cache in a not enabled challenge)
			Checkpoint::clearCheckpointsOfChallengeCoord($this->dbh, $this->args["ccid"]);

			$coordId = ChallengeCoord::getCoordinate($this->dbh, $this->args["ccid"]);
			ChallengeCoord::remove($this->dbh, $this->args["ccid"]);
			CoordinateManager::tryToRemoveCooridate($this->dbh, $coordId);

			return self::buildResponse(true);
		}

		/**
		 * Task: 'enable'
		 *
		 * This will enable a challenge
		 *
		 * Required HTTP parameters:
		 * - <b>challenge</b> The session key of the challenge
		 */
		protected function enable(){
			$session = $this->requireLogin();

			$this->requireParameters(array(
					"challenge" => "/^[A-Za-z0-9]{4,16}$/"
			));

			$challengeId = ChallengeManager::getChallengeIdBySessionKey($this->dbh, $this->args["challenge"]);
			$this->requireChallengeOwner($challengeId, $session);

			require_once __DIR__ . "/../app/challenge/ChallengeCoord.php";

			if(ChallengeCoord::countCoordsOfChallenge($this->dbh, $challengeId, false) == 0){
				return self::buildResponse(false, array("msg" => "A challenge needs at least one cache to be enabled"));
			}
			ChallengeManager::setEnabled($this->dbh, $challengeId, true);

			return self::buildResponse(true);
		}

		/**
		 * Task: 'reset'
		 *
		 * This resets a challenge. After this all teams and stats of this challenge are removed.
		 *
		 * Required HTTP parameters:
		 * - <b>challenge</b> The session key of the challenge
		 */
		protected function reset(){
			$session = $this->requireLogin();

			$this->requireParameters(array(
					"challenge" => "/^[A-Za-z0-9]{4,16}$/"
			));

			$challengeId = ChallengeManager::getChallengeIdBySessionKey($this->dbh, $this->args["challenge"]);
			$this->requireChallengeOwner($challengeId, $session);

			ChallengeManager::resetChallenge($this->dbh, $challengeId);
			ChallengeManager::setEnabled($this->dbh, $challengeId, false);

			return self::buildResponse(true);
		}

		/**
		 * Task: 'delete'
		 *
		 * This deletes a challenge completely
		 *
		 * Required HTTP parameters:
		 * - <b>challenge</b> The session key of the challenge
		 */
		protected function delete(){
			$session = $this->requireLogin();

			$this->requireParameters(array(
					"challenge" => "/^[A-Za-z0-9]{4,16}$/"
			));

			$challengeId = ChallengeManager::getChallengeIdBySessionKey($this->dbh, $this->args["challenge"]);
			$this->requireChallengeOwner($challengeId, $session);

			ChallengeManager::deleteChallenge($this->dbh, $challengeId);

			return self::buildResponse(true);
		}

		/*
		 * ====================================================================
		 * 	Challenge Browser
		 * ====================================================================
		 */

		/**
		 * Task: 'get_challenges'
		 *
		 * Returns a list of all public challenges
		 *
		 * Optional HTTP parameters:
		 * - <b>limit</b>
		 * - <b>offset</b>
		 * - <b>type</b>: Can be 'public', 'own' or 'joined'
		 */
		protected function get_challenges(){

			$types = array("public", "own", "joined");

			$this->verifyOptionalParameters(array(
					"type" => "/^(public|own|joined)$/",
					"limit" => "/^[0-9]+$/",
					"offset" => "/^[0-9]+$/"
			));

			$this->assignOptionalParameter("type", "public");
			$this->assignOptionalParameter("limit", 10);
			$this->assignOptionalParameter("offset", 0);

			switch($this->args["type"]){
				case "public":
					return ChallengeManager::getPublicChallengs($this->dbh, $this->args["limit"], $this->args["offset"]);
				case "own":
					return ChallengeManager::getMyChallenges($this->dbh, $this->requireLogin(), $this->args["limit"], $this->args["offset"]);
				case "joined":
					return ChallengeManager::getParticipatedChallenges($this->dbh, $this->requireLogin(), $this->args["limit"], $this->args["offset"]);
			}
		}

		/**
		 * Task: 'get_teams'
		 *
		 * Returns the team list of a challenge (this requires that the user is signed in)
		 *
		 * Required HTTP parameters:
		 * - <b>challenge</b> The session key of the challenge
		 */
		protected function get_teams(){
			$session = $this->requireLogin();

			$this->requireParameters(array(
					"challenge" => "/^[A-Za-z0-9]{4,16}$/"
			));

			$challengeId = ChallengeManager::getChallengeIdBySessionKey($this->dbh, $this->args["challenge"]);

			return ChallengeManager::getTeams($this->dbh, $challengeId);
		}

		/**
		 * Task: 'join_team'
		 *
		 * This function can be used to join a team.
		 * This requires a valid login.
		 *
		 * Required HTTP parameters:
		 * - <b>challenge</b> The session key of the challenge
		 * - <b>team_id</b> The team the user will join
		 *
		 * @throws InvalidArgumentException if the user can't join this team
		 */
		protected function join_team(){
			$session = $this->requireLogin();

			$this->requireParameters(array(
					"challenge" => "/^[A-Za-z0-9]{4,16}$/",
					"team_id" => "/[0-9]+/"
			));

			$this->verifyOptionalParameters(array(
					"code" => self::defaultTextRegEx(0,16)
			));
			$this->assignOptionalParameter("code", null);

			$challengeId = ChallengeManager::getChallengeIdBySessionKey($this->dbh, $this->args["challenge"]);

			$this->requireEnabledChallenge($challengeId);

			TeamManager::joinTeam($this->dbh, $this->args["team_id"], $session->getAccountId(), $this->args["code"]);

			return self::buildResponse(true);
		}

		/**
		 * Task: 'leave_team'
		 *
		 * This function can be used to leave a team.
		 * This requires a valid login.
		 *
		 * Required HTTP parameters:
		 * - <b>challenge</b> The session key of the challenge
		 * - <b>team_id</b> The team the user will leave
		 *
		 * @throws InvalidArgumentException if the user can't leave the team
		 */
		protected function leave_team(){
			$session = $this->requireLogin();

			$this->requireParameters(array(
					"challenge" => "/^[A-Za-z0-9]{4,16}$/",
					"team_id" => "/[0-9]+/"
			));

			$challengeId = ChallengeManager::getChallengeIdBySessionKey($this->dbh, $this->args["challenge"]);

			$this->requireEnabledChallenge($challengeId);

			TeamManager::leaveTeam($this->dbh, $this->args["team_id"], $session->getAccountId());
			return self::buildResponse(true);
		}

		/**
		 * Task: 'create_team'
		 *
		 * Create a new team - this requires a valid login.
		 *
		 * Required HTTP parameters:
		 * - <b>challenge</b> The session key of the challenge
		 * - <b>name</b> The name of the new team
		 *
		 * @throws InvalidArgumentException if the operation failed
		 */
		protected function create_team(){

			$session = $this->requireLogin();

			$this->requireParameters(array(
					"challenge" => "/^[A-Za-z0-9]{4,16}$/",
					"name" => self::defaultTextRegEx(1, 32)
			));

			$challengeId = ChallengeManager::getChallengeIdBySessionKey($this->dbh, $this->args["challenge"]);

			if(!ChallengeManager::challengeExists($this->dbh, $challengeId)){
				throw new InvalidArgumentException($this->locale->get("query.challenge.challenge_does_not_exist"));
			}

			if(TeamManager::teamWithNameExists($this->dbh, $challengeId, $this->args["name"])){
				throw new InvalidArgumentException($this->locale->get("query.challenge.teamname_in_use"));
			}

			if($this->hasParameter("predefined_team")){
				$this->requireChallengeOwner($challengeId, $session);
			}

			// Check optional parameters
			$this->verifyOptionalParameters(array(
					"color" => "/^#[A-Fa-f0-9]{6}$/",
					"code" => self::defaultTextRegEx(0,16),
					"predefined_team" => "/^(true|false|0|1)$/"
			));

			$this->assignOptionalParameter("color", "0xFF0000"); //TODO: randomize this value
			$this->assignOptionalParameter("code", null);
			$this->assignOptionalParameter("predefined_team", false);

			$predefTeamVal = false;
			if(strcasecmp($this->args["predefined_team"], "true") == 0 || strcasecmp($this->args["predefined_team"], "1") == 0){
				$predefTeamVal = true;
			}

			$id = TeamManager::createTeam($this->dbh, $challengeId, $this->args["name"], $this->args["color"], $predefTeamVal, $this->args["code"]);

			return self::buildResponse(true, array("team_id" => $id));
		}

		/**
		 * Task: 'delete_team'
		 *
		 * Delete a team - this requires a valid login.
		 *
		 * Required HTTP parameters:
		 * - <b>challenge</b> The session key of the challenge
		 * - <b>team_id</b> The team that will be deleted
		 *
		 * @throws InvalidArgumentException if the operation failed
		 */
		protected function delete_team(){
			$session = $this->requireLogin();

			$this->requireParameters(array(
					"challenge" => "/^[A-Za-z0-9]{4,16}$/",
					"team_id" => "/[0-9]+/"
			));

			$challengeId = ChallengeManager::getChallengeIdBySessionKey($this->dbh, $this->args["challenge"]);

			$this->requireEnabledChallenge($challengeId);
			$this->requireChallengeOwner($challengeId, $session);

			TeamManager::deleteTeam($this->dbh, $this->args["team_id"]);

			return self::buildResponse(true);
		}

		// ================ Challenge Info ==========================

		/**
		 * Task: 'about'
		 *
		 * Returns detailed information about a challenge
		 *
		 * Required HTTP parameters:
		 * - <b>challenge</b> The session key of the challenge
		 */
		protected function about(){

			$this->requireParameters(array(
					"challenge" => "/^[A-Za-z0-9]{4,16}$/"
			));

			$challengeId = ChallengeManager::getChallengeIdBySessionKey($this->dbh, $this->args["challenge"]);

			if($challengeId == -1){
				return self::buildResponse(false, array("msg" => "Invalid session key."));
			}

			$info = ChallengeManager::getChallengeInformation($this->dbh, $challengeId);

			$info["is_enabled"] = (ChallengeManager::isChallengeEnabled($this->dbh, $challengeId) ? 1 : 0);
			$info["team_list"] = ChallengeManager::getTeamsAndMemberCount($this->dbh, $challengeId);

			require_once(__DIR__ . "/../app/SessionManager.php");
			$session = new SessionManager();
			if($session->isSignedIn()){
				$info["your_team"] = TeamManager::getTeamOfUser($this->dbh, $challengeId, $session->getAccountId());
			}
			else{
				$info["your_team"] = -1;
			}
			return self::buildResponse(true, $info);
		}

		/**
		 * Task: 'get_memberlist'
		 *
		 * Returns the list of challenge members for this team
		 *
		 * Required HTTP parameters:
		 * - <b>challenge</b> The session key of the challenge
		 * - <b>teamid</b>
		 */
		protected function get_memberlist(){

			$this->requireParameters(array(
					"challenge" => "/^[A-Za-z0-9]{4,16}$/",
					"teamid" => "/[0-9]+/"
			));

			$challengeId = ChallengeManager::getChallengeIdBySessionKey($this->dbh, $this->args["challenge"]);

			if($challengeId == -1){
				return self::buildResponse(false, array("msg" => "Invalid session key."));
			}

			$info["memberlist"] = ChallengeManager::getTeamlistById($this->dbh, $this->args["teamid"]);

			return self::buildResponse(true, $info);
		}

		/**
		 * Task: 'coord_info'
		 *
		 * Returns detailed information about the caches of a challenge
		 * This requires a valid login.
		 *
		 * Required HTTP parameters:
		 * - <b>challenge</b> The session key of the challenge
		 */
		protected function coord_info(){

			$this->requireParameters(array(
					"challenge" => "/^[A-Za-z0-9]{4,16}$/"
			));

			$challengeId = ChallengeManager::getChallengeIdBySessionKey($this->dbh, $this->args["challenge"]);

			if($challengeId == -1){
				return self::buildResponse(false, array("msg" => "Invalid session key."));
			}

			$isOwner = false;

			require_once(__DIR__ . "/../app/SessionManager.php");
			$session = new SessionManager();

			if($session->isSignedIn()){
				$isOwner = (ChallengeManager::getOwner($this->dbh, $challengeId) == $session->getAccountId());
			}

			if(!$isOwner){
				$this->requireEnabledChallenge($challengeId);
				$this->requireStartedChallenge($challengeId, false);
			}
			$coords = ChallengeManager::getChallengeCoordinates($this->dbh, $challengeId, $isOwner);

			return self::buildResponse(true, array("coords" => $coords));
		}

		// ================ Challenge Navigator ==========================

		/**
		 * Task: 'device_start'
		 *
		 * Get information about this challenge (name, teams, team colors, ...)
		 * This requires a valid login.
		 *
		 * Required HTTP parameters:
		 * - <b>challenge</b> The session key of the challenge
		 */
		protected function device_start(){
			$session = $this->requireLogin();

			$this->requireParameters(array(
					"challenge" => "/^[A-Za-z0-9]{4,16}$/"
			));

			$challengeId = ChallengeManager::getChallengeIdBySessionKey($this->dbh, $this->args["challenge"]);
			$team_id = $this->getTeamId($challengeId, $session);

			$info = ChallengeManager::getChallengeInformation($this->dbh, $challengeId);
			$info["team"] = $team_id;
			$info["team_members"] = TeamManager::getTeamMembers($this->dbh, $team_id);
			$info["team_color"] = TeamManager::getTeamInfo($this->dbh, $team_id)["color"];

			if($info["type_id"] == ChallengeType::CaptureTheFlag){
				$info["team_list"] = ChallengeManager::getTeams($this->dbh, $challengeId);
			}

			return self::buildResponse(true, $info);
		}

		/**
		 * Task: 'status'
		 *
		 * Returns the status of any challenge you are part of
		 * This requires a valid login.
		 *
		 * Required HTTP parameters:
		 * - <b>challenge</b> The session key of the challenge
		 */
		protected function status(){
			require_once(__DIR__ . "/../app/challenge/Checkpoint.php");

			$session = $this->requireLogin();

			$this->requireParameters(array(
					"challenge" => "/^[A-Za-z0-9]{4,16}$/"
			));

			$challengeId = ChallengeManager::getChallengeIdBySessionKey($this->dbh, $this->args["challenge"]);

			$this->requireEnabledChallenge($challengeId);
			$this->requireStartedChallenge($challengeId, false);

			$team_id = $this->getTeamId($challengeId, $session);

			$coords = ChallengeManager::getChallengeCoordinates($this->dbh, $challengeId);

			foreach($coords as &$c){
				$c["reached"] = Checkpoint::isReachedBy($this->dbh, $c["challenge_coord_id"], $team_id);
			}

			return self::buildResponse(true, array("coords" => $coords));
		}

		/**
		 * Task: 'checkpoint'
		 *
		 * Set a checkpoint as reached
		 *
		 * Required HTTP parameters:
		 * - <b>challenge</b> The session key of the challenge
		 * - <b>coord</b> The coordinate id
		 */
		protected function checkpoint(){
			require_once(__DIR__ . "/../app/challenge/Checkpoint.php");
			require_once(__DIR__ . "/../app/challenge/ChallengeCoord.php");

			$session = $this->requireLogin();

			$this->requireParameters(array(
					"challenge" => "/^[A-Za-z0-9]{4,16}$/",
					"coord" => "/[0-9]+/"
			));

			$this->assignOptionalParameter("code", null);

			$challengeId = ChallengeManager::getChallengeIdBySessionKey($this->dbh, $this->args["challenge"]);
			$this->requireEnabledChallenge($challengeId);
			$this->requireStartedChallenge($challengeId, true);

			$ccid = $this->args["coord"];
			$cid = ChallengeCoord::getCoordinate($this->dbh, $this->args["coord"]);

			if($cid == -1){
				return self::buildResponse(false, array("msg" => $this->locale->get("query.challenge.unknown_coord")));
			}

			$teamId = $this->getTeamId($challengeId, $session);

			if(ChallengeCoord::hasCode($this->dbh, $ccid)){
				if($this->args["code"] == null){
					return self::buildResponse(false, array("msg" => $this->locale->get("query.challenge.no_code")));
				}

				if(!ChallengeCoord::checkCode($this->dbh, $ccid, $this->args["code"])){
					return self::buildResponse(false, array("msg" => $this->locale->get("query.challenge.wrong_code")));
				}
			}

			if(!Checkpoint::isReachedBy($this->dbh, $ccid, $teamId)){
				require_once(__DIR__ . "/../app/challenge/ChallengeStats.php");
				$time = Checkpoint::setReached($this->dbh, $ccid, $teamId);
				$ret = array("time" => $time);

				$totalTime = ChallengeStats::calculateStats($this->dbh, $challengeId, $teamId);
				if($totalTime > 0){
					$ret["total_time"] = $totalTime;
				}

				return self::buildResponse(true, $ret);
			}
			else{
				return self::buildResponse(false, array("msg" => $this->locale->get("query.challenge.already_reached")));
			}
		}

		/**
		 * Task: 'capture'
		 *
		 * Set a cache as captured
		 *
		 * Required HTTP parameters:
		 * - <b>challenge</b> The session key of the challenge
		 * - <b>coord</b> The coordinate id
		 */
		protected function capture(){
			require_once(__DIR__ . "/../app/challenge/Checkpoint.php");
			require_once(__DIR__ . "/../app/challenge/ChallengeCoord.php");

			$session = $this->requireLogin();

			$this->requireParameters(array(
					"challenge" => "/^[A-Za-z0-9]{4,16}$/",
					"coord" => "/[0-9]+/"
			));

			$challengeId = ChallengeManager::getChallengeIdBySessionKey($this->dbh, $this->args["challenge"]);
			$this->requireEnabledChallenge($challengeId);

			$teamId = $this->getTeamId($challengeId, $session);

			$challengeData = ChallengeManager::getChallengeInformation($this->dbh, $challengeId);
			$this->requireStartedChallenge($challengeId, true, $challengeData);

			if($challengeData["type_id"] != ChallengeType::CaptureTheFlag){
				return self::buildResponse(false, array("msg" => $this->locale->get("query.challenge.capture_not_allowed")));
			}

			$ccid = $this->args["coord"];
			$captureStatus = ChallengeCoord::isCaptured($this->dbh, $ccid);

			if($captureStatus == -1){
				return self::buildResponse(false, array("msg" => $this->locale->get("query.challenge.unknown_coord")));
			}
			else if($captureStatus == 1){
				return self::buildResponse(false, array("msg" => $this->locale->get("query.challenge.already_captured")));
			}
			else{
				// Check to code
				if(ChallengeCoord::hasCode($this->dbh, $ccid)){
					if($this->args["code"] == null){
						return self::buildResponse(false, array("msg" =>$this->locale->get("query.challenge.no_code")));
					}

					if(!ChallengeCoord::checkCode($this->dbh, $ccid, $this->args["code"])){
						return self::buildResponse(false, array("msg" => $this->locale->get("query.challenge.wrong_code")));
					}
				}

				ChallengeCoord::capture($this->dbh, $ccid, $teamId);
				$time = ChallengeCoord::getCaptureTime($this->dbh, $ccid);
				return self::buildResponse(true, array("time" => $time));
			}
		}

		/*
		 * ====================================================================
		 * 	Challenge stats
		 * ====================================================================
		 */

		/**
		 * Task: 'get_stats'
		 *
		 * Get the stats of a challenge
		 */
		protected function get_stats(){
			require_once(__DIR__ . "/../app/challenge/ChallengeStats.php");

			$this->requireParameters(array(
					"challenge" => "/^[A-Za-z0-9]{4,16}$/"
			));

			$challengeId = ChallengeManager::getChallengeIdBySessionKey($this->dbh, $this->args["challenge"]);
			$this->requireEnabledChallenge($challengeId);

			$stats = ChallengeStats::getStats($this->dbh, $challengeId);
			return self::buildResponse(true, array("stats" => $stats));
		}

		/**
		 * Require that the user is the challenge owner
		 * @param integer $challengeId
		 * @param SessionManager $session
		 * @throws InvalidArgumentException if the user is not the owner
		 */
		private function requireChallengeOwner($challengeId, $session){
			if(ChallengeManager::getOwner($this->dbh, $challengeId) != $session->getAccountId()){
				throw new InvalidArgumentException($this->locale->get("query.challenge.unauthorized") . ".");
			}
		}

		/**
		 * Require that the challenge is enabled
		 * @param integer $challengeId
		 * @throws InvalidArgumentException if the challenge is not enabled
		 */
		private function requireEnabledChallenge($challengeId){
			if(!ChallengeManager::isChallengeEnabled($this->dbh, $challengeId)){
				throw new InvalidArgumentException($this->locale->get("query.challenge.challenge_not_enabled"));
			}
		}

		/**
		 * Require a started challenge
		 * @param integer $challengeId
		 * @param boolean $checkEndTimeToo
		 * @param array $challengeData (Optional) The challenge data stored in the database
		 * @throws InvalidArgumentException if the challenge has not started yet (or already ended)
		 */
		private function requireStartedChallenge($challengeId, $checkEndTimeToo, $challengeData = null){
			if($challengeData == null){
				$challengeData = ChallengeManager::getChallengeInformation($this->dbh, $challengeId);
			}

			$startTime = strtotime($challengeData["start_time"]);
			$endTimeStr = $challengeData["end_time"];
			$current_time = time();

			if($current_time < $startTime){
				throw new InvalidArgumentException(sprintf($this->locale->get("query.challenge.not_started"), date("d.m.y", $startTime),  date("H:i:s", $startTime)));
			}


			if($endTimeStr != null && $checkEndTimeToo){
				if($current_time > strtotime($endTimeStr)){
					throw new InvalidArgumentException(sprintf($this->locale->get("query.challenge.has_ended")));
				}
			}
		}

		/**
		 * Returns the team_id of the user
		 * @param integer $challengeId
		 * @param SessionManager $session
		 * @throws InvalidArgumentException if the user is no member of this challenge
		 */
		private function getTeamId($challengeId, $session){
			$team_id = TeamManager::getTeamOfUser($this->dbh, $challengeId, $session->getAccountId());
			if($team_id == -1){
				throw new InvalidArgumentException($this->locale->get("query.challenge.not_a_member"));
			}
			return $team_id;
		}

		/**
		 * This function will set a empty parameter to null.
		 *
		 * @see self::assignOptionalParameter()
		 * @param string $paramName
		 */
		private function setNullIfEmpty($paramName){
			if($this->hasParameter($paramName)){
				if(empty($this->args[$paramName])){
					$this->args[$paramName] = null;
				}
			}
		}
	}

	$config = require(__DIR__ . "/../config/config.php");

	$requestHandler = new ChallengeHTTPRequestHandler($_POST, DBTools::connectToDatabase($config));
	$requestHandler->handleRequest();
?>
