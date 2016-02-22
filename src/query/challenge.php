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

	require_once(__DIR__ . "/../app/RequestInterface.php");
	require_once(__DIR__ . "/../app/DBTools.php");
	require_once(__DIR__ . "/../app/challenge/ChallengeManager.php");
	require_once(__DIR__ . "/../app/challenge/TeamManager.php");
	require_once(__DIR__ . "/../app/JSONLocale.php");

	class ChallengeHTTPRequestHandler extends RequestInterface {

		private $dbh;

		public function __construct($parameters, $dbh){
			parent::__construct($parameters, JSONLocale::withBrowserLanguage());
			$this->dbh = $dbh;
		}

		public function handleRequest(){
			$this->handleAndSendResponseByArgsKey("task");
		}

		private function verifyChallengeAccess($challengeId){
			$isPublic = ChallengeManager::isChallengePublic($this->dbh, $challengeId);

			if(!$isPublic){
				// TODO: Allow lower case characters and convert them
				$this->requireParameters(array("key" => "/^[A-Z0-9]{4,8}$/")); //Sessionkey
				$this->assignOptionalParameter("key", null);
				if(!ChallengeManager::checkChallengeKey($this->dbh, $challengeId, $this->args["key"])){
					throw new InvalidArgumentException($this->locale->get("query.challenge.invalid_key"));
				}
			}
		}

		protected function create_challenge(){

			$session = $this->requireLogin();
			$this->requireParameters(array(
				"name" => self::defaultTextRegEx(1, 64),
				"desc" => self::defaultTextRegEx(0, 512),
				"type" => "/^(default|ctf)$/i"
			));

			$this->verifyOptionalParameters(array(
				"is_public" => "/[0-1]/",
				"start_time" => $this::defaultTimeRegEx(),
				"end_time" => $this::defaultTimeRegEx(),
				"predefined_teams" =>  "/[0-1]/",
				"max_teams" => "/\d/",
				"max_team_members" => "/\d/"
			));

			$this->assignOptionalParameter("is_public", 0);
			$this->assignOptionalParameter("start_time", null);
			$this->assignOptionalParameter("end_time", null);
			$this->assignOptionalParameter("predefined_teams", 0);
			$this->assignOptionalParameter("max_teams", 4);
			$this->assignOptionalParameter("max_team_members", 4);

			$challengeType = ChallengeType::DefaultChallenge;
			if($this->args["type"] == "ctf"){$challengeType = ChallengeType::CaptureTheFlag;}

			$id = ChallengeManager::createChallenge($this->dbh, $this->args["name"], $challengeType, $session->getAccountId(), $this->args["desc"],
													$this->args["is_public"], $this->args["start_time"], $this->args["end_time"],
													$this->args["predefined_teams"], $this->args["max_teams"],  $this->args["max_team_members"]);

			return self::buildResponse(true, array("session_id" => $id));
		}

		// Modify some data of the challenge
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
				"description" => self::defaultTextRegEx(0, 512),
				"is_public" => "/[0-1]/",
			);

			// This parameters can be modified before a challenge is enabled
			$optionalArgs2 = array(
					"type_id" => "/[0-" . ChallengeManager::CHALLENGE_TYPE_ID_MAX . "]/i",
					"start_time" => $this::defaultTimeRegEx(),
					"end_time" => null,
					"predefined_teams" =>  "/[0-1]/",
					"max_teams" => "/\d/",
					"max_team_members" => "/\d/"
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

		protected function get_challenges(){

			$this->verifyOptionalParameters(array(
					"limit" => "/\d/",
					"offset" => "/\d/"
			));

			$this->assignOptionalParameter("limit", 10);
			$this->assignOptionalParameter("offset", 0);
			return ChallengeManager::getPublicChallengs($this->dbh, $this->args["limit"], $this->args["offset"]);
		}

		protected function count_challenges(){
			return array("count" => ChallengeManager::countPublicChallenges($this->dbh));
		}

		protected function get_teams(){
			$session = $this->requireLogin();

			$this->requireParameters(array(
					"challenge" => "/^[A-Za-z0-9]{4,16}$/"
			));

			$challengeId = ChallengeManager::getChallengeIdBySessionKey($this->dbh, $this->args["challenge"]);

			// Verify that the user is allowed to receive information about this challenge
			$this->verifyChallengeAccess($challengeId);
			return ChallengeManager::getTeams($this->dbh, $challengeId);
		}

		protected function join_team(){
			$session = $this->requireLogin();

			$this->requireParameters(array(
					"challenge" => "/^[A-Za-z0-9]{4,16}$/",
					"team_id" => "/\d/"
			));

			$this->verifyOptionalParameters(array(
					"code" => "/\d/" // Code that is maybe required to join a team
			));
			$this->assignOptionalParameter("code", null);

			$challengeId = ChallengeManager::getChallengeIdBySessionKey($this->dbh, $this->args["challenge"]);

			$this->requireEnabledChallenge($challengeId);
			$this->verifyChallengeAccess($challengeId);

			TeamManager::joinTeam($this->dbh, $this->args["team_id"], $session->getAccountId(), $this->args["code"]);

			return self::buildResponse(true);
		}

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

			// Verify that the user is allowed to join the challenge
			$this->verifyChallengeAccess($challengeId);

			$challengeInfo = ChallengeManager::getChallengeInformation($this->dbh, $challengeId);
			$currentUserIsOwner = ($challengeInfo["owner"] == $session->getAccountId());

			// Check that the user has the right to create teams
			if($challengeInfo["predefined_teams"] == 1 && !$currentUserIsOwner){
				throw new InvalidArgumentException($this->locale->get("query.challenge.no_custom_teams"));
			}

			// Check optional parameters
			$this->verifyOptionalParameters(array(
					"color" => "/\^#[A-Fa-f0-9]{6}$/",
					"code" => self::defaultTextRegEx(1,16)
			));

			$this->assignOptionalParameter("color", "0xFF0000"); //TODO: randomize this value
			$this->assignOptionalParameter("code", null);

			$id = TeamManager::createTeam($this->dbh, $challengeId, $this->args["name"], $this->args["color"], $challengeInfo["predefined_teams"], $this->args["code"]);

			return self::buildResponse(true, array("team_id" => $id));
		}

		// ================ Challenge Info ==========================

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
			$coords = ChallengeManager::getChallengeCoordinates($this->dbh, $challengeId);

			return self::buildResponse(true, array("coords" => $coords));
		}

		// ================ Challenge Navigator ==========================

		// Get the information about this challenge (name, teams, team colors, ...)
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

		// Returns the status of any challenge you are part of
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

		// Tag a checkpoint as 'reached'
		protected function checkpoint(){
			require_once(__DIR__ . "/../app/challenge/Checkpoint.php");
			require_once(__DIR__ . "/../app/challenge/ChallengeCoord.php");

			$session = $this->requireLogin();

			$this->requireParameters(array(
					"challenge" => "/^[A-Za-z0-9]{4,16}$/",
					"coord" => "/\d/"
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
				$time = Checkpoint::setReached($this->dbh, $ccid, $teamId);
				return self::buildResponse(true, array("time" => $time));
			}
			else{
				return self::buildResponse(false, array("msg" => $this->locale->get("query.challenge.already_reached")));
			}
		}

		// Tag a checkpoint as 'captured'
		protected function capture(){
			require_once(__DIR__ . "/../app/challenge/Checkpoint.php");
			require_once(__DIR__ . "/../app/challenge/ChallengeCoord.php");

			$session = $this->requireLogin();

			$this->requireParameters(array(
					"challenge" => "/^[A-Za-z0-9]{4,16}$/",
					"coord" => "/\d/"
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

		private function requireChallengeOwner($challengeId, $session){
			if(ChallengeManager::getOwner($this->dbh, $challengeId) != $session->getAccountId()){
				throw new InvalidArgumentException($this->locale->get("query.challenge.unauthorized") . ".");
			}
		}

		private function requireEnabledChallenge($challengeId){
			if(!ChallengeManager::isChallengeEnabled($this->dbh, $challengeId)){
				throw new InvalidArgumentException($this->locale->get("query.challenge.challenge_not_enabled"));
			}
		}

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

		private function getTeamId($challengeId, $session){
			$team_id = TeamManager::getTeamOfUser($this->dbh, $challengeId, $session->getAccountId());
			if($team_id == -1){
				throw new InvalidArgumentException($this->locale->get("query.challenge.not_a_member"));
			}
			return $team_id;
		}
	}

	$config = require(__DIR__ . "/../config/config.php");

	$requestHandler = new ChallengeHTTPRequestHandler($_POST, DBTools::connectToDatabase($config));
	$requestHandler->handleRequest();
?>
