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

	this.onPageOpened = function(){
		pages["gpsnavigator"] = new GPSNavigator($("#gpsnavigator_content")[0]);

		// Append some event handler
		$("#CurrentDesitionListPanel").on("panelbeforeopen", function(){
			var destList = $("#CurrentDestinationList");
			destList.empty()

			var list = pages["gpsnavigator"].getDestinationList();
			for(var key in list){
				destList.append("<li dest-id='" + key + "'><a href='#'>" + list[key].name + "</a></li>");
			}

			destList.listview('refresh');
		});

		$("#CurrentDesitionListPanel").on("panelbeforeopen", function(){
			var destList = $("#CurrentDestinationList");
			destList.empty()

			var list = pages["gpsnavigator"].getDestinationList();
			for(var key in list){
				destList.append("<li dest-id='" + key + "'><a href='#'>" + list[key].name + "</a></li>");
			}

			destList.listview('refresh');
		});

		$("#CurrentDestinationList").delegate('li', 'click', function(){
			var destID = $(this).attr("dest-id");
			var dest = pages["gpsnavigator"].getDestinationById(destID);
			showCoordinateEditDialog(destID, dest.name, dest.description, dest.lat, dest.lon);
		});

		$("#GPSNavigator_AddCoordinate").click(function(e){
			if(pages["gpsnavigator"] == null){return;}

			var lastGPSPos = pages["gpsnavigator"].getLastGPSPosition();
			if(lastGPSPos != null){
				showCoordinateEditDialog(Date.now(), "Neues Ziel", "", lastGPSPos.coords.latitude, lastGPSPos.coords.longitude);
			}
			else{
				alert("Unable to get current GPS position.");
				resetActiveButtonState("#GPSNavigator_AddCoordinate");
			}
		});

		$("#GPSNavDestListPopup_Save").click(function(e) {
			var id = $("#GPSNavDestListPopup").attr("dest-id");
			pages["gpsnavigator"].addDestination(id, new Coordinate($("#GPSNavDestListPopup_Name").val(),
																	$("#GPSNavDestListPopup_Lat").val(),
																	$("#GPSNavDestListPopup_Lon").val(),
																	$("#GPSNavDestListPopup_Desc").val()));

			// Update label text
			$("#CurrentDestinationList li[dest-id=" + id + "] a").text($("#GPSNavDestListPopup_Name").val());

			// Close popup
			$("#GPSNavDestListPopup").popup("close");
		});

		$("#GPSNavDestListPopup").on("popupafterclose", function(event, ui){
			resetActiveButtonState("#GPSNavigator_AddCoordinate");
		});

		//$("#CurrentDesitionListPanel").on("panelbeforeclose", function(){});
	}

	function resetActiveButtonState(buttonId){
		$(buttonId).removeClass($.mobile.activeBtnClass);
	}

	this.onPageClosed = function(){
		if(pages["gpsnavigator"] != null){
			$("#CurrentDesitionListPanel").off() // Remove all event handler
			pages["gpsnavigator"].destroy();
			pages["gpsnavigator"] = null;

			pageHeightOffset = 80; //global variable
		}
	}

	function showCoordinateEditDialog(id, name, description, latitude, longitude){
		$("#GPSNavDestListPopup_Name").val(name);
		$("#GPSNavDestListPopup_Desc").val(description);
		$("#GPSNavDestListPopup_Lat").val(latitude);
		$("#GPSNavDestListPopup_Lon").val(longitude);
		$("#GPSNavDestListPopup").css("width", window.innerWidth - (window.innerWidth * 0.1) + "px");
		$("#GPSNavDestListPopup").attr("dest-id", id);

		$("#GPSNavDestListPopup").popup("open", {positionTo: "window", transition: "pop"});
	}
}
