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

			$this->assignOptionalParameter("isPublic", 0);
			$this->assignOptionalParameter("start_time", null);
			$this->assignOptionalParameter("end_time", null);
			$this->assignOptionalParameter("predefined_teams", 0);
			$this->assignOptionalParameter("max_teams", 4);
			$this->assignOptionalParameter("max_team_members", 4);

			$challengeType = ChallengeType::DefaultChallenge;
			if($this->args["type"] == "ctf"){$challengeType = ChallengeType::CaptureTheFlag;}

			$id = ChallengeManager::createChallenge($this->dbh, $this->args["name"], $challengeType, $session->getAccountId(), $this->args["desc"],
													$this->args["isPublic"], $this->args["start_time"], $this->args["end_time"],
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
					"id" => "/\d/",
			));

			$challengeId = $this->args["id"];

			// Verify that the user is allowed to receive information about this challenge
			$this->verifyChallengeAccess($challengeId);
			return ChallengeManager::getTeams($this->dbh, $this->args["id"]);
		}

		protected function join_team(){
			$session = $this->requireLogin();

			$this->requireParameters(array(
					"id" => "/\d/",
					"team_id" => "/\d/"
			));

			$this->verifyOptionalParameters(array(
					"code" => "/\d/" // Code that is maybe required to join a team
			));
			$this->assignOptionalParameter("code", null);

			$this->verifyChallengeAccess($this->args["id"]);
			TeamManager::joinTeam($this->dbh, $this->args["team_id"], $session->getAccountId(), $this->args["code"]);

			return self::buildResponse(true);
		}

		protected function create_team(){

			$session = $this->requireLogin();

			$this->requireParameters(array(
					"id" => "/\d/",
					"name" => self::defaultNameRegEx(1, 32)
			));

			$challengeId = $this->args["id"];

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
	}

	$config = require(__DIR__ . "/../config/config.php");

	$requestHandler = new ChallengeRequestHandler($_POST, DBTools::connectToDatabase($config));
	$requestHandler->handleRequest();
?>