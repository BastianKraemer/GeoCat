function ChallengeNavigatorController(challenge_id){

	var htmlElement = new Object();
	htmlElement["coord_panel"] = "#challenge-navigator-coord-panel";
	htmlElement["coord_list"] = "#challenge-navigator-coord-list";
	htmlElement["stats"] = "#challenge-navigator-stats";
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
					SubstanceTheme.showNotification("<h3>Unable to download challenge information</h3><p>Server returned:<br>" + response + "</p>", 7,
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
					alert(e);
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
		updateStatsPanel();

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
		if(isCTF){
			for(var i = 0; i < challengeData.team_list.length; i++){
				teamMap[challengeData.team_list[i].team_id] = {color: challengeData.team_list[i].color, name: challengeData.team_list[i].name};
			}
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
									 isCTF ? coordData.coords[i].captured_by : null, i));
		}

		$(htmlElement["coord_list"] + " li a.li-clickable").click(function(){
			listItemOnClick(this);
		});

		list.listview('refresh');
	};

	var generateItem = function(name, reachedTime, capturedBy, index){
		return generateDefaultListItem(name,
					"<a class=\"li-clickable\" data-index=\"" + index + "\">" +
					"<p>Reached: " + (reachedTime == null ? "-" : reachedTime) + "</p>" +
					(capturedBy != null ? "<p style=\"color: " + teamMap[capturedBy].color + "\">Captured by: " + teamMap[capturedBy].name + "</p>" : "")
				);
	};

	var listItemOnClick = function(el){
		var data = coordData.coords[$(el).attr("data-index")];
		SubstanceTheme.showNotification("<h3>Hint for cache '" + data.name + "'</h3><p>" + data.hint + "</p>", -1,
										$.mobile.activePage[0], "substance-skyblue no-shadow white")
	};

	var updateStatsPanel = function(){

		var list = $(htmlElement["stats"]);
		list.empty();

		list.append(generateDefaultListItem("Challenge Information",
						"<h3>" + challengeData.name + "</h3>\n" +
						"<table>" +
						"<tr><td>Type:</td><td>" + challengeData.type_name + "</td></tr>\n" +
						"<tr><td>Organizer:</td><td>" + challengeData.owner_name + "</td></tr>\n" +
						"<tr><td>Start:</td><td>" + challengeData.start_time + "</td></tr>\n" +
						"<tr><td>End:</td><td>" + challengeData.end_time + "</td></tr>\n" +
						"</table>\n"));


		if(isCTF){
			// Stats for capture the flag challenges
			var free = 0;
			var coordCount = 0;
			var stats = new Object();
			for(var i = 0; i < coordData.coords.length; i++){
				if(coordData.coords[i].captured_by == null){
					free++;
				}
				else{
					if(stats.hasOwnProperty(coordData.coords[i].captured_by)){
						stats[coordData.coords[i].captured_by]++;
					}
					else{
						stats[coordData.coords[i].captured_by] = 1;
					}
				}
				coordCount++;
			}

			var txt = "<tr><td>Free</td><td>" + free  + " (" + ((100 / coordCount) * free) + "%)</td></tr>";

			for(var key in stats){
				txt += "<tr><td>" + teamMap[key].name + "</td><td>" + stats[key] + " (" + ((100 / coordCount) * stats[key]) + "%)</td></tr>\n"
			}

			list.append(generateDefaultListItem("Stats", "<table>\n" + txt + "</table>\n"));
		}
		else{
			// Stats for regular challenges
			var coordCount = 0;
			var reachedCount = 0;
			for(var i = 0; i < coordData.coords.length; i++){
				if(coordData.coords[i].reached != null){
					reachedCount++;
				}
				coordCount++;
			}

			list.append(generateDefaultListItem("Stats",
					"<table>\n" +
					"<tr><td>Caches:</td><td>" + coordCount + "</td></tr>\n" +
					"<tr><td>Reached:</td><td>" + reachedCount + " (" + ((100 / coordCount) * reachedCount) + "%)</td></tr>\n" +
					"</table>\n"));
		}

		list.listview('refresh');
	};

	var generateDefaultListItem = function(title, content){
		return	"<li data-role=\"list-divider\">" +
				"<span class=\"listview-left\">" + title + "</span>" +
				"<li data-icon=\"false\">" + content + "</li>\n";
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
				var responseData = null
				try{
					responseData = JSON.parse(response);
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
						updateStatsPanel();
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
						updateStatsPanel();
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
