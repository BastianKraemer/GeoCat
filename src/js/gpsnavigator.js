/*	GeoCat - Geocaching and -Tracking platform
	Copyright (C) 2015 Bastian Kraemer

	gpsnavigator.js

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

function Coordinate(name, latitude, longitude, description){
	this.lat = latitude;
	this.lon = longitude;
	this.name = name;
	this.description = description;
}

 // TODO: Load/Store destination list in session/database via ajax
 // TODO: Improve error handling

function GPSNavigator(canvas_container){

	var container = canvas_container;
	var coords = new Object(); /* Map of "Coordinates" */
	var currentHeading = 0;
	var gpsWatchId = -1;
	var lastGPSPosition = null;
	var updateTimer = null;
	var preferences = {rotate: true, debug_info: true, offline_mode: false};
	var gpsDisplay = null;
	/* Append some coordinates */
	//addDestination(<id>, new Coordinate("<Name>", <latitude>, <longitude>, "<Description>"));

	function addDestination(id, dest){
		coords[id] = dest;
	}

	this.addDestination = addDestination;

	this.removeDestination = function(id){
		if(coords.hasOwnProperty(id)){
			delete coords[id];
		}
	};

	this.getDestinationById = function(id){
		return coords[id];
	};

	this.getDestinationList = function(){
		return coords;
	};

	this.getLastGPSPosition = function(){
		return lastGPSPosition;
	};

	this.setPreference = function(key, value){
		preferences[key] = value;
	};

	this.getPreferences = function(){
		return preferences;
	};

	this.getPreference = function(key){
		return preferences[key];
	};

	this.getGPSPos = function(){
		return lastGPSPosition;
	};

	this.getHeading = function(){
		return currentHeading;
	}

	this.startNavigator = function(){
		if(gpsDisplay != null){
			stopTimer();
			gpsDisplay.stop();
		}
		gpsDisplay = new GPSRadar(container, this);
		gpsDisplay.start();
		watchGPSPosition();
	}

	this.stopNavigator = function(){
		if(gpsWatchId != -1){
			navigator.geolocation.clearWatch(gpsWatchId);
			gpsWatchId = -1;
		}

		stopTimer();
		if(gpsDisplay != null){gpsDisplay.stop(); gpsDisplay = null;}
	}

	function startTimer(){
		if(updateTimer == null){
			updateTimer = setInterval(gpsDisplay.update, 2000);
		}
	}

	function stopTimer(){
		if(updateTimer != null){
			clearInterval(updateTimer);
			updateTimer = null;
		}
	}

	function watchGPSPosition(){
		if(gpsWatchId == -1){
			if (navigator.geolocation) {
				//navigator.geolocation.getCurrentPosition(update);

				// enableHighAccuracy: Use GPS
				// maximumAge: Only use a position if it is not older than 2 seconds
				gpsWatchId = navigator.geolocation.watchPosition(newGPSPositionReceived, gpsErrorHandler, {enableHighAccuracy: true, maximumAge: 5000});
			} else {
				alert("Geolocation is not supported by this browser.");
				return;
			}
		}
		startTimer();
	}

	function newGPSPositionReceived(gpspos){
		lastGPSPosition = gpspos;

		if(preferences.rotate){
			if(!isNaN(gpspos.coords.heading) && gpspos.coords.heading != 0){
				currentHeading = gpspos.coords.heading.toFixed(0);
			}
		}
		else{
			currentHeading = 0;
		}
	}

	function gpsErrorHandler(err) {
		if(err.code == 1) {
			alert("Error: Access is denied!");
		}

		else if( err.code == 2) {
			alert("Error: Position is unavailable!");
		}
	 }
}
