var GPS = new function(){
	var currentHeading = 0;
	var currentPosition = null;
	var gpsWatchId = -1;
	var listenerCnt = 0;

	/**
	 * Returns the latest GPS position. The value is returned by a callback.
	 * @param callback {Function} A callback with the current gps position as parameter
	 * @param statusUpdateCallback {Function} A callback with the current progess status (in percent) as parameter
	 * @returns {Object} The object that is provided by the <code>navigator.geolocation.watchPosition</code> callback
	 *
	 * @public
	 * @function get
	 * @memberOf GPS
	 * @instance
	 */
	this.getOnce = function(callback, statusUpdateCallback){
		if(currentPosition != null){
			callback(currentPosition);
		}
		else{
			watchPositionOnce(callback, statusUpdateCallback);
		}
	};

	var watchPositionOnce = function(callback, statusUpdateCallback){
		var watchId = null;
		var counter = 0;
		var max = 7
		var report = function(current){
			if(statusUpdateCallback != null){
				statusUpdateCallback(((100 / max) * current).toFixed(0));
			}
		}

		report(0);
		watchId = navigator.geolocation.watchPosition(
				function(gpspos){
					counter++;
					report(counter);

					if(counter >= max){
						navigator.geolocation.clearWatch(watchId);
						callback(gpspos);
					}
				},
				function(){
					navigator.geolocation.clearWatch(watchId);
					GeoCat.displayError("Error: Unable to get GPS position");
				},
				{enableHighAccuracy: true});
	}

	/**
	 * Returns the latest GPS position that is availabe to this class
	 * @returns {Object} The object that is provided by the <code>navigator.geolocation.watchPosition</code> callback
	 *
	 * @public
	 * @function get
	 * @memberOf GPS
	 * @instance
	 */
	this.get = function(){
		return currentPosition;
	};

	/**
	 * Returns the current heading that has been calculated by the movement of the device.
	 * The value is based on the HTML5 geolocation heading value, but will keep the last angle if the device stops moving.
	 * The HTML5 geolocation API ist described here: http://www.w3.org/TR/geolocation-API/#heading
	 * @returns {Number} The heading is counted in degrees clockwise from north (0 <= heading < 360).
	 *
	 * @public
	 * @function getHeading
	 * @memberOf GPS
	 * @instance
	 */
	this.getHeading = function(){
		return currentHeading;
	};

	/**
	 * Starts watching the GPS location of the device
	 *
	 * @memberOf GPSNavigator
	 * @instance
	 */
	this.start = function(){
		if(gpsWatchId == -1){
			if (navigator.geolocation) {
				// enableHighAccuracy: Use GPS
				listenerCnt = 1;
				gpsWatchId = navigator.geolocation.watchPosition(newGPSPositionReceived, gpsErrorHandler, {enableHighAccuracy: true});
				return 1;
			} else {
				this.available = false;
				return 0;
			}
		}
		else{
			listenerCnt++;
			return 2;
		}
	};

	/**
	 * Stops watching the GPS location of the device
	 *
	 * @memberOf GPSNavigator
	 * @instance
	 */
	this.stop = function(){
		listenerCnt--;
		if(gpsWatchId != -1 && listenerCnt == 0){
			navigator.geolocation.clearWatch(gpsWatchId);
			gpsWatchId = -1;
			currentPosition = null;
		}
	};

	/**
	 * This method is called when the geolocation API has an updated location
	 *
	 * @private
	 * @memberOf GPSNavigator
	 * @instance
	 */
	var newGPSPositionReceived = function(gpspos){

		currentPosition = gpspos;
		if(gpspos.coords.heading != null && !isNaN(gpspos.coords.heading) && gpspos.coords.heading != 0){
				currentHeading = gpspos.coords.heading.toFixed(0);
		}
	};

	var gpsErrorHandler = function(error) {
		if(gpsWatchId != -1){
			navigator.geolocation.clearWatch(gpsWatchId);
			gpsWatchId = -1;
		}

		var errorMsg = "Unknown error.";

		switch(error.code){
			case error.PERMISSION_DENIED:
				errorMsg = "Permission denied.";
				break;
			case error.POSITION_UNAVAILABLE:
				errorMsg = "GPS is not availabe.";
				break;
			case error.TIMEOUT:
				errorMsg = "GPS timeout.";
				break;
			case error.UNKNOWN_ERROR:
				break;
	    }

		GeoCat.displayError("Unable to get GPS position: " + errorMsg);
	};
};
