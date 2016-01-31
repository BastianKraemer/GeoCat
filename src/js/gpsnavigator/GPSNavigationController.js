/*	GeoCat - Geolocation caching and tracking platform
	Copyright (C) 2015-2016 Bastian Kraemer

	GPSNavigationController.js

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
 * Event handling for the "GPS Navigtor" page
 * @class GPSNavigationController
 */
function GPSNavigationController(){

	// Private variables

	var localCoordStore = GeoCat.getLocalCoordStore();
	var uplink = GeoCat.getUplink();
	var gpsRadar = null;
	var updateTimer = null; // Interval to update the gps Radar

	// Collection (Map) of all important HTML elements (defeined by their id)
	var htmlElement = new Object();
	htmlElement["coordinate_list"] = "#CurrentDestinationList";
	htmlElement["coordinate_panel"] = "#CurrentDesitionListPanel";
	htmlElement["popup"] = "#GPSNavDestListPopup";
	htmlElement["popup_button_save"] = "#GPSNavDestListPopup_Save";
	htmlElement["popup_button_close"] = "#GPSNavDestListPopup_Close";
	htmlElement["popup_checkbox_ownplaces"]  = "#GPSNavDestListPopup_Add2OwnPlaces";
	htmlElement["button_add_coordinate"] = "#GPSNavigator_AddCoordinate";
	htmlElement["field_name"] = "#GPSNavDestListPopup_Name";
	htmlElement["field_lat"] = "#GPSNavDestListPopup_Lat";
	htmlElement["field_lon"] = "#GPSNavDestListPopup_Lon";
	htmlElement["field_desc"] = "#GPSNavDestListPopup_Desc";
	htmlElement["preferences_panel"] = "#GPSNavigatorPreferencesPanel";
	htmlElement["flipswitch_pref_rotate"] = "#GPSNavDisableRotation";
	htmlElement["flipswitch_pref_debuginfo"] = "#GPSNavShowDebugInfo";
	htmlElement["flipswitch_offline_mode"] = "#GPSNavOfflineMode";

	/*
	 * ============================================================================================
	 * Public methods
	 * ============================================================================================
	 */

	/**
	 * This function should be called when the "GPS navigator" page is opened
	 *
	 * @public
	 * @function onPageOpened
	 * @memberOf GPSNavigationController
	 * @instance
	 */
	this.onPageOpened = function(){

		// Download the latest navigation list from the server
		downloadNavListFromServer();

		// Append some event handler

		$(htmlElement["coordinate_panel"]).on("panelbeforeopen", function(){
			updateCurrentDestinationList();
		});

		// Button "Add coordinate"
		$(htmlElement["button_add_coordinate"]).click(function(e){
			var lastGPSPos = GPS.get();
			if(lastGPSPos != null){
				showCoordinateEditDialog(null, "", "", lastGPSPos.coords.latitude, lastGPSPos.coords.longitude, true);
			}
			else{
				Tools.showPopup("Notification", "Unable to get current GPS position.", "OK", function(){resetActiveButtonState(htmlElement["button_add_coordinate"]);});
			}
		});

		// When button "Save" is clicked
		$(htmlElement["popup_button_save"]).click(editDialog_SaveButton_OnClick);

		// When button "Close dialog" is closed
		$(htmlElement["popup_button_close"]).click(function(){
			$(htmlElement["popup"]).popup("close");
		});

		// When the edit dialog has been closed
		$(htmlElement["popup"]).on("popupafterclose", function(event, ui){
			resetActiveButtonState(htmlElement["button_add_coordinate"]);
		});

		// When the preferences panel is opened
		/*$(htmlElement["preferences_panel"]).on("panelafteropen", function(){
			$(htmlElement["flipswitch_pref_rotate"]).val(getPreference("rotate")).slider("refresh");
			$(htmlElement["flipswitch_pref_debuginfo"]).val(getPreference("debug_info")).slider("refresh");
			$(htmlElement["flipswitch_offline_mode"]).val(getPreference("offline_mode")).slider("refresh");
		});*/

		/*bindPreferenceChangeEvent(htmlElement["flipswitch_pref_rotate"], "rotate");
		bindPreferenceChangeEvent(htmlElement["flipswitch_pref_debuginfo"], "debug_info");
		bindPreferenceChangeEvent(htmlElement["flipswitch_offline_mode"], "offline_mode");*/

		gpsRadar = new GPSRadar($("#gpsnavigator_content")[0], $("#NavigatorCanvas")[0]);
		gpsRadar.start();
		startTimer();
	}

	/**
	 * This function should be called when the "GPS Navigator" page is closed
	 *
	 * @public
	 * @function onPageClosed
	 * @memberOf GPSNavigationController
	 * @instance
	 */
	this.onPageClosed = function(){

		stopTimer();
		gpsRadar.stop();

		// Remove all event handler
		$(htmlElement["coordinate_panel"]).off();
		$(htmlElement["popup"]).off();
		$(htmlElement["popup_button_save"]).unbind();
		$(htmlElement["popup_button_close"]).unbind();
		/*$(htmlElement["flipswitch_pref_rotate"]).unbind();
		$(htmlElement["flipswitch_pref_debuginfo"]).unbind();
		$(htmlElement["flipswitch_offline_mode"]).unbind();*/
		$(htmlElement["button_add_coordinate"]).unbind();
	}

	function startTimer(){
		if(updateTimer == null){
			updateTimer = setInterval(function(){
				gpsRadar.update(localCoordStore.getCurrentNavigation(), {}, {});
			}, 2000);
		}
	}

	function stopTimer(){
		if(updateTimer != null){
			clearInterval(updateTimer);
			updateTimer = null;
		}
	}

	/*
	 * ============================================================================================
	 * Private methods
	 * ============================================================================================
	 */

	/**
	 * Downloads the latest navigation list from the server
	 *
	 * @private
	 * @function
	 * @memberOf GPSNavigationController
	 * @instance
	 */
	function downloadNavListFromServer(){
		if(GeoCat.loginStatus.isSignedIn){
			uplink.sendNavList_Get(
						function(response){
							var result = JSON.parse(response);
							for(var i = 0; i < result.length; i++){
								result[i].is_public = false;
								localCoordStore.addCoordinateToNavigation(result[i]);
							}
						},
						uplinkOnError);
		}
	}

	/**
	 * Appends a coordinate to the current naviagtion list.<br />
	 * This method will send a request to the server too.
	 * @param coord {Coordinate} The coordinate
	 * @param coordinateAlreadyExistsOnServer {Boolean} Specify if the coordinate is already stored on the server
	 *
	 * @private
	 * @function
	 * @memberOf GPSNavigationController
	 * @instance
	 */
	function addCoordToNavList(coord, coordinateAlreadyExistsOnServer){

		if(coordinateAlreadyExistsOnServer){
			uplink.sendNavList_Add(coord.coord_id,
					function(result){
						localCoordStore.addCoordinateToNavigation(coord);
					},
					uplinkOnError);
		}
		else{
			uplink.sendNavList_Create(coord.name, coord.desc, coord.lat, coord.lon,
					function(result){
						coord.coord_id = result.coord_id; //Change the coord_id to the id that has been returned from the server
						localCoordStore.addCoordinateToNavigation(coord);
					},
					uplinkOnError);
		}
	}

	function uplinkOnError(response){
		alert(Tools.sprintf("Unable to perform this operation. (Status {0})\n" +
				"Server returned: {1}", [response["status"], response["msg"]]));
	}

	/*
	/**
	 * Reads a preference from the {@link GPSNavigator}
	 * @param key {String} Identifier for this preference
	 * @returns {String} Value "On" or "Off"
	 *
	 * @private
	 * @function
	 * @memberOf GPSNavigationController
	 * @instance
	 */
	/*function getPreference(key){
		var val = pages["gpsnavigator"].getPreference(key);
		if(val != undefined){
			return val == true ? "on" : "off";
		}
		return false;
	}*/

	/*function bindPreferenceChangeEvent(id, preferenceKey){
		$(id).bind( "change", function(event, ui) {
			pages["gpsnavigator"].setPreference(preferenceKey, $(id).is(":checked"));
		});
	}*/

	function resetActiveButtonState(buttonId){
		$(buttonId).removeClass($.mobile.activeBtnClass);
	}

	/**
	 * Shows the edit dialog
	 * @param id {integer} Identifier of the coordinate
	 * @param name {String} Name of the coordinate
	 * @param description {String} Description of the coordinate
	 * @param latitude {Double} Latitude of the coordinate
	 * @param longitude {Double} Longitude of the coordinate
	 * @param showAdd2OwnPlaces {Boolean} Show option "Add to own places"?
	 *
	 * @private
	 * @function
	 * @memberOf GPSNavigationController
	 * @instance
	 */
	function showCoordinateEditDialog(id, name, description, latitude, longitude, showAdd2OwnPlaces){

		// Fill the input fields with the values
		$(htmlElement["field_name"]).val(name);
		$(htmlElement["field_desc"]).val(description);
		$(htmlElement["field_lat"]).val(latitude);
		$(htmlElement["field_lon"]).val(longitude);

		if(id == null){
			$(htmlElement["popup"]).removeAttr("dest-id");
		}
		else{
			$(htmlElement["popup"]).attr("dest-id", id);
		}

		$(htmlElement["popup_checkbox_ownplaces"]).parent().css("display", showAdd2OwnPlaces ? "block" : "none" );

		$(htmlElement["popup"]).popup("open", {positionTo: "window", transition: "pop"});
	}

	/**
	 * Updates the current destination list based on tha values stored in the {@link LocalCoordinateStore}
	 *
	 * @private
	 * @function
	 * @memberOf GPSNavigationController
	 * @instance
	 */
	function updateCurrentDestinationList(){
		var destList = $(htmlElement["coordinate_list"]);
		destList.empty()

		var list = localCoordStore.getCurrentNavigation();
		for(var key in list){
			destList.append("<li dest-id=\"" + key + "\">" +
							"<a>" + list[key].name + "</a>" +
							"<a class=\"ui-icon-delete\">Remove</a>" +
							"</li>");
		}

		$(htmlElement["coordinate_list"] + " li a:first-child").click(function(){coordinateListItem_OnClick(this);});
		$(htmlElement["coordinate_list"] + " li a.ui-icon-delete").click(function(){deleteListItem_OnClick(this);});

		destList.listview('refresh');
	}

	/*
	 * ============================================================================================
	 * "OnClick" functions
	 * ============================================================================================
	 */

	function coordinateListItem_OnClick(element){
		var dest = localCoordStore.get($(element).parent().attr("dest-id"));
		showCoordinateEditDialog(dest.coord_id, dest.name, dest.desc, dest.lat, dest.lon, false);
	}

	function deleteListItem_OnClick(element){
		var key = $(element).parent().attr("dest-id");
		if(GeoCat.loginStatus.isSignedIn){
			uplink.sendNavList_Remove(key,
						function(response){
							localCoordStore.removeFromNavigationById(key);
							updateCurrentDestinationList();
						},
						uplinkOnError);
		}
		else{
			localCoordStore.removeFromNavigationById(key);
			updateCurrentDestinationList();
		}
	}

	function editDialog_SaveButton_OnClick(){

		// The id of the coordinate is stored as attribute in the HTML element
		var id = $(htmlElement["popup"]).attr("dest-id");

		var name = $(htmlElement["field_name"]).val();
		var desc = $(htmlElement["field_desc"]).val();

		// Verify the values of "name" and "description"
		var msg = "%s enthält ungültige Zeichen. Bitte verwenden Sie nur 'A-Z', '0-9' sowie einige Sonderzeichen ('!,;.#_-*')."
		if(!localCoordStore.verifyString(name)){
			alert(msg.replace("%s", "Der Name"));
			return;
		}

		if(desc != ""){
			if(!localCoordStore.verifyDescriptionString(desc)){
				alert(msg.replace("%s", "Die Beschreibung"));
				return;
			}
		}

		var lat = parseFloat($(htmlElement["field_lat"]).val());
		var lon = parseFloat($(htmlElement["field_lon"]).val());

		// Verify the values of "latitude" and "longitude"
		if(name == "" || isNaN(lat) || isNaN(lon)){
			alert("Please enter a valid name and values for latitude and longitude.");
		}
		else if(GeoCat.loginStatus.isSignedIn){
			// Everything ok

			if(id == undefined){
				// It is a new place
				var add2OwnPlaces = $(htmlElement["popup_checkbox_ownplaces"]).is(":checked");

				if(add2OwnPlaces){
					// This place will be added to your own places
					uplink.sendNewCoordinate(name, desc, lat, lon, false,
							function(result){
								var coord = new Coordinate(result["coord_id"], name, lat, lon, desc, false)
								addCoordToNavList(coord, true);
							},
							uplinkOnError);
				}
				else{
					// This place has to be added to the navigation list only
					addCoordToNavList(new Coordinate.create(name, lat, lon, desc), false);
				}
			}
			else{
				if(id > 0){
					var coord = localCoordStore.get(id);
					coord.name = name;
					coord.desc = desc;
					coord.lat = lat;
					coord.lon = lon;

					uplink.sendCoordinateUpdate(coord,
							function(result){
								localCoordStore.storePlace(coord);

								// Update label text
								$(htmlElement["coordinate_list"] + " li[dest-id=" + id + "] a[href='#']").text($(htmlElement["field_name"]).val());
							},
							uplinkOnError);
				}
				else{
					// Only store this coordinate local
					localCoordStore.storePlace(coord);
				}
			}

			// Close popup
			$(htmlElement["popup"]).popup("close");
		}
		else{
			// The user is not signed in - just add the coordinate to the navigation
			localCoordStore.addCoordinateToNavigation(new Coordinate.create(name, lat, lon, desc));
		}
	}
}

GPSNavigationController.currentInstance = null;

GPSNavigationController.init = function(){
	$(document).on("pageshow", "#GPSNavigator", function(){
		GPSNavigationController.currentInstance = new GPSNavigationController();
		GPSNavigationController.currentInstance.onPageOpened();
	});

	$(document).on("pagebeforehide", "#GPSNavigator", function(){
		GPSNavigationController.currentInstance.onPageClosed();
		GPSNavigationController.currentInstance = null
	});
};
