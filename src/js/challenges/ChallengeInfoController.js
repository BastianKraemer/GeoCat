function ChallengeInfoController(sessionKey){

	var challengeSessionKey = sessionKey;
	var challengeData;
	var coordData;
	
	var clickedTeam = 0;

	var enableCoordEdit = false;
	var me = this;

	// Id configuration

	var infoElements = {
		title: "#challengeinfo-title",
		description: "#challengeinfo-description",
		owner: "#challengeinfo-owner",
		type: "#challengeinfo-type",
		startTime: "#challengeinfo-start-time",
		endTime: "#challengeinfo-end-time",
		cacheList: "#challengeinfo-cache-list",
		teamList: "#challengeinfo-team-list",
		helpSection: "#challengeinfo-help-section",
		
		memberList: "#join-team-members"
	}

	var buttons = {
		addCache: "#challengeinfo-add-cache",
		createTeam: "#challengeinfo-create-team",
		start: "#challengeinfo-start",
		leaveChallenge: "#challengeinfo-leave",
		resetChallenge: "#challengeinfo-reset",
		enableChallenge: "#challengeinfo-enable",
		deleteChallenge: "#challengeinfo-delete"
	};

	var popups = {
		editDescriptionPopup: "#challengeinfo-editdesc-popup",
		editEtcPopup: "#challengeinfo-editetc-popup",
		cachePopup: "#challengeinfo-cache-popup",
		
		createNewTeam: "#create-team",
		joinTeam: "#join-team"
	};

	var inputElements = {
		editName: "#challengeinfo-edit-name",
		editDesc: "#challengeinfo-edit-desc",
		editIsPublic: "#challengeinfo-edit-ispublic",
		editType: "#challengeinfo-edit-type",
		editStartTime: "#challengeinfo-edit-starttime",
		editEndTime: "#challengeinfo-edit-endtime",
		editPredefTeams: "#challengeinfo-edit-predefteams",
		editMaxTeams: "#challengeinfo-edit-maxteams",
		editMaxTeamMembers: "#challengeinfo-edit-maxteam-members",
		
		teamname: "#team-name",
		teamcolor: "#team-color",
		teamaccess: "#team-access-checkbox",
		teampassword: "#team-access-password",
		teampredefined: "#team-ispredefined",
		joinTeamCodeWrap: "#join-team-wrap-password",
		joinTeamCode: "#join-team-field-password"
	};

	var confirmButtons = {
		editDescriptionConfirm: "#challengeinfo-editdesc-ok",
		editEtcConfirm: "#challengeinfo-editetc-ok",
		editCache: "#challengeinfo-editcache",
		deleteCache: "#challengeinfo-deletecache",
		
		teamcreate: "#team-button-create",
		joinYes: "#join-team-yes",
		joinNo: "#join-team-no"
	};

	// Public functions

	this.pageOpened = function(){
		downloadChallengeInfo();

		$(confirmButtons.editDescriptionConfirm).click(editDescriptionPopupSaveButtonClicked);
		$(confirmButtons.editEtcConfirm).click(editEtcPopupSaveButtonClicked);
		$(confirmButtons.editCache).click(editCacheOnClick);
		$(confirmButtons.deleteCache).click(deleteCacheOnClick);
		
		$(confirmButtons.teamcreate).click(createTeamClicked);
		$(confirmButtons.joinYes).click(joinTeamYesClicked);
		$(confirmButtons.joinNo).click(joinTeamNoClicked);
	};

	this.pageClosed = function(){
		disableControls();

		$(infoElements.teamList).html("");
		$(infoElements.cacheList).html("");
		$(infoElements.helpSection).html("");

		for(var key in confirmButtons){
			$(confirmButtons[key].editDescriptionConfirm).unbind();
		}
	};

	// private functions

	var ajaxError = function(xhr, status, error){
		SubstanceTheme.showNotification("<h3>Unknown error - Ajax request failed</h3><p>" + error + "</p>", 7,
				$.mobile.activePage[0], "substance-red no-shadow white");
	};

	var downloadChallengeInfo = function(){
		$.ajax({
			type: "POST", url: "./query/challenge.php",
			encoding: "UTF-8",
			data: {task: "about", challenge: challengeSessionKey},
			cache: false,
			success: function(response){
					var responseData;
					try{
						responseData = JSON.parse(response);
					}
					catch(e){
						SubstanceTheme.showNotification("<h3>Unable to download challenge information</h3><p>Server returned:<br>" + response + "</p>", 7,
														$.mobile.activePage[0], "substance-red no-shadow white");
						return;
					}

					if(responseData.status == "ok"){
						onChallengeDataReceived(responseData);
					}
					else{
						$.mobile.changePage("#ChallengeBrowser");

						setTimeout(function(){
							SubstanceTheme.showNotification("<h3>" + GeoCat.locale.get("challenge.nav.error.download_info", "Unable to download challenge information") + "</h3>" +
															"<p>" + responseData.msg + "</p>", 7, $.mobile.activePage[0], "substance-red no-shadow white");
						}, 750);
					}

			},
			error: ajaxError
		});
	};

	var downloadCoordData = function(){
		$.ajax({
			type: "POST", url: "./query/challenge.php",
			encoding: "UTF-8",
			data: {task: "coord_info", challenge: challengeSessionKey},
			cache: false,
			success: function(response){
				var responseData;
				try{
					responseData= JSON.parse(response);
				}
				catch(e){
					SubstanceTheme.showNotification("<h3>Unable to download cache positions</h3><p>Server returned:<br>" + response + "</p>", 7,
													$.mobile.activePage[0], "substance-red no-shadow white");
					return;
				}

				if(responseData.status == "ok"){
					onCoordinateDataReceived(responseData);
				}
				else{
					// The only error that could oocur ist that the challenge has not started yet...
					$(infoElements.cacheList).html("<tr><td colspan=4>" + GeoCat.locale.get("challenge.info.not_started", "The challenge has not started yet.") + "</td></tr>");
				}

			},
			error: ajaxError});
	};

	var onChallengeDataReceived = function(data){

		challengeData = data;

		// If the challenge is not enabled then the user must be the owner
		downloadCoordData();

		updateGUIWithChallengeData();
		updateTeamList();
		enableControls();
	};

	var updateGUIWithChallengeData = function(){
		$(infoElements.title).html(challengeData["name"]);
		$(infoElements.description).html(challengeData["description"]);

		$(infoElements.owner).html(challengeData["owner_name"]);
		$(infoElements.type).html(challengeData["type_name"]);
		$(infoElements.startTime).html(challengeData["start_time"]);
		$(infoElements.endTime).html(challengeData["end_time"] == null ? "-" : challengeData["end_time"]);
	}

	var onCoordinateDataReceived = function(data){
		coordData = {};
		if(data["coords"].length > 0){
			$(infoElements.cacheList).html("");
			data["coords"].forEach(function(coord){
				$(infoElements.cacheList).append(
					"<tr data-cc-id=\"" + coord.challenge_coord_id + "\">" +
						"<td>" + coord.name + "</td>" +
						"<td>" + (coord.hint == null ? "-" : coord.hint) + "</td>" +
						"<td>" + (coord.code_required == 1 ? GeoCat.locale.get("yes", "Yes") : GeoCat.locale.get("no", "No")) + "</td>" +
						"<td>" + coord.latitude + ", " + coord.longitude + "</td>" +
					"</tr>");

				coordData[coord.challenge_coord_id] = coord;
			});

			if(enableCoordEdit){;
				$(infoElements.cacheList + " tr td").click(trOnClick);
				$(infoElements.cacheList + " tr td").css("cursor", "pointer");
			}
		}
		else{
			$(infoElements.cacheList).html("<tr><td colspan=4>" + GeoCat.locale.get("challenge.info.no_caches", "There are no caches assigned to this challenge") + ".</td></tr>");
		}
	};

	var updateCacheList = function(data){
		$("#challengeinfo-team-list").html("");

		data["team_list"].forEach(function(teamData) {
			$("#challengeinfo-team-list").append(
				"<tr>" +
					"<td style=\"background-color: " + teamData.color + "; width: 0px;\"></td>" +
					"<td>" + teamData.name + (teamData.has_code == 1 ? "*" : "") + "</td>" +
					"<td>" + teamData.member_cnt + "/" + data["max_team_members"] + "</td>" +
				"</tr>");
		});
	};

	var updateTeamList = function(){
		$(infoElements.teamList).html("");
		
		challengeData["team_list"].forEach(function(teamData) {
			var teamName;
			if(teamData.team_id == challengeData["your_team"]){
				teamName = "<b><i>" + teamData.name + (teamData.has_code == 1 ? "*" : "") + "</i></b>"
			}
			else{
				teamName = teamData.name + (teamData.has_code == 1 ? "*" : "")
			}

			$(infoElements.teamList).append(
				"<tr id=\"" + teamData.team_id + "\">" + 
					"<td style=\"background-color: " + teamData.color + "; width: 0px;\"></td>" +
					"<td>" + teamName + "</td>" +
					"<td>" + teamData.member_cnt + "/" + challengeData["max_team_members"] + "</td>" +
				"</tr>");
			$("#" + teamData.team_id).addClass('teamlist');
		});
		$(".teamlist").click(handleClickOnJoinTeam);
	};

	var enableControls = function(){

		disableControls();
		if(GeoCat.loginStatus.isSignedIn == true){
			if(userIsChallengeOwner()){
				// The user is the owner of this challenge

				if(challengeData["is_enabled"] == 1){
					// The challenge is already enabled - caches cannot be edited anymore
					showButton(buttons.start, handleClickOnGoToNavigator);
					sendRequestHasJoined(function(response){
						if(response.has_team == true){
							showButton(buttons.leaveChallenge, handleClickOnLeaveTeam);
							$(buttons.leaveChallenge).attr("data-teamid", response.teamid);
						} else {
							showButton(buttons.resetChallenge, handleClickOnResetChallenge);
						}
					}); 

					enableCoordEdit = false;
				}
				else{
					// The challenge is not enabled yet
					showButton(buttons.addCache, handleClickOnAddCache);
					showButton(buttons.enableChallenge, handleClickOnEnableChallenge);

					addClickHandlerToInfoField(infoElements.startTime, handleClickOnEditEtc, true);
					addClickHandlerToInfoField(infoElements.endTime, handleClickOnEditEtc, true);
					addClickHandlerToInfoField(infoElements.type, handleClickOnEditEtc, true);

					enableCoordEdit = true;
				}

				showButton(buttons.deleteChallenge, handleClickOnDeleteChallenge);

				addClickHandlerToInfoField(infoElements.title, handleClickOnEditDescription, false);
				addClickHandlerToInfoField(infoElements.description, handleClickOnEditDescription, false);

				var txt = GeoCat.locale.get("challenge.info.sessionkey", "The sessionkey for this challenge is {0}");
				$(infoElements.helpSection).html("<p class=\"center\">" + GuiToolkit.sprintf(txt, ["<b>" + challengeSessionKey.toUpperCase() + "</b>"]) + "</p>");
			}
			else{
				enableCoordEdit = false;
				// The user is NOT the owner of this challenge
				if(challengeData["your_team"] != -1){
					// The user is already part of this challenge and has coosen a team
					showButton(buttons.start, handleClickOnGoToNavigator);
					showButton(buttons.leaveChallenge, handleClickOnLeaveTeam);
				}
			}
			if((userIsChallengeOwner() && challengeData["is_enabled"] == 1) || (!userIsChallengeOwner() && challengeData["your_team"] == -1)){
				showButton(buttons.createTeam, handleClickOnCreateTeam);
			}
		}

	};

	var disableControls = function(){
		for(var key in buttons){
			$(buttons[key]).css("display", "none");
			$(buttons[key]).unbind();
		}

		$(infoElements.startTime).parent().unbind();
		$(infoElements.startTime).parent().removeClass("clickable")
		$(infoElements.endTime).parent().unbind();
		$(infoElements.endTime).parent().removeClass("clickable")
		$(infoElements.type).parent().unbind();
		$(infoElements.type).parent().removeClass("clickable");
	}

	var addClickHandlerToInfoField = function(id, callback, useParentElement){
		var el = useParentElement ? $(id).parent() : $(id);
		el.click(callback);
		el.addClass("clickable");
	}

	var showButton = function(id, onClickHandler){
		$(id).css("display", "inline-block");
		$(id).click(onClickHandler);
	};

	var userIsChallengeOwner = function(){
		return (GeoCat.loginStatus.username == challengeData["owner_name"]);
	};

	var showCacheEditDialog = function(ccId, editData){
		me.enableEvents(false);

		CoordinateEditDialogController.showDialog(
			$.mobile.activePage.attr("id"),
			function(){me.enableEvents(true);},
			function(data, editDialog){
				sendChallengeCacheUpdateRequest(data, ccId, editDialog);
			},
			editData,
			{
				showHintField: true,
				showPriorityField: true,
				showCodeField: true,
				hideIsPublicField: true,
				hideDescriptionField: true,
				noAutoClose: true,
				getCurrentPos: (ccId == null)}
		);
	}
	
	var showJoinTeamPopup = function(teamid){
		$(popups.joinTeam).popup('open');
		var teamData = challengeData['team_list'];
		teamData.forEach(function(team){
			if(team.team_id == teamid){
				if(team.has_code == 0){
					$(inputElements.joinTeamCodeWrap).hide();
				} else {
					$(inputElements.joinTeamCodeWrap).show();
				}
				if(team.member_cnt > 0){
					sendGetTeamMemberlist(teamid);
				} else {
					$(infoElements.memberList).html(GeoCat.locale.get("challenge.info.teamempty"));
				}
				$(confirmButtons.joinYes).attr("name", teamid);
			}
		}); 
	}

	/*
	 * ========================================================================
	 *	Click handler
	 * ========================================================================
	 */
	
	var handleClickOnCreateTeam = function(){
		$(popups.createNewTeam).popup("open", {positionTo: "window", transition: "pop"});
	}
	
	var handleClickOnJoinTeam = function(){
		clickedTeam = this.id;
		sendRequestHasJoined(function(response){
			if(response.has_team == false){
				showJoinTeamPopup(clickedTeam);
				clickedTeam = 0;
			}
		});
	}
	
	var handleClickOnLeaveTeam = function(){
		var teamname = "";
		challengeData['team_list'].forEach(function(team){
			if(team.team_id == $(buttons.leaveChallenge).attr("data-teamid")){
				teamname = team.name;
			}
		});
		SubstanceTheme.showYesNoDialog(
				"<h2>" + GeoCat.locale.get("challenge.info.leave.team", "Leave Team") + "</h2>" +
				"<p>" + GeoCat.locale.get("challenge.info.leave.text", "Do you really want to leave this team?") + "</p>" +
				"<p><b>" + teamname + "</b></p>",
				$.mobile.activePage[0], sendLeaveTeam, null, "substance-white")
	}

	var handleClickOnEditDescription = function(){
		$(inputElements.editName).val(challengeData["name"]);
		$(inputElements.editDesc).val(challengeData["description"]);
		$(inputElements.editIsPublic).prop('checked', (challengeData["is_public"] == 1)).checkboxradio('refresh');

		$(popups.editDescriptionPopup).popup("open", {positionTo: "window", transition: "pop"});
	};

	var handleClickOnEditEtc = function(){

		$(inputElements.editType).val(challengeData["type_id"]).selectmenu('refresh');
		$(inputElements.editStartTime).val(challengeData["start_time"]);
		$(inputElements.editEndTime).val(challengeData["end_time"] == null ? "" : challengeData["end_time"]);
		$(inputElements.editPredefTeams).prop('checked', (challengeData["predefined_teams"] == 1)).checkboxradio('refresh');
		$(inputElements.editMaxTeams).val(challengeData["max_teams"]).selectmenu('refresh');
		$(inputElements.editMaxTeamMembers).val(challengeData["max_team_members"]).selectmenu('refresh');
		$(popups.editEtcPopup).popup("open", {positionTo: "window", transition: "pop"});
	};

	var handleClickOnAddCache = function(){
		var ccId = $(this).parent().attr("data-cc-id"); //ccId = challenge coord id
		showCacheEditDialog(null, {priority: 1});
	};

	var handleClickOnGoToNavigator = function(){
		GeoCat.setCurrentChallenge(challengeSessionKey);
		$.mobile.changePage("#ChallengeNavigator");
	};

	var handleClickOnEnableChallenge = function(){
		SubstanceTheme.showYesNoDialog(
			"<h2>" + GeoCat.locale.get("challenge.info.enable.title", "Enable challenge") + "</h2>" +
			"<p>" + GeoCat.locale.get("challenge.info.enable.text", "Do you want to enable this challenge? After this you won't be able to edit the caches of this challenge anymore.") + "</p>",
			$.mobile.activePage[0], sendChallengeEnableRequest, null, "substance-white")
	};

	var handleClickOnResetChallenge = function(){
		SubstanceTheme.showYesNoDialog(
				"<h2>" + GeoCat.locale.get("challenge.info.reset.title", "Reset challenge") + "</h2>" +
				"<p>" + GeoCat.locale.get("challenge.info.reset.text", "Do you really want to reset this challenge? This will delete all teams and the stats.") + "</p>",
				$.mobile.activePage[0], sendChallengeResetRequest, null, "substance-white")
	};

	var handleClickOnDeleteChallenge = function(){
		SubstanceTheme.showYesNoDialog(
			"<h2>" + GeoCat.locale.get("challenge.info.delete.title", "Delete challenge") + "</h2>" +
			"<p>" + GeoCat.locale.get("challenge.info.delete.text", "Do you really want to delete this challenge. This operation can't be undone.") + "</p>",
			$.mobile.activePage[0], sendChallengeDeleteRequest, null, "substance-white")
	};

	var trOnClick = function(event, ui){
		var ccId = $(this).parent().attr("data-cc-id"); //ccId = challenge coord id
		$(popups.cachePopup).attr("data-cc-id", ccId);
		$(popups.cachePopup).popup("open", {transition: "pop", positionTo: event.target});

	};

	var editCacheOnClick = function(){
		var ccId = $(popups.cachePopup).attr("data-cc-id"); //ccId = challenge coord id
		var coord = coordData[ccId];

		showCacheEditDialog(ccId, CoordinateEditDialogController.genCacheDataObject(
				coord.name, coord.description, coord.latitude, coord.longitude,
				coord.hint, coord.priority, coord.code));
	}

	var deleteCacheOnClick = function(){
		sendChallengeRemoveCacheRequest($(popups.cachePopup).attr("data-cc-id"), popups.cachePopup);
	}

	/*
	 * ========================================================================
	 *	Popup click handler
	 * ========================================================================
	 */
	
	var createTeamClicked = function(){
		var name = $(inputElements.teamname).val();
		var color = $(inputElements.teamcolor).val();
		var code = "";
		if($(inputElements.teamaccess).is(":checked")){
			code = $(inputElements.teampassword).val();
		}
		var predefined_teams = $(inputElements.teampredefined).is(":checked");
		
		sendCreateTeam({name, color, code, predefined_teams});
	}
	
	var joinTeamYesClicked = function(){
		var teamid = this.name;
		
		var teamData = challengeData['team_list'];
		teamData.forEach(function(team){
			if(team.team_id == teamid){
				var code = "";
				if(team.has_code == 1){
					code = $(inputElements.joinTeamCode).val();
				}
				sendJoinTeam({teamid, code});
				$(popups.joinTeam).popup('close');
			}
		});
	}
	
	var joinTeamNoClicked = function(){
		$(popups.joinTeam).popup('close');
	}

	var editDescriptionPopupSaveButtonClicked = function(){

		var newName = $(inputElements.editName).val();
		var newDesc = $(inputElements.editDesc).val();
		var isPubic = $(inputElements.editIsPublic).is(":checked");

		sendModifiedChallengeInfo({name: newName, description: newDesc, is_Public: isPubic}, popups.editDescriptionPopup);
	}

	var editEtcPopupSaveButtonClicked = function(){

		var endTime = $(inputElements.editEndTime).val();

		sendModifiedChallengeInfo({
				type_id: 				$(inputElements.editType)[0].value,
				start_time: 		$(inputElements.editStartTime).val().replace("T", " "),
				end_time: 			endTime == "" ? null : $(inputElements.editEndTime).val().replace("T", " "),
				predefined_teams: 	$(inputElements.editPredefTeams).is(":checked") ? 1 : 0,
				max_teams:			$(inputElements.editMaxTeams)[0].value,
				max_team_members:	$(inputElements.editMaxTeamMembers)[0].value,
			},
			popups.editEtcPopup);
	}

	/*
	 * ========================================================================
	 *	AJAX functions
	 * ========================================================================
	 */
	
	var sendRequestHasJoined = function(successCallback){
		var ajaxData = {};
		
		ajaxData["task"] = "user_has_team";
		ajaxData["challenge"] = challengeSessionKey;
		
		sendAJAXRequest(
			ajaxData,
			function(response){
				successCallback(response);
			},
			"Error: request 'has team' failed",
			null);
	}
	
	var sendCreateTeam = function(ajaxData){
		ajaxData["task"] = "create_team";
		ajaxData["challenge"] = challengeSessionKey;
		
		$(popups.createNewTeam).popup('close');
		
		sendAJAXRequest(
				ajaxData,
			function(response){
				downloadChallengeInfo();
			},
			"Error: request 'create team' failed",
			null);
	};
	
	var sendGetTeamMemberlist = function(teamid){
		var ajaxData = {};
		ajaxData["task"] = "get_memberlist";
		ajaxData["challenge"] = challengeSessionKey;
		ajaxData["teamid"] = teamid; 
		
		sendAJAXRequest(
			ajaxData,
			function(response){
				var responseData; 
				try{
					$(infoElements.memberList).html("");
					response['memberlist'].forEach(function(member){
						$(infoElements.memberList).append(
						"<tr>" + 
							"<td>" + member.username + "</td>" +
						"</tr>");
					});
				}
				catch(e){
					$(popups.joinTeam).popup('close');
					SubstanceTheme.showNotification("<h3>Unable to download member list</h3><p>Server returned:<br>" + response + "</p>", 7,
													$.mobile.activePage[0], "substance-red no-shadow white");
					return;
				}
			},
			"Error: request 'get memberlist' failed",
			null);
	}
	
	var sendJoinTeam = function(data){
		var ajaxData = {};
		ajaxData["task"] = "join_team";
		ajaxData["challenge"] = challengeSessionKey;
		ajaxData["team_id"] = data.teamid;
		ajaxData["code"] = data.code;
		
		sendAJAXRequest(
				ajaxData,
			function(response){
				downloadChallengeInfo();
			},
			"Error: request 'join team' failed",
			null);
	}
	
	var sendLeaveTeam = function(){
		var ajaxData = {};
		ajaxData["task"] = "leave_team";
		ajaxData["challenge"] = challengeSessionKey;
		ajaxData["team_id"] = $(buttons.leaveChallenge).attr("data-teamid");
		
		sendAJAXRequest(
				ajaxData,
			function(response){
				downloadChallengeInfo();
			},
			"Error: request 'leave team' failed",
			null);
	}

	var sendModifiedChallengeInfo = function(ajaxData, popupId){
		ajaxData["task"] = "modify";
		ajaxData["challenge"] = challengeSessionKey;

		sendAJAXRequest(
			ajaxData,
			function(response){
				// Update the new data in the local "database"
				for(var key in ajaxData){
					if(key != "task" && key != "challenge"){
						challengeData[key] = ajaxData[key];
						if(key == "type_id"){
							challengeData["type_name"] = ajaxData[key] == 1 ? "Capture the Flag" : "Default Challenge";
						}
						updateGUIWithChallengeData();
					}
				}
			},
			"Error: Update of challenge data failed.",
			popupId
		);
	};

	var sendChallengeCacheUpdateRequest = function(ajaxData, ccid, editDialog){
		ajaxData["task"] = "update_cache";
		ajaxData["challenge"] = challengeSessionKey;
		ajaxData["ccid"] = ccid;

		sendAJAXRequest(
			ajaxData,
			function(response){
				// Download all caches again...
				editDialog.close();
				downloadCoordData();
			},
			"Error: Update of cache failed.",
			null);
	};

	var sendChallengeRemoveCacheRequest = function(challengeCoordId, popupId){
		sendAJAXRequest({
				task: "remove_cache",
				challenge: challengeSessionKey,
				ccid: challengeCoordId
			},
			function(response){
				$(infoElements.cacheList + " tr[data-cc-id='" + challengeCoordId + "']").remove();
			},
			"Error: Unable to delete cache.",
			popupId);
	};

	var sendChallengeEnableRequest = function(){
		sendAJAXRequest({
				task: "enable",
				challenge: challengeSessionKey,
			},
			function(response){
				challengeData["is_enabled"] = 1;
				$(infoElements.cacheList + " tr td").unbind().css("cursor", "default");
				enableControls();
			},
			"Error: Cannot enable challenge",
			null);
	};

	var sendChallengeResetRequest = function(){
		sendAJAXRequest({
				task: "reset",
				challenge: challengeSessionKey,
			},
			downloadChallengeInfo,
			"Error: Unable to reset challenge",
			null);
	};

	var sendChallengeDeleteRequest = function(){
		sendAJAXRequest({
				task: "delete",
				challenge: challengeSessionKey,
			},
			function(){
				$.mobile.changePage("#ChallengeBrowser");
			},
			"Error: Unable to delete challenge",
			null);
	};


	var sendAJAXRequest = function(ajaxData, successHandler, errorMsg, closePopup){
		$.ajax({
			type: "POST", url: "./query/challenge.php",
			encoding: "UTF-8",
			data: ajaxData,
			cache: false,
			success: function(response){
				try{
					var responseData = JSON.parse(response);
					if(responseData.status == "ok"){
						successHandler(responseData);
					}
					else{
						showError(errorMsg, responseData.msg);
					}
				}
				catch(e){
					console.log("ERROR: " + e);
					console.log("Server returned: " + response);
					showError(errorMsg);
				}

				if(closePopup != null){
					$(closePopup).popup("close");
				}
			},
			error: ajaxError
		});
	};

	var showError = function(h3, p){
		SubstanceTheme.showNotification("<h3>" + h3 + "</h3>" + (p != null ? "<p>" + p + "</p>" : ""), 7, $.mobile.activePage[0], "substance-red no-shadow white");
	};
}

ChallengeInfoController.init = function(myPageId){
	ChallengeInfoController.prototype = new PagePrototype(myPageId, function(){
		var key = GeoCat.getCurrentChallenge();

		if(key == ""){
			key = location.search;
			if(key != ""){
				key = key.slice(1);
			}
			else{
				SubstanceTheme.showNotification("<h3>Unknown Sessionkey</h3>", 7, $.mobile.activePage[0], "substance-red no-shadow white")
				return;
			}
		}

		return new ChallengeInfoController(key);
	});
};
