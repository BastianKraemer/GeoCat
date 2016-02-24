var GPS = new function(){
	var currentHeading = 0;
	var currentPosition = null;
	var gpsWatchId = -1;

	/**
	 * Returns the latest GPS position. The value is returned by a callback.
	 * @param callback {Function} A callback with the current gps position as parameter
	 * @returns {Object} The object that is provided by the <code>navigator.geolocation.watchPosition</code> callback
	 *
	 * @public
	 * @function get
	 * @memberOf GPS
	 * @instance
	 */
	this.getOnce = function(callback){
		if(currentPosition != null){
			callback(currentPosition);
		}
		else{
			navigator.geolocation.getCurrentPosition(callback, gpsErrorHandler, {enableHighAccuracy: true});
		}
	};

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
				gpsWatchId = navigator.geolocation.watchPosition(newGPSPositionReceived, gpsErrorHandler, {enableHighAccuracy: true});
				return 1;
			} else {
				this.available = false;
				return 0;
			}
		}
		else{
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
		if(gpsWatchId != -1){
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

	var gpsErrorHandler = function(err) {
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
	};
};
