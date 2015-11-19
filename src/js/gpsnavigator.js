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

function Coordinate(identifier, latitude, longitude, name, description){
	this.id = identifier;
	this.lat = latitude;
	this.lon = longitude;
	this.name = name;
	this.description = description;
}

/*
 * TODO:
 * - Load/Store destination list in session/database via ajax
 * - Improve error handling
 * - Append a "buffer zone" between canvas and its border to prevent cut text
 * - List all destinations
 * - Add and remove items from destination list
 */

function GPSNavigator(navigator_htmlelement){

	var active = true;
	var coords = new Array(); /* Array of "Coordinates" */
	var maxDisplayedDistance = 1000; //distance between window border and center (in meters)
	var container = navigator_htmlelement;
	var canvas = $("#NavigatorCanvas")[0];
	var canvasFrame = $("#CanvasFrame")[0];
	var preferedCanvasSize;
	var canvasAxisLength = 500;
	var currentHeading = 0;
	var gpsWatchId = -1;
	var lastGPSPosition = null;
	var updateTimer = null;

	window.addEventListener('resize', updateCanvasSize, true);
	updateCanvasSize();
	drawGridInitial();

	/* Append some coordinates */
	//addDestination(new Coordinate("#1", <latitude>, <longitude> , "<Name>", "<Description>"));

	start();

	function addDestination(dest){
		coords.push(dest);
	}

	this.addDestination = addDestination;

	this.getDestinationList = function(){
		return coords;
	};

	function start(){
		if(gpsWatchId == -1){
			watchGPSPosition();
		}
	}

	this.destroy = function(){
		clearCanvas(canvas.getContext("2d"));
		window.removeEventListener('resize', updateCanvasSize, false);
		stop();
	};

	function stop(){
		if(gpsWatchId != -1){
			navigator.geolocation.clearWatch(gpsWatchId);
			gpsWatchId = -1;
		}

		if(updateTimer != null){
			clearInterval(updateTimer);
			updateTimer = null;
		}
	}

	function updateCanvasSize(){
		var h = getPageHeight();
		var w = container.offsetWidth;
		var offset = 40;

		if(w > h){
			canvasFrame.style.width = (h - offset) + "px";
			canvasFrame.style.height = (h - offset) + "px";
			preferedCanvasSize = 1.25 * (h - offset);
		}
		else{
			canvasFrame.style.width = (w - offset) + "px";
			canvasFrame.style.height = (w - offset) + "px";
			preferedCanvasSize = 1.25 * (w - offset);
		}
	}

	function displayGrid(){
		var frameWidth = container.offsetWidth;
		var frameHeight = container.offsetHeight;
	}

	function clearCanvas(ctx){
		ctx.clearRect(-1 * canvasAxisLength, -1 * canvasAxisLength, canvas.width, canvas.height);
	}

	function update(){

		if(lastGPSPosition == null){return;}

		var lat = lastGPSPosition.coords.latitude;
		var lon = lastGPSPosition.coords.longitude;
		var heading = lastGPSPosition.coords.heading;
		var accuracy = lastGPSPosition.coords.accuracy;
		var speed =  lastGPSPosition.coords.speed;
		var timestamp = lastGPSPosition.timestamp;

		var ctx = canvas.getContext("2d");
		clearCanvas(ctx);

		if(preferedCanvasSize != canvas.width){
			ctx.restore();
			canvas.width = preferedCanvasSize;
			canvas.height = preferedCanvasSize;
			canvasAxisLength = preferedCanvasSize / 2;
			ctx.translate(canvasAxisLength, canvasAxisLength);
		}

		if(!isNaN(heading) && heading != 0){
			currentHeading = heading.toFixed(0);
		}

		ctx.fillStyle = 'black';
		drawGrid(ctx, currentHeading);
		ctx.font = "14px Arial";
		ctx.fillStyle = 'red';

		for(var i = 0; i < coords.length; i++){
			var distanceInMeter = GeoTools.calculateDistance(lon, lat, coords[i].lon, coords[i].lat) * 1000;

			var angle = GeoTools.calculateAngleTo(lon, lat, coords[i].lon, coords[i].lat);

			// Apply heading to coordinates
			angle = (angle - currentHeading) % 360;

			//Calulate position relative to your own position
			var distInPx = convertDistance2Points(distanceInMeter, maxDisplayedDistance, canvasAxisLength)
			var relativePosition = calculateRelativePoint(angle, distInPx);

			drawPoint(ctx, relativePosition[0], relativePosition[1], 10);
			ctx.fillText(coords[i].name, relativePosition[0] - 16, relativePosition[1] - 12);
			ctx.fillText(distanceInMeter.toFixed(0) + "m", relativePosition[0], relativePosition[1] + 24);
		}

		ctx.fillStyle = 'green';
		ctx.fillText(currentHeading + "°", canvasAxisLength * -1, -1 * canvasAxisLength + 10);
		ctx.fillText(lat.toFixed(6), canvasAxisLength * -1, -1 * canvasAxisLength + 30);
		ctx.fillText(lon.toFixed(6), canvasAxisLength * -1, -1 * canvasAxisLength + 50);
		ctx.fillText(timestamp, canvasAxisLength * -1, -1 * canvasAxisLength + 70);
		ctx.fillText(accuracy + "m", canvasAxisLength * -1, -1 * canvasAxisLength + 90);
		if(!isNaN(speed)){
			ctx.fillText(toFixed(1) + "m/s", canvasAxisLength * -1, -1 * canvasAxisLength + 110);
		}
	}

	function drawGridInitial(){
		var ctx = canvas.getContext("2d");
		ctx.font = "16px Arial";
		ctx.save();
		ctx.translate(canvasAxisLength, canvasAxisLength);
		drawGrid(ctx, 0);
	}

	function drawGrid(ctx, heading){
		ctx.font = "16px Arial";
		 //Radius for "1% of max distance" by default 10m
		drawCircleWithLabel(ctx, 0.215, -8, maxDisplayedDistance * 0.01 + "m");
		 //Radius for "10% of max distance" by default 100m
		drawCircleWithLabel(ctx, 0.463, -8, maxDisplayedDistance * 0.1 + "m");
		 //Radius for "40% of max distance" by default 400m
		drawCircleWithLabel(ctx, 0.734, -8, maxDisplayedDistance * 0.4 + "m");
		 //Radius for "100% of max distance" by default 1000m
		drawCircleWithLabel(ctx, 1, -8, maxDisplayedDistance + "m");

		ctx.font = "22px Arial";
		heading = 360 - heading;
		drawCompass(ctx, "N", heading);
		drawCompass(ctx, "W", heading - 90);
		drawCompass(ctx, "S", heading - 180);
		drawCompass(ctx, "E", heading - 270);

		ctx.moveTo(-1 * canvasAxisLength,0);
		ctx.lineTo(canvasAxisLength, 0);
		ctx.stroke();
		ctx.moveTo(0, -1 * canvasAxisLength);
		ctx.lineTo(0, canvasAxisLength);
		ctx.stroke();
	}

	function drawCompass(ctx, txt, heading){
		ctx.fillStyle = 'blue';
		if(heading < 0){heading = 360 + heading;} //note: heading is negative at the moment
		var point = calculateRelativePoint(heading, canvasAxisLength - 16);
		//alert(txt + " -> " + (heading % 360) +  " (" + point[0] + "/" + point[1] * -1 + ")");
		//console.log(txt + " -> " + (heading % 360) + " (" + point[0] + "/" + point[1] * -1 + ")");
		ctx.fillText(txt, point[0] - 8, point[1] + 8);
	}

	function drawPoint(ctx, x, y, r, color){
		ctx.beginPath();
		ctx.arc(x, y, r, 0, 2*Math.PI);
		ctx.fill();
	}

	function convertDistance2Points(currentDist, maxDist, axisLength){
		if(currentDist >= maxDist){return axisLength;}
		// y = 0.215x^0.333
		return 0.215 * Math.pow((currentDist/maxDist) * 100, 0.333) * axisLength;
	}

	function drawCircleWithLabel(ctx, radiusInPercent, offset, label){
		drawCircle(ctx, canvasAxisLength * radiusInPercent);
		ctx.fillText(label, 2, canvasAxisLength * radiusInPercent + offset);
	}

	function drawCircle(ctx, r){
		ctx.beginPath();
		ctx.arc(0, 0, r, 0, 2*Math.PI);
		ctx.stroke();
	}

	function watchGPSPosition(){
		if(gpsWatchId == -1 && updateTimer == null){
			if (navigator.geolocation) {
				//navigator.geolocation.getCurrentPosition(update);

				// enableHighAccuracy: Use GPS
				// maximumAge: Only use a position if it is not older than 2 seconds
				gpsWatchId = navigator.geolocation.watchPosition(newGPSPositionReceived, gpsErrorHandler, {enableHighAccuracy: true, maximumAge: 5000});
				updateTimer = setInterval(update, 2000);
			} else {
				alert("Geolocation is not supported by this browser.");
			}
		}
		else{
			alert("GPSNavigator has been already started!");
		}
	}

	function newGPSPositionReceived(gpspos){
		lastGPSPosition = gpspos;
	}

	function gpsErrorHandler(err) {
		if(err.code == 1) {
			alert("Error: Access is denied!");
		}

		else if( err.code == 2) {
			alert("Error: Position is unavailable!");
		}
	 }

	function calculateRelativePoint(angle, dist){
		var ret = new Array(2);

		var alpharad = angle * (Math.PI / 180);
		ret[0] = Math.sin(alpharad) * dist;
		ret[1] = Math.cos(alpharad) * dist * -1; // * -1 to invert y-axis
		return ret;
	}
}
