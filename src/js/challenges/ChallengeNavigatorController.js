function ChallengeNavigatorController(challenge_id){

	var htmlElement = new Object();
	htmlElement["coord_panel"] = "#challenge-navigator-coord-panel";
	htmlElement["coord_list"] = "#challenge-navigator-coord-list";
	htmlElement["canvas"] = "#challenge-navigator-canvas";
	htmlElement["canvas_container"] = "#challenge-navigator-content";
	htmlElement["codeinput_popup"] = "#code-input-popup";
	htmlElement["codeinput_textfield"] = "#checkpoint-code-input";
	htmlElement["codeinput_ok"] = "#checkpoint-code-input-ok";
	htmlElement["reached_button"] = "#checkpoint-reached-button";
	htmlElement["reload_button"] = "#challenge-navigator-update-button";
	var minDistanceToSetReached = 20; // In meters

	var challengeId = challenge_id;
	var challengeData = null;
	var isCTF = false;
	var coordData = null; //The respone from the server
	var coordList; // The map for the GPSRadar
	var iconList;
	var colorList;
	var teamMap;

	var gpsRadar = null;

	this.pageOpened = function(){

		$(htmlElement["codeinput_ok"]).click(function(e){
			sendCapturedOrReached($(htmlElement["codeinput_popup"]).attr("data-ccid"), $(htmlElement["codeinput_textfield"]).val());
			$(htmlElement["codeinput_textfield"]).val("");
			$(htmlElement["codeinput_popup"]).popup("close");
		});

		$(htmlElement["reload_button"]).click(function(e){
			getChallengeInformation();
		});

		$(htmlElement["reached_button"]).click(function(e){
			setReached();
		});

		getChallengeInformation();
	};

	this.pageClosed = function(){
		$(htmlElement["codeinput_ok"]).unbind();
		$(htmlElement["reload_button"]).unbind();
		$(htmlElement["reached_button"]).unbind();
	};

	var getChallengeInformation = function(){
		$.ajax({
			type: "POST", url: "./query/challenge.php",
			data: {task: "device_start", challenge: challengeId},
			cache: false,
			success: function(response){
				try{
					var responseData = JSON.parse(response);
					if(responseData.status == "ok"){
						onChallengeDataReceived(responseData);
					}
					else{
						SubstanceTheme.showNotification("<h3>Unable to download challenge information</h3><p>" + responseData.msg + "</p>", 7,
														$.mobile.activePage[0], "substance-red no-shadow white");
					}
				}
				catch(e){
					SubstanceTheme.showNotification("<h3>Unable to download challenge inforamtion</h3><p>Server returned:<br>" + response + "</p>", 7,
													$.mobile.activePage[0], "substance-red no-shadow white");
				}
			},
			error: ajaxError
		});
	};

	var getCoordinates = function(){
		$.ajax({
			type: "POST", url: "./query/challenge.php",
			data: {task: "info", challenge: challengeId},
			cache: false,
			success: function(response){
				try{
					var responseData = JSON.parse(response);
					if(responseData.status == "ok"){
						onCoordinateDataReceived(responseData);
					}
					else{
						SubstanceTheme.showNotification("<h3>Unable to download cache positions</h3><p>" + responseData.msg + "</p>", 7,
														$.mobile.activePage[0], "substance-red no-shadow white");
					}
				}
				catch(e){
					SubstanceTheme.showNotification("<h3>Unable to download cache positions</h3><p>Server returned:<br>" + response + "</p>", 7,
													$.mobile.activePage[0], "substance-red no-shadow white");
				}
			},
			error: ajaxError
		});
	};

	var onChallengeDataReceived = function(data){
		challengeData = data;
		isCTF = challengeData.type_id == 1; // type_id = 1 -> "Capture the Flag" challenge
		getCoordinates();
	};

	var onCoordinateDataReceived = function(data){
		coordData = data

		updateCoordList();
		updateListPanel();

		if(gpsRadar == null){
			start();
		}
		else{
			// Seems that the user has pressed the update button
			SubstanceTheme.showNotification("<p>Update successful.</p>", 3,
											$.mobile.activePage[0], "substance-skyblue no-shadow white");
		}
	};

	var updateCoordList = function(){
		coordList = new Object(); // Map id -> Coordinate (see "LocalCoordinateStore.js")
		colorList = new Object(); // Map id -> color
		iconList = new Object();  // Map id -> icon

		teamMap = new Object();

		for(var i = 0; i < challengeData.team_list.length; i++){
			teamMap[challengeData.team_list[i].team_id] = {color: challengeData.team_list[i].color, name: challengeData.team_list[i].name};
		}

		for(var i = 0; i < coordData.coords.length; i++){
			var c = coordData.coords[i];
			coordList[c.coord_id] = new Coordinate(c.coord_id, c.name, c.latitude, c.longitude, c.decription, false);

			if(isCTF){
				if(c.captured_by != null){
					colorList[c.coord_id] = teamMap[c.captured_by].color;
				}
				else{
					iconList[c.coord_id] = GPSRadar.CoordinateIcon.CIRCLE;
				}
			}
			else{
				if(c.reached != null){
					colorList[c.coord_id] = challengeData.team_color;
				}
				else{
					iconList[c.coord_id] = GPSRadar.CoordinateIcon.CIRCLE;
				}
			}
		}
	};

	var updateListPanel = function(){

		var list = $(htmlElement["coord_list"]);
		list.empty();

		for(var i = 0; i < coordData.coords.length; i++){
			list.append(generateItem(coordData.coords[i].name, isCTF ? coordData.coords[i].capture_time : coordData.coords[i].reached,
									 coordData.coords[i].captured_by));
		}

		list.listview('refresh');
	};

	var generateItem = function(name, reachedTime, capturedBy){
		return	"<li class=\"challenge-list-item\" data-role=\"list-divider\">" +
				"<span class=\"listview-left\">" + name + "</span>" +
				"<li data-icon=\"false\" class=\"challenge-list-item\"><a class=\"li-clickable\">" +
				"<p>Reached: " + (reachedTime == null ? "-" : reachedTime) + "</p>" +
				(capturedBy != null ? "<p style=\"color: " + teamMap[capturedBy].color + "\">Captured by: " + teamMap[capturedBy].name + "</p>" : "") +
				"</li>\n";
	};

/*
 * ============================================================================
 *  Send 'reached' / 'captured' to server
 * ============================================================================
 */

	var sendCapturedOrReached = function (challengeCoordId, cacheCode){
		if(isCTF){ // is "Capture the Flag" challenge
			sendCheckpointCaptured(challengeCoordId, cacheCode);
		}
		else{
			sendCheckpointReached(challengeCoordId, cacheCode);
		}
	};

	var sendCheckpointReached = function(challengeCoordId, cacheCode){
		$.ajax({
			type: "POST", url: "./query/challenge.php",
			data: {
				task: "checkpoint",
				challenge: challengeId,
				coord: challengeCoordId,
				code: cacheCode
			},
			cache: false,
			success: function(response){
				try{
					var responseData = JSON.parse(response);
					if(responseData.status == "ok"){
						// The checkpoint ist now marked as reached - update the local "database"
						for(var i = 0; i < coordData.coords.length; i++){
							if(coordData.coords[i].challenge_coord_id == challengeCoordId){
								coordData.coords[i].reached = responseData.time;
								SubstanceTheme.showNotification("<p>Checkpoint has been successfully tagged as 'reached'.</p>", 7,
																$.mobile.activePage[0], "substance-green no-shadow white");
								break;
							}
						}

						updateCoordList();
						updateListPanel();
					}
					else{
						SubstanceTheme.showNotification("<h3>Unable to update checkpoint</h3><p>" + responseData.msg + "</p>", 7,
														$.mobile.activePage[0], "substance-red no-shadow white");
					}
				}
				catch(e){
					SubstanceTheme.showNotification("<h3>Unable to store checkpoint</h3><p>" + response + "</p>", 7,
													$.mobile.activePage[0], "substance-red no-shadow white");
				}
			},
			error: ajaxError
		});
	};

	var sendCheckpointCaptured = function(challengeCoordId, cacheCode){
		$.ajax({
			type: "POST", url: "./query/challenge.php",
			data: {
				task: "capture",
				challenge: challengeId,
				coord: challengeCoordId,
				code: cacheCode
			},
			cache: false,
			success: function(response){
				try{
					var responseData = JSON.parse(response);
					if(responseData.status == "ok"){
						// The checkpoint ist now tagged as reached - update the local "database"
						for(var i = 0; i < coordData.coords.length; i++){
							if(coordData.coords[i].challenge_coord_id == challengeCoordId){
								coordData.coords[i].captured_by = challengeData.team;
								coordData.coords[i].capture_time = responseData.time;
								SubstanceTheme.showNotification("<p>Checkpoint has been successfully captured.</p>", 7,
																$.mobile.activePage[0], "substance-green no-shadow white");
								break;
							}
						}

						updateCoordList();
						updateListPanel();
					}
					else{
						SubstanceTheme.showNotification("<h3>Unable to capture this checkpoint</h3><p>" + responseData.msg + "</p>", 7,
														$.mobile.activePage[0], "substance-red no-shadow white");
					}
				}
				catch(e){
					SubstanceTheme.showNotification("<h3>Unable to capture this checkpoint</h3><p>" + response + "</p>", 7,
													$.mobile.activePage[0], "substance-red no-shadow white");
				}
			},
			error: ajaxError
		});
	};

/*
 * ============================================================================
 *  Handler
 * ============================================================================
 */

	var start = function(){
		gpsRadar = new GPSRadar($(htmlElement["canvas_container"])[0], $(htmlElement["canvas"])[0]);
		gpsRadar.start();
		setInterval(updateGPSRadar, 2000);
	};

	var updateGPSRadar = function(){
		gpsRadar.update(coordList, colorList, iconList);
	};

	var setReached = function(){
		var myPos = GPS.get();

		if(myPos == null){
			SubstanceTheme.showNotification("<p>The GPS position is currently not available - please wait until the GPS position is fixed.</p>", 7,
											$.mobile.activePage[0], "substance-skyblue no-shadow white");
			return;
		}

		// Find the coordinate with the lowsest distance to the device
		var coord = null;
		var lowestDist = minDistanceToSetReached;
		for(var i = 0; i < coordData.coords.length; i++){
			var c = coordData.coords[i];
			var distanceInMeter = GeoTools.calculateDistance(myPos.coords.latitude, myPos.coords.longitude, c.latitude,c.longitude) * 1000;
			if(distanceInMeter < lowestDist){
				ilowestDist = distanceInMeter;
				coord = c;
			}
		}

		if(coord != null){
			if(isCTF){
				if(coord.captured_by != null){
					SubstanceTheme.showNotification("<p>This cache has been already captured.</p>", 7,
													$.mobile.activePage[0], "substance-skyblue no-shadow white");
					return; //cancel
				}
			}
			else{
				if(coord.reached != null){
					SubstanceTheme.showNotification("<p>You have already tagged this cache as 'reached'.</p>", 7,
													$.mobile.activePage[0], "substance-skyblue no-shadow white");
					return; //cancel
				}
			}

			if(coord.code_required == 1){
				$(htmlElement["codeinput_popup"]).attr("data-ccid", coord.challenge_coord_id);
				$(htmlElement["codeinput_popup"]).popup("open", {positionTo: "window", transition: "pop"});
			}
			else{
				sendCapturedOrReached(coord.challenge_coord_id, null);
			}
		}
		else{
			SubstanceTheme.showNotification("<p>You have to get closer to a cache before you can set the point as 'reached' (less than " + minDistanceToSetReached + " m).</p>", 7,
											$.mobile.activePage[0], "substance-skyblue no-shadow white");
		}
	};

	var ajaxError = function(xhr, status, error){
		SubstanceTheme.showNotification("<h3>Unknown error - Ajax request faild</h3><p>" + error + "</p>", 7,
				$.mobile.activePage[0], "substance-red no-shadow white");
	};
}

ChallengeNavigatorController.currentInstance = null;

ChallengeNavigatorController.openPage = function(challengeId){
	ChallengeNavigatorController.currentInstance = new ChallengeNavigatorController(challengeId);
	ChallengeNavigatorController.currentInstance.pageOpened();
};

ChallengeNavigatorController.closePage = function(){
	ChallengeNavigatorController.currentInstance.pageClosed();
	ChallengeNavigatorController.currentInstance = null;
};

ChallengeNavigatorController.init = function(){
	$(document).on("pageshow", "#ChallengeNavigator", function(){
		var id = window.prompt("This feature is not completely implemented yet.\nPlease type in your challenge id:", "");
		ChallengeNavigatorController.openPage(id);
	});
	$(document).on("pagebeforehide", "#ChallengeNavigator", ChallengeNavigatorController.closePage);
};
