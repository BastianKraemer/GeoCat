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


 // TODO: Improve error handling

/**
 * This class is used by the {@link GPSRadar} to watch for the gps location
 * @class GPSNavigator
 * @param {HTMLElement} Container of the canvas which is used to display the navigator
 * @see GPSRadar
 */
function GPSNavigator(canvas_container){

	var container = canvas_container;
	var updateTimer = null;
	var preferences = {rotate: true, debug_info: true, offline_mode: false};
	var gpsDisplay = null;
	var localCoordStore = null;
	/**
	 * Sets a preference of the navigator
	 *
	 * @public
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
	 * @public
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
	 * @public
	 * @function getPreference
	 * @memberOf GPSNavigator
	 * @instance
	 */
	this.getPreference = function(key){
		return preferences[key];
	};

	/**
	 * Starts the {@link GPSNavigator}. This should be called when the GPS Navigator page is opened.
	 *
	 * @public
	 * @function startNavigator
	 * @memberOf GPSNavigator
	 * @instance
	 */
	this.startNavigator = function(localCoordinateStore){
		if(gpsDisplay != null){
			stopTimer();
			gpsDisplay.stop();

		}

		localCoordStore = localCoordinateStore;
		gpsDisplay = new GPSRadar(container, $("#NavigatorCanvas")[0]);
		gpsDisplay.start();
		startTimer();
	}

	/**
	 * Stops the {@link GPSNavigator}. This should be called when the GPS Navigator page is closed.
	 *
	 * @public
	 * @function stopNavigator
	 * @memberOf GPSNavigator
	 * @instance
	 */
	this.stopNavigator = function(){
		stopTimer();
		if(gpsDisplay != null){gpsDisplay.stop(); gpsDisplay = null;}
	}

	function startTimer(){
		if(updateTimer == null){
			updateTimer = setInterval(updateGPSDisplay, 2000);
		}
	}

	function updateGPSDisplay(){
		gpsDisplay.update(localCoordStore.getCurrentNavigation());
	}

	function stopTimer(){
		if(updateTimer != null){
			clearInterval(updateTimer);
			updateTimer = null;
		}
	}
}
