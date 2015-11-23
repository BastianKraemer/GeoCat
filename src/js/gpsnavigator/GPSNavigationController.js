/*	GeoCat - Geolocation caching and tracking platform
	Copyright (C) 2015 Bastian Kraemer

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

var GPSNavigationController = new function(){

	// Define HTML-Element IDs
	var idList = new Object();
	idList["list"] = "#CurrentDestinationList";
	idList["panel"] = "#CurrentDesitionListPanel";
	idList["popup"] = "#GPSNavDestListPopup";
	idList["popup_save"] = "#GPSNavDestListPopup_Save";
	idList["add_coordinate"] = "#GPSNavigator_AddCoordinate";
	idList["field_name"] = "#GPSNavDestListPopup_Name";
	idList["field_lat"] = "#GPSNavDestListPopup_Lat";
	idList["field_lon"] = "#GPSNavDestListPopup_Lon";
	idList["field_desc"] = "#GPSNavDestListPopup_Desc";
	idList["preferences"] = "#GPSNavigatorPreferencesPanel";
	idList["pref_rotate"] = "#GPSNavDisableRotation";
	idList["pref_debuginfo"] = "#GPSNavShowDebugInfo";
	idList["pref_offline_mode"] = "#GPSNavOfflineMode";

	this.onPageOpened = function(){
		if(pages["gpsnavigator"] == null){
			pages["gpsnavigator"] = new GPSNavigator($("#gpsnavigator_content")[0]);
		}
		else{
			pages["gpsnavigator"].start();
		}

		// Append some event handler

		/*
		 * On panel opened
		 */
		$(idList["panel"]).on("panelbeforeopen", function(){
			updateCurrentDestinationList();
		});

		/*
		 * Add coordinate button
		 */
		$(idList["add_coordinate"]).click(function(e){
			if(pages["gpsnavigator"] == null){return;}

			var lastGPSPos = pages["gpsnavigator"].getLastGPSPosition();
			if(lastGPSPos != null){
				showCoordinateEditDialog(Date.now(), "", "", lastGPSPos.coords.latitude, lastGPSPos.coords.longitude);
			}
			else{
				alert("Unable to get current GPS position.");
				resetActiveButtonState(idList["add_coordinate"]);
			}
		});

		/*
		 * Save button in popup dialog is clicked
		 */
		$(idList["popup_save"]).click(function(e) {
			var id = $(idList["popup"]).attr("dest-id");

			var name = $(idList["field_name"]).val();
			var lat = parseFloat($(idList["field_lat"]).val());
			var lon = parseFloat($(idList["field_lon"]).val());

			if(name == "" || isNaN(lat) || isNaN(lon)){
				alert("Please enter a valid name and values for latitude and longitude.");
			}
			else{
				// Everything ok
				pages["gpsnavigator"].addDestination(id, new Coordinate(name, lat, lon, $(idList["field_desc"]).val()));

				// Update label text
				$(idList["list"] + " li[dest-id=" + id + "] a[href='#']").text($(idList["field_name"]).val());

				// Close popup
				$(idList["popup"]).popup("close");
			}
		});

		$(idList["popup"]).on("popupafterclose", function(event, ui){
			resetActiveButtonState(idList["add_coordinate"]);
		});

		/*
		 * Before Preferences panel is opened
		 */
		$(idList["preferences"]).on("panelafteropen", function(){
			$(idList["pref_rotate"]).val(getPreference("rotate")).slider("refresh");
			$(idList["pref_debuginfo"]).val(getPreference("debug_info")).slider("refresh");
			$(idList["pref_offline_mode"]).val(getPreference("offline_mode")).slider("refresh");
		});

		bindPreferenceChangeEvent(idList["pref_rotate"], "rotate");
		bindPreferenceChangeEvent(idList["pref_debuginfo"], "debug_info");
		bindPreferenceChangeEvent(idList["pref_offline_mode"], "offline_mode");

		//$(idList["panel"]).on("panelbeforeclose", function(){});
	}

	function getPreference(key){
		var val = pages["gpsnavigator"].getPreference("rotate");
		if(val != undefined){
			return val == true ? "on" : "off";
		}
		return false;
	}

	function bindPreferenceChangeEvent(id, preferenceKey){
		$(id).bind( "change", function(event, ui) {
			pages["gpsnavigator"].setPreference(preferenceKey, $(id).is(":checked"));
		});
	}

	function resetActiveButtonState(buttonId){
		$(buttonId).removeClass($.mobile.activeBtnClass);
	}

	this.onPageClosed = function(){
		if(pages["gpsnavigator"] != null){

			// Remove all event handler
			$(idList["panel"]).off();
			$(idList["popup"]).off();
			$(idList["pref_rotate"]).unbind();
			$(idList["pref_debuginfo"]).unbind();
			$(idList["pref_offline_mode"]).unbind();
			$(idList["popup_save"]).unbind();
			$(idList["add_coordinate"]).unbind();

			pages["gpsnavigator"].stop();

			pageHeightOffset = 80; //global variable
		}
	}

	this.showCoordinateEditDialogForExistingDestination = function(destID){
		if(pages["gpsnavigator"] != null){
			var dest = pages["gpsnavigator"].getDestinationById(destID);
			showCoordinateEditDialog(destID, dest.name, dest.description, dest.lat, dest.lon);
		}
	}

	function showCoordinateEditDialog(id, name, description, latitude, longitude){
		$(idList["field_name"]).val(name);
		$(idList["field_desc"]).val(description);
		$(idList["field_lat"]).val(latitude);
		$(idList["field_lon"]).val(longitude);
		$(idList["popup"]).css("width", window.innerWidth - (window.innerWidth * 0.1) + "px");
		$(idList["popup"]).attr("dest-id", id);

		$(idList["popup"]).popup("open", {positionTo: "window", transition: "pop"});
	}

	function updateCurrentDestinationList(){
		var destList = $(idList["list"]);
		destList.empty()

		var list = pages["gpsnavigator"].getDestinationList();
		for(var key in list){
			destList.append("<li dest-id=\"" + key + "\">" +
							"<a onclick=\"GPSNavigationController.showCoordinateEditDialogForExistingDestination('" + key + "');\" href=\"#\">" + list[key].name + "</a>" +
							"<a onclick=\"GPSNavigationController.deleteListItem('" + key + "');\" class=\"ui-icon-delete\">Remove</a>" +
							"</li>");
		}

		destList.listview('refresh');
	}

	this.deleteListItem = function(key){
		pages["gpsnavigator"].removeDestination(key);
		updateCurrentDestinationList();
	}
}
