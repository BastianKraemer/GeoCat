function GPSTracker(callback, captureInterval, callbackInterval){

	var callbackTimer;
	var captureTimer;
	var track;

	this.start = function(){
		GPS.start();

		track = new Array();
		captureTimer = setInterval(capturePosition, captureInterval);
		if(callbackInterval != null){
			callbackTimer = setInterval(report, callbackInterval);
		}
	}

	this.stop = function(){
		GPS.stop();
		clearInterval(callbackTimer);
		clearInterval(captureTimer);
	}

	var report = function(){
		callback(track);
		track.length = 0;
	}

	var capturePosition = function(){

		if(callbackInterval == null){
			//report directly
			callback([GPS.get()]);
		}
		else{
			track.push(GPS.get());
		}
	}
}
