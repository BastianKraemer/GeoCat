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

	class ChallengeRequestHandler extends RequestInterface {

		private $dbh;

		public function __construct($parameters, $dbh){
			parent::__construct($parameters);
			parent::requireParameters(array("task" => null));
			$this->dbh = $dbh;
		}

		public function handleRequest(){
			$this->handleAndSendResponseByArgsKey("task");
		}

		private function requireLogin(){
			require_once(__DIR__ . "/../app/SessionManager.php");

			$session = new SessionManager();
			if(!$session->isSignedIn()){
				throw new MissingSessionException();
			}
			return $session;
		}

		private function verifyChallengeAccess($challengeId){
			$isPublic = ChallengeManager::isChallengePublic($this->dbh, $challengeId);

			if(!$isPublic){
				// TODO: Allow lower case characters and convert them
				$this->requireParameters(array("key" => "/^[A-Z0-9]{4,8}$/")); //Sessionkey
				$this->assignOptionalParameter("key", null);
				if(!ChallengeManager::checkChallengeKey($this->dbh, $challengeId, $this->args["key"])){
					throw new InvalidArgumentException("Invalid session key.");
				}
			}
		}

		protected function create_challenge(){

			$session = $this->requireLogin();
			$this->requireParameters(array(
				"name" => self::defaultNameRegEx(1, 64),
				"desc" => self::defaultNameRegEx(1, 512),
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
					"challenge" => "/\d/",
			));

			$challengeId = $this->args["challenge"];

			// Verify that the user is allowed to receive information about this challenge
			$this->verifyChallengeAccess($challengeId);
			return ChallengeManager::getTeams($this->dbh, $this->args["challenge"]);
		}

		protected function join_team(){
			$session = $this->requireLogin();

			$this->requireParameters(array(
					"challenge" => "/\d/",
					"team_id" => "/\d/"
			));

			$this->verifyOptionalParameters(array(
					"code" => "/\d/" // Code that is maybe required to join a team
			));
			$this->assignOptionalParameter("code", null);

			$this->requireEnabledChallenge($this->args["challenge"]);
			$this->verifyChallengeAccess($this->args["challenge"]);

			TeamManager::joinTeam($this->dbh, $this->args["team_id"], $session->getAccountId(), $this->args["code"]);

			return self::buildResponse(true);
		}

		protected function create_team(){

			$session = $this->requireLogin();

			$this->requireParameters(array(
					"challenge" => "/\d/",
					"name" => self::defaultNameRegEx(1, 32)
			));

			$challengeId = $this->args["challenge"];

			if(!ChallengeManager::challengeExists($this->dbh, $challengeId)){
				throw new InvalidArgumentException("Challenge does not exist.");
			}

			// Verify that the user is allowed to join the challenge
			$this->verifyChallengeAccess($challengeId);

			$challengeInfo = ChallengeManager::getChallengeInformation($this->dbh, $challengeId);
			$currentUserIsOwner = ($challengeInfo["owner"] == $session->getAccountId());

			// Check that the user has the right to create teams
			if($challengeInfo["predefined_teams"] == 1 && !$currentUserIsOwner){
				throw new InvalidArgumentException("You cannot create own teams in this challenge.");
			}

			// Check optional parameters
			$this->verifyOptionalParameters(array(
					"color" => "/\^#[A-Fa-f0-9]{6}$/",
					"code" => self::defaultNameRegEx(1,16)
			));

			$this->assignOptionalParameter("color", "0xFF0000"); //TODO: randomize this value
			$this->assignOptionalParameter("code", null);

			$id = TeamManager::createTeam($this->dbh, $challengeId, $this->args["name"], $this->args["color"], $challengeInfo["predefined_teams"], $this->args["code"]);

			return self::buildResponse(true, array("team_id" => $id));
		}

		// ================ Challenge Navigator ==========================

		protected function device_start(){
			$session = $this->requireLogin();

			$this->requireParameters(array(
					"challenge" => "/\d/",
			));

			$challengeId = $this->args["challenge"];
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

		protected function info(){
			require_once(__DIR__ . "/../app/challenge/Checkpoint.php");

			$session = $this->requireLogin();

			$this->requireParameters(array(
					"challenge" => "/\d/",
			));

			$challengeId = $this->args["challenge"];

			$this->requireEnabledChallenge($challengeId);
			$this->requireStartedChallenge($challengeId);

			$team_id = $this->getTeamId($challengeId, $session);

			$coords = ChallengeManager::getChallengeCoordinates($this->dbh, $challengeId);

			foreach($coords as &$c){
				$c["reached"] = Checkpoint::isReachedBy($this->dbh, $c["challenge_coord_id"], $team_id);
			}

			return self::buildResponse(true, array("coords" => $coords));
		}

		protected function checkpoint(){
			require_once(__DIR__ . "/../app/challenge/Checkpoint.php");
			require_once(__DIR__ . "/../app/challenge/ChallengeCoord.php");

			$session = $this->requireLogin();

			$this->requireParameters(array(
					"challenge" => "/\d/",
					"coord" => "/\d/"
			));

			$this->assignOptionalParameter("code", null);

			$challengeId = $this->args["challenge"];
			$this->requireEnabledChallenge($challengeId);
			$this->requireStartedChallenge($challengeId);

			$ccid = $this->args["coord"];
			$cid = ChallengeCoord::getCoordinate($this->dbh, $this->args["coord"]);

			if($cid == -1){
				return self::buildResponse(false, array("msg" => "There is no coordinate with this id."));
			}

			$teamId = $this->getTeamId($challengeId, $session);

			if(ChallengeCoord::hasCode($this->dbh, $ccid)){
				if($this->args["code"] == null){
					return self::buildResponse(false, array("msg" => "Please specify the code for this cache."));
				}

				if(!ChallengeCoord::checkCode($this->dbh, $ccid, $this->args["code"])){
					return self::buildResponse(false, array("msg" => "You have insert the wrong code for this cache."));
				}
			}

			if(!Checkpoint::isReachedBy($this->dbh, $ccid, $teamId)){
				$time = Checkpoint::setReached($this->dbh, $ccid, $teamId);
				return self::buildResponse(true, array("time" => $time));
			}
			else{
				return self::buildResponse(false, array("msg" => "This coordinate has been already tagged as reached."));
			}
		}

		protected function capture(){
			require_once(__DIR__ . "/../app/challenge/Checkpoint.php");
			require_once(__DIR__ . "/../app/challenge/ChallengeCoord.php");

			$session = $this->requireLogin();

			$this->requireParameters(array(
					"challenge" => "/\d/",
					"coord" => "/\d/"
			));

			$challengeId = $this->args["challenge"];
			$this->requireEnabledChallenge($challengeId);
			$this->requireStartedChallenge($challengeId);

			$teamId = $this->getTeamId($challengeId, $session);

			if(!ChallengeManager::getChallengeInformation($this->dbh, $challengeId)["type_id"] == ChallengeType::CaptureTheFlag){
				return self::buildResponse(false, array("msg" => "You cannot capture coordinates on non 'Capture the Flag' challenges."));
			}

			$ccid = $this->args["coord"];
			$captureStatus = ChallengeCoord::isCaptured($this->dbh, $ccid);

			if($captureStatus == -1){
				return self::buildResponse(false, array("msg" => "There is no coordinate with this id."));
			}
			else if($captureStatus == 1){
				return self::buildResponse(false, array("msg" => "The coordinate has been already captured."));
			}
			else{
				// Check to code
				if(ChallengeCoord::hasCode($this->dbh, $ccid)){
					if($this->args["code"] == null){
						return self::buildResponse(false, array("msg" => "Please specify the code for this cache."));
					}

					if(!ChallengeCoord::checkCode($this->dbh, $ccid, $this->args["code"])){
						return self::buildResponse(false, array("msg" => "You have insert the wrong code for this cache."));
					}
				}

				ChallengeCoord::capture($this->dbh, $ccid, $teamId);
				$time = ChallengeCoord::getCaptureTime($this->dbh, $ccid);
				return self::buildResponse(true, array("time" => $time));
			}
		}

		private function requireEnabledChallenge($challengeId){
			if(!ChallengeManager::isChallengeEnabled($this->dbh, $challengeId)){
				throw new InvalidArgumentException(false, array("msg" => "The challenge is not enabled."));
			}
		}

		private function requireStartedChallenge($challengeId){
			// TODO: has challenge started (compare start_time with current time)
		}

		private function getTeamId($challengeId, $session){
			$team_id = TeamManager::getTeamOfUser($this->dbh, $challengeId, $session->getAccountId());
			if($team_id == -1){
				throw new InvalidArgumentException("You are not a member of this challenge");
			}
			return $team_id;
		}
	}

	$config = require(__DIR__ . "/../config/config.php");

	$requestHandler = new ChallengeRequestHandler($_POST, DBTools::connectToDatabase($config));
	$requestHandler->handleRequest();
?>
