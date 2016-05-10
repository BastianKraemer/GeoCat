/* GeoCat - Geocaching and -tracking application
 * Copyright (C) 2016 Bastian Kraemer
 *
 * BuddyController.js
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
 * Controller for the GeoCat buddy page
 *
 * @class BuddyController
 */
function BuddyController(){
	var htmlElements = {
		list: "#buddy-list",
		searchContainer: "#buddy-search-container",
		searchInput: "#buddy-search-input",
		searchButton: "#buddy-search-confirm",
		radar: "#buddy-radar"
	};

	var buttons = {
		showList: "#buddies-show-list-btn",
		searchMode: "#buddies-search-mode-btn",
		startStopTacking: "#start-tracking",
		locateBuddies: "#locate-friends"
	};

	var me = this;
	var gpsRadar = null;

	/**
	 * This function is called when the page is opened
	 *
	 * @public
	 * @function pageOpened
	 * @memberOf BuddyController
	 * @instance
	 */
	this.pageOpened = function(){
		if(GeoCat.loginStatus.isSignedIn){
			startup();
		}
		else{
			$.mobile.changePage("#Home");
			setTimeout(function(){
				SubstanceTheme.showNotification(
					"<p>" + GeoCat.locale.get("nologin", "Please sign in to use this feature.") + "</p>", 7,
					$.mobile.activePage[0], "substance-skyblue no-shadow white");
			}, 200);
		}

		var searchAction = function(){
			switch(BuddyController.currentMode){
				case BuddyController.modeTypes.BUDDY_LIST:
					runBuddyFilter($(htmlElements.searchInput).val());
					break;
				case BuddyController.modeTypes.BUDDY_SEARCH:
					searchForBuddies($(htmlElements.searchInput).val());
					break;
			}
		};

		$(htmlElements.searchButton).click(searchAction);
		$(htmlElements.searchInput).keyup(function(e){
			if(e.keyCode == 13){
				searchAction();
			}
		});

		$(buttons.showList).click(function(){
			changeMode(BuddyController.modeTypes.BUDDY_LIST, false);
		});

		$(buttons.searchMode).click(function(){
			changeMode(BuddyController.modeTypes.BUDDY_SEARCH, false);
		});

		$(buttons.startStopTacking).click(function(){

			var stopTrackingCallback = function(){
				SubstanceTheme.showYesNoDialog(	"<p>" + GeoCat.locale.get(
						"tracking.stop",
						"Do you want to stop the GPS tracking?") +
				"</p>",
				$.mobile.activePage[0],
				function(){
					GeoCat.stopGPSTracking();
				}, null, "substance-white no-shadow", true);
			}

			var html;
			var yesCb;
			if(GeoCat.gpsTracker == null){

				var content = document.createElement("div");
				content.className = "button-list";

				buttonCreator(content, GeoCat.locale.get("tracking.button_start", "Start GPS tracking"), function(){
					// This function will be executed when the button is clicked
					SubstanceTheme.showYesNoDialog(
						"<p>" + GeoCat.locale.get(
								"tracking.start",
								"Do you want GeoCat to track your GPS position?<br>" +
								"Your coordinates will be stored on the server and all your buddies will be able to see your position.") +
						"</p>",
					$.mobile.activePage[0],
					function(){
						GeoCat.startGPSTracking(function(track){
							sendCurrentPosition(track[0].coords.latitude, track[0].coords.longitude, function(status){
								if(!status){
									console.log("Error: Unable to send current psoition to server.");
								}
							});
						},
						stopTrackingCallback);
					}, null, "substance-white no-shadow", true);
				});

				buttonCreator(content, GeoCat.locale.get("tracking.button_share", "Share current position"), function(){
					// This function will be executed when the button is clicked
					SubstanceTheme.showNotification("<p>" + GeoCat.locale.get("tracking.uploading", "Uploading GPS position...") + "</p>",
													0, $.mobile.activePage[0], "substance-orange no-shadow white");

					GPS.getOnce(function(pos){
						// This callback is executed when the gps position is available
						sendCurrentPosition(pos.coords.latitude, pos.coords.longitude, function(status){
							// ...and this one after the AJAX requets has finished
							var txt = status ? GeoCat.locale.get("tracking.upload_success", "GPS Upload successfull") : GeoCat.locale.get("tracking.upload_error", "GPS Upload failed");
							SubstanceTheme.showNotification("<p>" + txt + "</p>",
															7, $.mobile.activePage[0], "substance-" + (status ? "green" : "red") + " no-shadow white");
						});
					}, null);
				});

				buttonCreator(content, GeoCat.locale.get("tracking.button_remove", "Remove position from server"), function(){
					sendClearPosition();
				});

				SubstanceTheme.showNotification(content, 0, $.mobile.activePage[0], "substance-skyblue no-shadow");
			}
			else{
				stopTrackingCallback();
			}
		});

		$(buttons.locateBuddies).click(function(){
			changeMode(BuddyController.modeTypes.BUDDY_RADAR, false);
		});
	};

	var buttonCreator = function(container, text, onClick){
		var b = document.createElement("button");
		var p = document.createElement("p");
		b.innerText = text;
		b.onclick = onClick;
		p.appendChild(b);
		container.appendChild(p);
		return b;
	}

	/**
	 * This function is called when the page is closed
	 *
	 * @public
	 * @function pageClosed
	 * @memberOf BuddyController
	 * @instance
	 */
	this.pageClosed = function(){
		stopRadar();
		$(htmlElements.list).empty();
		$(htmlElements.searchButton).unbind();
		$(htmlElements.searchInput).unbind();
		$(buttons.showList).unbind();
		$(buttons.searchMode).unbind();
		$(buttons.startStopTacking).unbind();
		$(buttons.locateBuddies).unbind();
	};

	var startup = function(){
		changeMode(BuddyController.currentMode, true);
	};

	var changeMode = function(mode, noMapDisplay){

		// Stop the radar
		if(mode != BuddyController.modeTypes.BUDDY_RADAR && BuddyController.currentMode == BuddyController.modeTypes.BUDDY_RADAR){
			showRadar(false);
			stopRadar();
		}

		switch(mode){
			case BuddyController.modeTypes.BUDDY_LIST:
				$(BuddyController.pageId + " > div > h1").text(GeoCat.locale.get("buddies.show_list", "My buddies"));

				activateButton(buttons.showList);
				downloadBuddyList();
				break;

			case BuddyController.modeTypes.BUDDY_SEARCH:
				$(BuddyController.pageId + " > div > h1").text(GeoCat.locale.get("buddies.search", "Find buddies"));

				$(htmlElements.list).empty();
				searchForBuddies($(htmlElements.searchInput).val());

				activateButton(buttons.searchMode);
				break;

			case BuddyController.modeTypes.BUDDY_RADAR:
				if(BuddyController.currentMode != BuddyController.modeTypes.BUDDY_RADAR || noMapDisplay){
					// First click on the radar button
					$(BuddyController.pageId + " > div > h1").text(GeoCat.locale.get("buddies.radar", "Buddy radar"));

					activateButton(buttons.locateBuddies);

					if(gpsRadar == null){
						showRadar(true);
						startRadar();
					}
				}
				else{
					// Another click on the radar button
					switchToMapView();
				}
				break;
		}

		BuddyController.currentMode = mode;
	};

	var activateButton = function(btnId){
		for(var key in buttons){
			if(btnId == buttons[key]){
				$(buttons[key]).removeClass("substance-blue");
				$(buttons[key]).addClass("substance-lime")
			}
			else{
				$(buttons[key]).removeClass("substance-lime");
				$(buttons[key]).addClass("substance-blue")
			}
		}
	};

	var downloadBuddyList = function(){
		$.ajax({
			type: "POST", url: "query/buddies.php",
			data: {
				task: "buddylist"
			},
			cache: false,
			success: function(response){
				buildBuddylist(JSON.parse(response));
			},
			error: function(xhr, status, error){
				SubstanceTheme.showNotification("<h3>Unable to dowload buddy list.</h3><p>Please try again later.</p>", 7,
						$.mobile.activePage[0], "substance-red no-shadow white");
			}
		});
	};

	var searchForBuddies = function(searchText){
		if(searchText != ""){
			$.ajax({
				type: "POST", url: "query/buddies.php",
				data: {
					task: "search_buddy",
					searchtext: searchText
				},
				cache: false,
				success: function(response){
					displaySearchResults(JSON.parse(response));
				},
				error: function(xhr, status, error){
					SubstanceTheme.showNotification("<h3>Unable to search for buddies</h3><p>Please try again later.</p>", 7,
							$.mobile.activePage[0], "substance-red no-shadow white");
				}
			});
		}
	};

	var buildBuddylist = function(response){
		var list = $(htmlElements.list);
		list.empty();

		if(response.buddies.length > 0 || response.requests.length > 0){

			for(var i = 0; i < response.requests.length; i++){
				list.append(generateTitleLi(response.requests[i], BuddyType.REQUEST));
				list.append(generateContentLi(response.requests[i], BuddyType.REQUEST));
			}

			for(var i = 0; i < response.buddies.length; i++){
				var type = (response.buddies[i].confirmed == "yes" ? BuddyType.BUDDY : BuddyType.WAITING);

				list.append(generateTitleLi(response.buddies[i], type));
				list.append(generateContentLi(response.buddies[i], type));
			}
		}
		else{
			list.append("<li style=\"white-space: normal\">" + GeoCat.locale.get("buddies.empty_list", "You don't have added any buddies to your buddy list yet") + "</li>");
		}

		list.listview('refresh');
	};

	var displaySearchResults = function(response){
		var list = $(htmlElements.list);
		list.empty();

		var listLength = 0;

		if(response.status == "ok"){
			for(var i = 0; i < response.matches.length; i++){
				if(!response.matches[i].isFriend){
					list.append(generateTitleLi(response.matches[i], BuddyType.ADD));
					list.append(generateContentLi(response.matches[i], BuddyType.ADD));
					listLength++;
				}
			}
		}

		if(listLength == 0){
			list.append("<li>" + GeoCat.locale.get("buddies.buddies.no_results", "No matches found.") + "</li>");
		}

		list.listview('refresh');
	};

	/*
	 * BUDDY: Both accounts have each other as friend
	 * REQUEST: Someone offers me a friend request
	 * WAITING: I am waiting that somone accepts my friend request
	 */
	var BuddyType = {
		BUDDY: 0,
		REQUEST: 1,
		WAITING: 2,
		ADD: 3
	};

	var generateTitleLi = function(buddy, buddyType){
		var li = document.createElement("li");
		li.setAttribute("data-role", "list-divider");

		var spanName = document.createElement("span");
		spanName.className = "listview-left";
		spanName.textContent = buddy.username;

		li.appendChild(spanName);
		setLiAttributes(li, buddy);

		if(buddyType == BuddyType.WAITING || buddyType == BuddyType.REQUEST){
			var spanStatus = document.createElement("span");
			spanStatus.className = "listview-right";

			if(buddyType == BuddyType.WAITING){
				spanStatus.textContent = GeoCat.locale.get("buddies.status.waiting", "Not accepted");
				spanStatus.style.borderBottom = "2px solid orange";
			}
			else{
				spanStatus.textContent = GeoCat.locale.get("buddies.status.req", "Request");
				spanStatus.style.borderBottom = "2px solid rgb(111, 200, 40)";
			}

			li.appendChild(spanStatus);
		}

		return li;
	};

	var generateContentLi = function(buddy, buddyType){

		var li = document.createElement("li");
		var a = document.createElement("a");

		a.className = "li-clickable";
		a.onclick = function(){};

		if(buddy.firstname != null || buddy.lastname != null){
			var h = document.createElement("h2");
			h.textContent = (buddy.firstname != null ? buddy.firstname : "") + " " + (buddy.lastname != null ? buddy.lastname : "");
			a.appendChild(h);
		}

		var p = document.createElement("p");
		p.innerHTML = GeoCat.locale.get("buddies.last_pos_update", "Last position update") + ": " + (buddy.pos_timestamp == null ? "-" : buddy.pos_timestamp);

		var b = document.createElement("a");

		if(buddyType == BuddyType.BUDDY || buddyType == BuddyType.WAITING){
			setRemoveUserButton(b, buddy.username, li);
		}
		else{
			b.onclick = function(){sendAddBuddy(buddy.username, li, buddyType != BuddyType.REQUEST);}
			b.className = "ui-icon-check";
			b.textContent = GeoCat.locale.get("buddies.add", "Add buddy to list");
		}

		a.appendChild(p);

		li.appendChild(a);
		li.appendChild(b);
		setLiAttributes(li, buddy);

		return li;
	};

	var setRemoveUserButton = function(btn, username, li){
		btn.onclick = function(){sendRemoveBuddy(username, li);}
		btn.className = "ui-icon-delete ui-btn ui-btn-icon-notext";
		btn.title = GeoCat.locale.get("buddies.remove", "Remove buddy from list");
	}

	var setLiAttributes = function(li, buddy){
		li.setAttribute("data-username", buddy.username);
		li.setAttribute("data-firstname", buddy.firstname);
		li.setAttribute("data-lastname", buddy.lastname);
	};

	var runBuddyFilter = function(searchKey){
		var liNodes = $(htmlElements.list)[0].childNodes;
		if(liNodes.length <= 1){return;}

		if(searchKey == ""){
			for(var i = 0; i < liNodes.length; i++){
				liNodes[i].style.display = "block";
			}
		}
		else{
			var regEx = new RegExp(searchKey);
			for(var i = 0; i < liNodes.length; i++){
				var li = liNodes[i];
				if(	regEx.exec(li.getAttribute("data-username")) != null ||
					regEx.exec(li.getAttribute("data-firstname")) != null ||
					regEx.exec(li.getAttribute("data-lastname")) != null){
					li.style.display = "block";
				}
				else{
					li.style.display = "none";
				}
			}
		}
	};

	var showRadar = function(value){
		if(value){
			$(htmlElements.searchContainer).hide();
			$(htmlElements.list).hide();
			$(htmlElements.radar).show();
		}
		else{
			$(htmlElements.radar).hide();
			$(htmlElements.searchContainer).show();
			$(htmlElements.list).show();
		}
	};

	var switchToMapView = function(){
		stopRadar();
		var mapController = MapController.prepareMap(MapController.MapTask.SHOW_COORDS, {returnTo: BuddyController.pageId, coords: []});

		var callback = function(){
			receiveBuddyPosition(function(coords){
				mapController.updateCoords(coords);
			});
		}

		var interval = setInterval(function(){
			callback();
		}, 30000);

		me.showSubPage(function(){
			clearInterval(interval);
			startRadar();
		});

		MapController.showPreparedMap(mapController);
		setTimeout(callback, 1000);
	};

	var startRadar = function(){
		var radarInstance = new GPSRadar($("#" + $.mobile.activePage[0].id + " > div.ui-content")[0], $(htmlElements.radar + " > canvas")[0]);
		var buddyPosition = null;

		var downloadCallback = function(coords){
			buddyPosition = coords;
		};

		var radarUpdateTimer = setInterval(function(){
			radarInstance.update(buddyPosition, {}, {});
		}, 2000);

		var positionDownloadTimer = setInterval(function(){
			receiveBuddyPosition(downloadCallback);
		}, 30000);

		receiveBuddyPosition(downloadCallback);

		gpsRadar = {
				radar: radarInstance,
				updateTimer: radarUpdateTimer,
				downloadTimer: positionDownloadTimer
		};

		radarInstance.start();
	};

	var stopRadar = function(){
		if(gpsRadar != null){
			gpsRadar.radar.stop();
			clearInterval(gpsRadar.updateTimer);
			clearInterval(gpsRadar.downloadTimer);
			gpsRadar = null;
		}
	};

	var sendAddBuddy = function(username, element, removeLi){
		$.ajax({
			type: "POST", url: "query/buddies.php",
			data: {
				task: "add_buddy",
				username: username
			},
			cache: false,
			success: function(response){

				var res = JSON.parse(response);

				if(res.status == "ok"){
					SubstanceTheme.showNotification(
						"<p>" + sprintf(GeoCat.locale.get("buddies.added", "'{0}' has been added to your buddy list"), [username]) + "</p>", 7,
						$.mobile.activePage[0], "substance-green no-shadow white");

					if(removeLi){
						var titleLi = $(element).prev();
						$(titleLi).slideUp('fast', function(){ $(titleLi).remove(); });
						$(element).slideUp('fast', function(){ $(element).remove(); });
					}
					else{
						var titleLi = $(element).prev();
						$(titleLi).find("span.listview-right").remove();
						setRemoveUserButton($(element).find("a.ui-icon-check")[0], $(element).attr("data-username"), element);
					}
				}
				else{
					SubstanceTheme.showNotification("<p>" + res.msg + "</p>", 7, $.mobile.activePage[0], "substance-red no-shadow white");
				}
			},
			error: function(xhr, status, error){
				SubstanceTheme.showNotification("<h3>Unable to add buddy to buddy list</h3><p>Please try again later.</p>", 7,
						$.mobile.activePage[0], "substance-red no-shadow white");
			}
		});
	};

	var sendRemoveBuddy = function(username, element){
		$.ajax({
			type: "POST", url: "query/buddies.php",
			data: {
				task: "remove_buddy",
				username: username
			},
			cache: false,
			success: function(response){

				var res = JSON.parse(response);

				if(res.status == "ok"){
					SubstanceTheme.showNotification(
						"<p>" + sprintf(GeoCat.locale.get("buddies.removed", "'{0}' has been removed from your buddy list"), [username]) + "</p>", 7,
						$.mobile.activePage[0], "substance-green no-shadow white");

					var titleLi = $(element).prev();
					$(titleLi).slideUp('fast', function(){ $(titleLi).remove(); });
					$(element).slideUp('fast', function(){ $(element).remove(); });
				}
				else{
					SubstanceTheme.showNotification("<p>" + res.msg + "</p>", 7, $.mobile.activePage[0], "substance-red no-shadow white");
				}
			},
			error: function(xhr, status, error){
				SubstanceTheme.showNotification("<h3>Unable to remove buddy from buddy list</h3><p>Please try again later.</p>", 7,
						$.mobile.activePage[0], "substance-red no-shadow white");
			}
		});
	};

	var sendCurrentPosition = function(lat, lon, callback){
		$.ajax({
			type: "POST", url: "query/buddies.php",
			data: {
				task: "upload_position",
				lat: lat,
				lon: lon
			},
			cache: false,
			success: function(response){

				var res = JSON.parse(response);

				if(callback != null){
						callback(res.status == "ok");
				}
			},
			error: function(xhr, status, error){
				console.log("ERROR: GPS position upload failed: " + error);
			}
		});
	};

	var sendClearPosition = function(lat, lon, callback){
		$.ajax({
			type: "POST", url: "query/buddies.php",
			data: {
				task: "clear_position"
			},
			cache: false,
			success: function(response){

				var res = JSON.parse(response);

				SubstanceTheme.showNotification(
						"<p>" + res.msg + "</p>", 7,
						$.mobile.activePage[0], "substance-green no-shadow white");
			},
			error: function(xhr, status, error){
				console.log("ERROR: Unable to remove GPS position from Server: " + error);
			}
		});
	};

	var receiveBuddyPosition = function(callback){
		$.ajax({
			type: "POST", url: "query/buddies.php",
			data: {
				task: "get_buddy_positions"
			},
			cache: false,
			success: function(response){
				var result = JSON.parse(response);
				if(result.status == "ok"){
					callback(result.coords);
				}
			},
			error: function(xhr, status, error){
				console.log("ERROR: Unable to receive buddy positions from server: " + error);
			}
		});
	};
}

BuddyController.modeTypes = {
	BUDDY_LIST: 0,
	BUDDY_SEARCH: 1,
	BUDDY_RADAR: 2
};

BuddyController.currentMode = BuddyController.modeTypes.BUDDY_LIST;

BuddyController.init = function(myPageId){
	BuddyController.pageId = myPageId;
	BuddyController.prototype = new PagePrototype(myPageId, function(){
		return new BuddyController();
	});
};
