/*	GeoCat - Geocaching and -Tracking platform
	Copyright (C) 2015 Bastian Kraemer

	GPSNavigator.js

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


 // TODO: Load/Store destination list in session/database via ajax
 // TODO: Improve error handling

/**
 * This class is used by the GPS Radar to handle "destination list"
 * @class GPSNavigator
 * @param {HTMLElement} Container of the canvas which is used to display the navigator
 * @see GPSRadar
 */
function GPSNavigator(canvas_container){

	var container = canvas_container;
	var currentHeading = 0;
	var gpsWatchId = -1;
	var lastGPSPosition = null;
	var updateTimer = null;
	var preferences = {rotate: true, debug_info: true, offline_mode: false};
	var gpsDisplay = null;

	/**
	 * Sets a preference of the navigator
	 * @returns {Object.<String, Object>} Map of the current preferences
	 *
	 * @function setPreferences
	 * @memberOf GPSNavigator
	 * @instance
	 */
	this.setPreference = function(key, value){
		preferences[key] = value;
	};

	/**
	 * Returns all preferences of the navigator
	 * @returns {Object.<String, Object>} Map of the current preferences
	 *
	 * @function getPreferences
	 * @memberOf GPSNavigator
	 * @instance
	 */
	this.getPreferences = function(){
		return preferences;
	};

	/**
	 * Gets the value of a specific preference of the navigator
	 * @param {String} key The key that identifies the preference
	 * @returns {Object} The value of this preference
	 *
	 * @function getPreference
	 * @memberOf GPSNavigator
	 * @instance
	 */
	this.getPreference = function(key){
		return preferences[key];
	};

	/**
	 * Returns the latest GPS position that is availabe to this class
	 * @returns {Object} The object that is provided by the <code>navigator.geolocation.watchPosition</code> callback
	 *
	 * @function getGPSPos
	 * @memberOf GPSNavigator
	 * @instance
	 */
	this.getGPSPos = function(){
		return lastGPSPosition;
	};

	/**
	 * Returns the current heading that has been calculated by the movement of the device.
	 * The value is based on the HTML5 geolocation heading value, but will keep the last angle if the device stops moving.
	 * The HTML5 geolocation API ist described here: http://www.w3.org/TR/geolocation-API/#heading
	 * The value is directly represets the number of
	 * @returns {Number} The heading in degrees counted clockwise from north (0 <= heading < 360).
	 *
	 * @function getHeading
	 * @memberOf GPSNavigator
	 * @instance
	 */
	this.getHeading = function(){
		return currentHeading;
	}

	/**
	 * Starts the navigator. This should be called when the GPS Navigator page is opened.
	 *
	 * @function startNavigator
	 * @memberOf GPSNavigator
	 * @instance
	 */
	this.startNavigator = function(localCoordStore){
		if(gpsDisplay != null){
			stopTimer();
			gpsDisplay.stop();
		}
		gpsDisplay = new GPSRadar(container, this, localCoordStore);
		gpsDisplay.start();
		watchGPSPosition();
	}

	/**
	 * Stops the navigator. This should be called when the GPS Navigator page is closed.
	 *
	 * @function stopNavigator
	 * @memberOf GPSNavigator
	 * @instance
	 */
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
		if(gpsWatchId != -1){
			navigator.geolocation.clearWatch(gpsWatchId);
			gpsWatchId = -1;
		}

		if(err.code == 1) {
			alert("Error: Access to GPS denied!");
		}
		else if( err.code == 2) {
			alert("Error: Position is unavailable!");
		}
	}
}
