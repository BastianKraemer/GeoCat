function ChallengeNavigatorController(challenge_id){

	var htmlElement = new Object();
	htmlElement["coord_panel"] = "#challenge-navigator-coord-panel";
	htmlElement["coord_list"] = "#challenge-navigator-coord-list";
	htmlElement["stats"] = "#challenge-navigator-stats";
	htmlElement["canvas"] = "#challenge-navigator-canvas";
	htmlElement["canvas_container"] = "#challenge-navigator-content";
	htmlElement["codeinput_popup"] = "#code-input-popup";
	htmlElement["codeinput_hint"] = "#checkpoint-code-input-hint";
	htmlElement["codeinput_textfield"] = "#checkpoint-code-input";
	htmlElement["codeinput_ok"] = "#checkpoint-code-input-ok";
	htmlElement["reached_button"] = "#checkpoint-reached-button";
	htmlElement["reload_button"] = "#challenge-navigator-update-button";
	htmlElement["autohide_flipswitch"] = "#challenge-navigator-autohide";

	var minDistanceToSetReached = 20; // In meters

	var challengeKey = challenge_id;
	var challengeData = null;
	var isCTF = false;
	var coordData = null; //The respone from the server
	var coordList; // The map for the GPSRadar
	var iconList;
	var colorList;
	var visibilityList = new Object();
	var teamMap;
	var order;
	var currentPriority = 0;

	var displayCurrentPriorityItemsOnly = true;
	var showPriorities = false;

	var gpsRadar = null;

	this.pageOpened = function(){

		$(htmlElement["codeinput_ok"]).click(codeInputOnClick);
		$(htmlElement["codeinput_textfield"]).keyup(function(e){
			if(e.keyCode == 13){
				codeInputOnClick();
			}
		});

		$(htmlElement["reload_button"]).click(function(e){
			getChallengeInformation();
		});

		$(htmlElement["reached_button"]).click(function(e){
			setReached();
		});

		$(htmlElement["coord_panel"]).on("panelbeforeopen", updateListPanel);

		$(htmlElement["autohide_flipswitch"]).bind("change", function(event, ui){
			displayCurrentPriorityItemsOnly = $(htmlElement["autohide_flipswitch"]).is(":checked");
		});

		getChallengeInformation();
	};

	this.pageClosed = function(){
		$(htmlElement["codeinput_ok"]).unbind();
		$(htmlElement["codeinput_textfield"]).unbind();
		$(htmlElement["reload_button"]).unbind();
		$(htmlElement["reached_button"]).unbind();
		$(htmlElement["coord_panel"]).unbind();
		$(htmlElement["autohide_flipswitch"]).unbind();

		// Close any opened notifications
		if(SubstanceTheme.previousNotification != null){
			SubstanceTheme.previousNotification.hide();
		}
	};

	var codeInputOnClick = function(){
		sendCapturedOrReached($(htmlElement["codeinput_popup"]).attr("data-ccid"), $(htmlElement["codeinput_textfield"]).val());
		$(htmlElement["codeinput_textfield"]).val("");
		$(htmlElement["codeinput_popup"]).popup("close");
	};

	var getChallengeInformation = function(){
		$.ajax({
			type: "POST", url: "./query/challenge.php",
			encoding: "UTF-8",
			data: {task: "device_start", challenge: challengeKey},
			cache: false,
			success: function(response){
				try{
					var responseData = JSON.parse(response);
					if(responseData.status == "ok"){
						onChallengeDataReceived(responseData);
					}
					else{
						SubstanceTheme.showNotification("<h3>" + GeoCat.locale.get("challenge.nav.error.download_info", "Unable to download challenge information") + "</h3>" +
														"<p>" + responseData.msg + "</p>", 7, $.mobile.activePage[0], "substance-red no-shadow white");
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
			encoding: "UTF-8",
			data: {task: "status", challenge: challengeKey},
			cache: false,
			success: function(response){
				try{
					var responseData = JSON.parse(response);
					if(responseData.status == "ok"){
						onCoordinateDataReceived(responseData);
					}
					else{
						SubstanceTheme.showNotification("<h3>" + GeoCat.locale.get("challenge.nav.error.download_caches", "Unable to download cache positions") + "</h3>" +
														"<p>" + responseData.msg + "</p>", 7, $.mobile.activePage[0], "substance-red no-shadow white");
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

		generateOrderByPriority();
		updateCurrentPriority();
		updateCoordList();
		updateStatsPanel();

		if(gpsRadar == null){
			start();
		}
		else{
			// Seems that the user has pressed the update button
			SubstanceTheme.showNotification("<p>" + GeoCat.locale.get("challenge.nav.updated", "Update successful") + "</p>", 3,
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
			if(setCacheStyle(c.coord_id, c)){
				coordList[c.coord_id] = new Coordinate(c.coord_id, c.name, c.latitude, c.longitude, c.decription, false);
				visibilityList[c.coord_id] = true;
			}
			else{
				visibilityList[c.coord_id] = false;
			}
		}
	};

	var setCacheStyle = function(id, coord){
		if(isCTF){
			if(currentPriority == 0){
				if(c.priority > 0){
					return false;
				}
			}
			else{
				if(coord.captured_by != null){
					colorList[id] = teamMap[coord.captured_by].color;
				}
				else{
					iconList[id] = GPSRadar.CoordinateIcon.CIRCLE;
				}
			}
		}
		else{
			if(coord.priority <= currentPriority){
				if(coord.reached == null){
					colorList[id] = "#000";
					iconList[id] = GPSRadar.CoordinateIcon.CIRCLE;
				}
				else{
					colorList[id] = challengeData.team_color;

					if(displayCurrentPriorityItemsOnly && coord.priority < (currentPriority - 1)){
						// Hide previously reached caches
						return false;
					}
				}
			}
			else {
				colorList[id] = "#949494";
				iconList[id] = GPSRadar.CoordinateIcon.CIRCLE;

				if(displayCurrentPriorityItemsOnly && (coord.priority > (currentPriority + 1))){
					// Hide caches with priority + 2 or more
					return false;
				}
			}
		}
		return true; // return value: is this cache visible?
	};

	var generateOrderByPriority = function(currentPriority){
		order = new Array();
		coordData.coords.forEach(function(coord){
			for(var i = 0; i < order.length; i++){
				if(coord.priority <= order[i].priority){
					order.splice(i, 0, coord);
					return;
				}
			}
			order.push(coord);
		});
	}

	var updateCurrentPriority = function(){
		if(isCTF){
			if(order.length > 0){
				if(order[0].reached != null){
					// This team has reached the start position, there is no further priority in CTF challenges
					currentPriority = 1;
				}
			}
			else{
				// The team has to reach the start point at first
				currentPriority = 0;
			}
		}
		else{
			for(var i = 0; i < order.length; i++){
				if(order[i].reached == null){
					// The new priority is the priority of the first cache that is not reached
					currentPriority = order[i].priority;
					return;
				}
			}
		}
	}

	var updateListPanel = function(){
		var list = $(htmlElement["coord_list"]);
		list.empty();

		if(coordData != null){
			var pos = GPS.get();

			for(var i = 0; i < order.length; i++){
				list.append(generateItem(order[i].name,
								isCTF && order[i].priority != 0 ? order[i].capture_time : order[i].reached,
								isCTF ? order[i].captured_by : null,
								(pos == null ? "-" : (GeoTools.calculateDistance(pos.coords.longitude, pos.coords.latitude, order[i].longitude, order[i].latitude) * 1000).toFixed(1) + " m"),
								isVisible(order[i].coord_id), i, order[i].priority, ((isCTF && currentPriority == 0) || (!isCTF && order[i].priority == currentPriority))));
			}

			$(htmlElement["coord_list"] + " li.li-clickable, " + htmlElement["coord_list"] + " li a.li-clickable").click(function(){
				listItemOnClick(this);
			});

			$(htmlElement["coord_list"] + " li a.show-coord").click(function(){
				toogleVisibilityOnClick(this);
			});

			list.listview('refresh');
		}
	};

	var generateItem = function(name, reachedTime, capturedBy, distance, isVisible, index, priority, highlight){

		var prefix = "", textHighlightClass = "", bgHighlightClass = "";
		var CacheStyle = {REGULAR: 0, STRIKEOUT: 1, HIGHLIGHTED: 2, REACHED: 3, CAPTURED: 4};

		var style = CacheStyle.REGULAR;

		if(!isCTF || priority == 0){
				style = (reachedTime == null ? (priority == currentPriority ? CacheStyle.HIGHLIGHTED  : CacheStyle.REGULAR ) : CacheStyle.REACHED);
		}
		else if(isCTF){
			if(capturedBy != null){
				style = (capturedBy == challengeData.team ? CacheStyle.CAPTURED : CacheStyle.STRIKEOUT);
			}
			else{
				style = CacheStyle.HIGHLIGHTED;
			}
		}

		switch(style){
			case CacheStyle.HIGHLIGHTED:
				prefix = "&#10148; ";
				bgHighlightClass = "active-cache ";
				textHighlightClass = "white ";
				break;
			case CacheStyle.CAPTURED:
				prefix = "<span>&checkmark;&nbsp;</span>";
				bgHighlightClass = "reached-cache ";
				break;
			case CacheStyle.REACHED:
				prefix = "<span>&checkmark;&nbsp;</span>";
				bgHighlightClass = "reached-cache ";
				// no break here
			case CacheStyle.STRIKEOUT:
				name = "<strike>" + name + "</strike>"
				break;
		}

		return	"<li data-role=\"list-divider\" data-index=\"" + index + "\" class=\"" + bgHighlightClass + "li-clickable\">" +
					"<span class=\"" + textHighlightClass + " no-shadow listview-left\">" + prefix + name + "</span>" +
					(showPriorities ? "<span class=\"" + textHighlightClass + " no-shadow listview-right\">" + priority + "</span>" : "") +
				"</li>" +
				"<li data-icon=\"true\">" +
					"<a class=\"li-clickable\" data-index=\"" + index + "\">" +
						"<p>" + GeoCat.locale.get("challenge.nav.distance", "Distance") + ": " + distance + "</p>" +
						"<p>" + GeoCat.locale.get("challenge.nav.reached", "Reached") + ": " + (reachedTime == null ? "-" : reachedTime) + "</p>" +
						(capturedBy != null ? "<p style=\"color: " + teamMap[capturedBy].color + "\">" +
							GeoCat.locale.get("challenge.nav.captured_by", "Captured by") + ": " + teamMap[capturedBy].name + "</p>" : "") +
					"</a>" +
					"<a class=\"" + (isVisible ? "ui-icon-eye " : "") + "show-coord\" data-index=\"" + index + "\">" +
						GeoCat.locale.get("challenge.nav.show", "Start navigation") +
					"</a>" +
				"</li>\n";
	};

	var listItemOnClick = function(el){
		var data = coordData.coords[$(el).attr("data-index")];
		if(data.hint != null){
			SubstanceTheme.showNotification("<h3>" + GuiToolkit.sprintf(GeoCat.locale.get("challenge.nav.hint_for", "Hint for cache '{0}'"), [data.name]) + "</h3>" +
											"<p>" + data.hint + "</p>", -1,	$.mobile.activePage[0], "substance-skyblue no-shadow white");
		}
		else{
			SubstanceTheme.showNotification("<p>" + GuiToolkit.sprintf(GeoCat.locale.get("challenge.nav.nohint", "There is no hint for cache '{0}' available"), [data.name]) + "</p>", 7,
											$.mobile.activePage[0], "substance-skyblue no-shadow white");
		}
	};

	var toogleVisibilityOnClick = function(el){
		var coord = coordData.coords[$(el).attr("data-index")];
		var id = coord.coord_id;
		if(isVisible(id)){
			visibilityList[id] = false;
			delete coordList[id];
			$(el).removeClass("ui-icon-eye");
		}
		else{
			visibilityList[id] = true;
			coordList[id] = new Coordinate(id, coord.name, coord.latitude, coord.longitude, coord.decription, false);
			$(el).addClass("ui-icon-eye");
		}
	};

	var isVisible = function(id){
		if(visibilityList.hasOwnProperty(id)){
			return visibilityList[id];
		}
		return true;
	};

	var updateStatsPanel = function(){

		var list = $(htmlElement["stats"]);
		list.empty();

		var endTime = challengeData.end_time == null ? "-" : challengeData.end_time;

		list.append(generateDefaultListItem(GeoCat.locale.get("challenge.nav.challenge_info", "Challenge Information"),
						"<h3>" + challengeData.name + "</h3>\n" +
						"<table>" +
						"<tr><td>" + GeoCat.locale.get("challenge.nav.typeinfo", "Type") + ":</td><td>" + challengeData.type_name + "</td></tr>\n" +
						"<tr><td>" + GeoCat.locale.get("challenge.nav.ownerinfo", "Organizer") + ":</td><td>" + challengeData.owner_name + "</td></tr>\n" +
						"<tr><td>" + GeoCat.locale.get("challenge.nav.startinfo", "Start") + "::</td><td>" + challengeData.start_time + "</td></tr>\n" +
						"<tr><td>" + GeoCat.locale.get("challenge.nav.endinfo", "End") + ":</td><td>" + endTime + "</td></tr>\n" +
						"</table>\n"));


		if(isCTF){
			// Stats for capture the flag challenges
			var free = 0;
			var coordCount = 0;
			var stats = new Object();
			for(var i = 0; i < coordData.coords.length; i++){
				if(coordData.coords[i].priority == 0){continue;}
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

			var txt = "<tr><td>" + GeoCat.locale.get("challenge.nav.free_caches", "Free") + "</td><td>" + free  + " (" + ((100 / coordCount) * free).toFixed(1) + "%)</td></tr>";

			for(var key in stats){
				txt += "<tr><td>" + teamMap[key].name + "</td><td>" + stats[key] + " (" + ((100 / coordCount) * stats[key]).toFixed(1) + "%)</td></tr>\n"
			}

			list.append(generateDefaultListItem(GeoCat.locale.get("challenge.nav.stats", "Stats"), "<table>\n" + txt + "</table>\n"));

			if(free == 0){showAllCachesReachedOrCapturedNotification();}
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

			list.append(generateDefaultListItem(GeoCat.locale.get("challenge.nav.stats", "Stats"),
					"<table>\n" +
					"<tr><td>" + GeoCat.locale.get("challenge.nav.caches", "Caches") + "</td><td>" + coordCount + "</td></tr>\n" +
					"<tr><td>" + GeoCat.locale.get("challenge.nav.reached", "Reached") + ":</td><td>" + reachedCount + " (" + ((100 / coordCount) * reachedCount).toFixed(1) + "%)</td></tr>\n" +
					"</table>\n"));

			if(reachedCount == coordCount){showAllCachesReachedOrCapturedNotification();}
		}

		list.listview('refresh');
	};

	var generateDefaultListItem = function(title, content){
		return	"<li data-role=\"list-divider\">" +
				"<span class=\"listview-left\">" + title + "</span>" +
				"<li data-icon=\"false\">" + content + "</li>\n";
	};

	var showAllCachesReachedOrCapturedNotification = function(){
		setTimeout(function(){
			SubstanceTheme.showNotification("<h3>" + GeoCat.locale.get("challenge.nav.finished.title", "You have finished this challenge") + "</h3>" +
											"<p>" + GeoCat.locale.get("challenge.nav.finished.text", "Go back to the challenge overview to take a look at the ranking") + "</p>",
											-1, $.mobile.activePage[0], "substance-green no-shadow white");
		}, 3000);
	};

/*
 * ============================================================================
 *  Send 'reached' / 'captured' to server
 * ============================================================================
 */

	var sendCapturedOrReached = function (coord, cacheCode){
		if(isCTF && coord.priority > 0){ // is "Capture the Flag" challenge or the start point
			sendCheckpointCaptured(coord.challenge_coord_id, cacheCode);
		}
		else{
			if(coord.priority == currentPriority){
				sendCheckpointReached(coord.challenge_coord_id, cacheCode);
			}
			else{
				SubstanceTheme.showNotification("<h3>" + GeoCat.locale.get("challenge.nav.priority_violation.title", "Priority violation") + "</h3>" +
												"<p>" + GeoCat.locale.get("challenge.nav.priority_violation.text", "You have to reach at least one other caches first, before you can set this cache as 'reached'!") + ".</p>",
												7, $.mobile.activePage[0], "substance-red no-shadow white");
			}
		}
	};

	var sendCheckpointReached = function(challengeCoordId, cacheCode){
		$.ajax({
			type: "POST", url: "./query/challenge.php",
			encoding: "UTF-8",
			data: {
				task: "checkpoint",
				challenge: challengeKey,
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
								updateCurrentPriority();
								SubstanceTheme.showNotification("<p>" + GeoCat.locale.get("challenge.nav.tagged", "Checkpoint has been successfully tagged as 'reached'") + "</p>",
																7, $.mobile.activePage[0], "substance-green no-shadow white");
								break;
							}
						}

						updateCoordList();
						updateStatsPanel();
					}
					else{
						SubstanceTheme.showNotification("<h3>" + GeoCat.locale.get("challenge.nav.error.update_checkpoint", "Unable to update checkpoint") + "</h3>" +
								"						<p>" + responseData.msg + "</p>", 7, $.mobile.activePage[0], "substance-red no-shadow white");
					}
				}
				catch(e){
					SubstanceTheme.showNotification("<h3>" + GeoCat.locale.get("challenge.nav.error.update_checkpoint", "Unable to update checkpoint") + "</h3>" +
													"<p>" + response + "</p>", 7, $.mobile.activePage[0], "substance-red no-shadow white");
				}
			},
			error: ajaxError
		});
	};

	var sendCheckpointCaptured = function(challengeCoordId, cacheCode){
		$.ajax({
			type: "POST", url: "./query/challenge.php",
			encoding: "UTF-8",
			data: {
				task: "capture",
				challenge: challengeKey,
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
								SubstanceTheme.showNotification("<p>" + GeoCat.locale.get("challenge.nav.captured", "Checkpoint has been successfully captured") + "</p>",
																7, $.mobile.activePage[0], "substance-green no-shadow white");
								break;
							}
						}

						updateCoordList();
						updateStatsPanel();
					}
					else{
						SubstanceTheme.showNotification("<h3>" + GeoCat.locale.get("challenge.nav.error.capture_checkpoint", "Unable to capture this checkpoint") + "</h3>" +
														"<p>" + responseData.msg + "</p>", 7, $.mobile.activePage[0], "substance-red no-shadow white");
					}
				}
				catch(e){
					SubstanceTheme.showNotification("<h3>" + GeoCat.locale.get("challenge.nav.error.capture_checkpoint", "Unable to capture this checkpoint") + "</h3>" +
													"<p>" + response + "</p>", 7, $.mobile.activePage[0], "substance-red no-shadow white");
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
			SubstanceTheme.showNotification("<p>" + GeoCat.locale.get("challenge.nav.gps_not_available ", "The GPS position is currently not available - please wait until the GPS position is fixed") + "</p>", 7,
											$.mobile.activePage[0], "substance-skyblue no-shadow white");
			return;
		}

		// Find the coordinate with the lowsest distance to the device
		var coord = null;
		var lowestDist = minDistanceToSetReached;
		for(var i = 0; i < coordData.coords.length; i++){
			var c = coordData.coords[i];
			var distanceInMeter = GeoTools.calculateDistance(myPos.coords.latitude, myPos.coords.longitude, c.latitude, c.longitude) * 1000;
			if(distanceInMeter < lowestDist){
				ilowestDist = distanceInMeter;
				coord = c;
			}
		}

		if(coord != null){
			if(isCTF){
				if(coord.captured_by != null){
					SubstanceTheme.showNotification("<p>" + GeoCat.locale.get("challenge.nav.already_captured", "This cache has been already captured") + "</p>", 7,
													$.mobile.activePage[0], "substance-skyblue no-shadow white");
					return; //cancel
				}
			}
			else{
				if(coord.reached != null){
					SubstanceTheme.showNotification("<p>" + GeoCat.locale.get("challenge.nav.already_tagged", "You have already tagged this cache as 'reached'") + "</p>", 7,
													$.mobile.activePage[0], "substance-skyblue no-shadow white");
					return; //cancel
				}
			}

			if(coord.code_required == 1){
				$(htmlElement["codeinput_popup"]).attr("data-ccid", coord.challenge_coord_id);
				$(htmlElement["codeinput_popup"]).popup("open", {positionTo: "window", transition: "pop"});
				if(coord.hint == null){
					$(htmlElement["codeinput_hint"]).css("height", "0").css("visibility", "hidden");
				}
				else{
					$(htmlElement["codeinput_hint"]).css("height", "auto").css("visibility", "shown");
					$(htmlElement["codeinput_hint"])[0].innerHTML = "<u>" + GeoCat.locale.get("challenge.nav.hint", "Hint") + ":</u> " + coord.hint;
				}

				$(htmlElement["codeinput_textfield"]).select();
			}
			else{
				sendCapturedOrReached(coord, null);
			}
		}
		else{
			SubstanceTheme.showNotification("<p>" + GuiToolkit.sprintf(GeoCat.locale.get("challenge.nav.too_far_away", "You have to get closer to a cache before you can set the point as 'reached' (less than {0} m)"), [minDistanceToSetReached]) + "</p>", 7,
											$.mobile.activePage[0], "substance-skyblue no-shadow white");
		}
	};

	var ajaxError = function(xhr, status, error){
		SubstanceTheme.showNotification("<h3>Unknown error - Ajax request failed</h3><p>" + error + "</p>", 7,
				$.mobile.activePage[0], "substance-red no-shadow white");
	};
}

ChallengeNavigatorController.currentInstance = null;

ChallengeNavigatorController.openPage = function(){
	var key = location.search;
	if(key == ""){
		SubstanceTheme.showNotification("<h3>Invalid Sessionkey</h3>", 7, $.mobile.activePage[0], "substance-red no-shadow white")
	}
	else{
		ChallengeNavigatorController.currentInstance = new ChallengeNavigatorController(key.slice(1));
		ChallengeNavigatorController.currentInstance.pageOpened();
	}
};

ChallengeNavigatorController.closePage = function(){
	ChallengeNavigatorController.currentInstance.pageClosed();
	ChallengeNavigatorController.currentInstance = null;
};

ChallengeNavigatorController.init = function(){
	$(document).on("pageshow", "#ChallengeNavigator", ChallengeNavigatorController.openPage);
	$(document).on("pagebeforehide", "#ChallengeNavigator", ChallengeNavigatorController.closePage);
};
