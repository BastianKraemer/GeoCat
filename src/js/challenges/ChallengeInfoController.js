function ChallengeInfoController(sessionKey){

	var challengeSessionKey = sessionKey;
	var challengeData;

	// Id configuration

	var infoElements ={
			title: "#challengeinfo-title",
			description: "#challengeinfo-description",
			owner: "#challengeinfo-owner",
			type: "#challengeinfo-type",
			startTime: "#challengeinfo-start-time",
			endTime: "#challengeinfo-end-time"
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
			editEtcPopup: "#challengeinfo-editetc-popup"
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
			editMaxTeamMembers: "#challengeinfo-edit-maxteam-members"
	};

	var confirmButtons = {
			editDescriptionConfirm: "#challengeinfo-editdesc-ok",
			editEtcConfirm: "#challengeinfo-editetc-ok"
	};

	// Public functions

	this.pageOpened = function(){
		downloadChallengeInfo();

		$(confirmButtons.editDescriptionConfirm).click(editDescriptionPopupSaveButtonClicked);
		$(confirmButtons.editEtcConfirm).click(editEtcPopupSaveButtonClicked);
	};

	this.pageClosed = function(){
		disableControls();

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
						SubstanceTheme.showNotification("<h3>" + GeoCat.locale.get("challenge.nav.error.download_info", "Unable to download challenge information") + "</h3>" +
														"<p>" + responseData.msg + "</p>", 7, $.mobile.activePage[0], "substance-red no-shadow white");
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
					$("#challengeinfo-cache-list").html("<tr><td colspan=4>" + GeoCat.locale.get("challenge.info.not_started", "The challenge has not started yet.") + "</td></tr>");
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

		if(data["coords"].length > 0){
			$("#challengeinfo-cache-list").html("");
			data["coords"].forEach(function(coord){
				$("#challengeinfo-cache-list").append(
					"<tr>" +
						"<td>" + coord.name + "</td>" +
						"<td>" + (coord.hint == null ? "-" : coord.hint) + "</td>" +
						"<td>" + (coord.code_required == 1 ? GeoCat.locale.get("yes", "Yes") : GeoCat.locale.get("no", "No")) + "</td>" +
						"<td>" + coord.latitude + ", " + coord.longitude + "</td>" +
					"</tr>");
			});
		}
		else{
			$("#challengeinfo-cache-list").html("<tr><td colspan=4>" + GeoCat.locale.get("challenge.info.no_caches", "There are no caches assigned to this challenge") + ".</td></tr>");
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
		$("#challengeinfo-team-list").html("");

		challengeData["team_list"].forEach(function(teamData) {
			var teamName;
			if(teamData.team_id == challengeData["your_team"]){
				teamName = "<b><i>" + teamData.name + (teamData.has_code == 1 ? "*" : "") + "</i></b>"
			}
			else{
				teamName = teamData.name + (teamData.has_code == 1 ? "*" : "")
			}

			$("#challengeinfo-team-list").append(
				"<tr>" +
					"<td style=\"background-color: " + teamData.color + "; width: 0px;\"></td>" +
					"<td>" + teamName + "</td>" +
					"<td>" + teamData.member_cnt + "/" + challengeData["max_team_members"] + "</td>" +
				"</tr>");
		});
	};

	var enableControls = function(){

		disableControls();
		if(GeoCat.loginStatus.isSignedIn == true){
			if(userIsChallengeOwner()){
				// The user is the owner of this challenge

				if(challengeData["is_enabled"] == 1){
					// The challenge is already enabled - caches cannot be edited anymore
					showButton(buttons.createTeam, function(){alert("Feature 'create team' is not implemented yet.");});
					showButton(buttons.start, function(){alert("Feature 'start' is not implemented yet.");});
					showButton(buttons.resetChallenge, function(){alert("Feature 'reset challenge' is not implemented yet.");});
					showButton(buttons.deleteChallenge, function(){alert("Feature 'delete challenge' is not implemented yet.");});
				}
				else{
					// The challenge is not enabled yet
					showButton(buttons.addCache, function(){alert("Feature 'add cache' is not implemented yet.");});
					showButton(buttons.enableChallenge, function(){alert("Feature 'enable challenge' is not implemented yet.");});
					showButton(buttons.deleteChallenge, function(){alert("Feature 'delete challenge' is not implemented yet.");});

					addClickHandlerToInfoField(infoElements.startTime, handleClickOnEditEtc, true);
					addClickHandlerToInfoField(infoElements.endTime, handleClickOnEditEtc, true);
					addClickHandlerToInfoField(infoElements.type, handleClickOnEditEtc, true);
				}

				addClickHandlerToInfoField(infoElements.title, handleClickOnEditDescription, false);
				addClickHandlerToInfoField(infoElements.description, handleClickOnEditDescription, false);
			}
			else{
				// The user is NOT the owner of this challenge
				if(challengeData["your_team"] == -1){
					// The user is already part of this challenge and has coosen a teamn
				}
				else{
					// The user does not have a team yet
				}
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
		$(infoElements.type).parent().removeClass("clickable")
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


	/*
	 * ========================================================================
	 *	Click handler
	 * ========================================================================
	 */

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

	/*
	 * ========================================================================
	 *	Popup click handler
	 * ========================================================================
	 */

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

	var sendModifiedChallengeInfo = function(ajaxData, popupId){
		ajaxData["task"] = "modify";
		ajaxData["challenge"] = challengeSessionKey;

		$.ajax({
			type: "POST", url: "./query/challenge.php",
			encoding: "UTF-8",
			data: ajaxData,
			cache: false,
			success: function(response){
				try{
					var responseData = JSON.parse(response);
					if(responseData.status == "ok"){
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
					}
					else{
						showError(GeoCat.locale.get("challenge.info.error.unknown", "Error: Update of challenge data failed."), responseData.msg);
					}
				}
				catch(e){
					console.log("ERROR: " + e);
					console.log("Server returned: " + response);
					showError(GeoCat.locale.get("challenge.info.error.unknown", "Error: Update of challenge data failed."));
				}

				$(popupId).popup("close");
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
