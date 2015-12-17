/*	GeoCat - Geolocation caching and tracking platform
	Copyright (C) 2015 Bastian Kraemer

	GPSRadar.js

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
 * This class displays the coordinates by using a HTML5 canvas
 * @class GPSRadar
 * @param canvas_container {HTMLElement} The target HTML5 canvas
 * @param gpsNavigator {GPSNavigator} Reference to the {@link GPSNavigator}
 * @param localCoordinateStore {LocalCoordinateStore} Reference to a {@link LocalCoordinateStore} object
 */
function GPSRadar(canvas_container, gpsNavigator, localCoordinateStore){

	//TODO: Append a "buffer zone" between canvas and its border to prevent cut text

	var maxDisplayedDistance = 1000; //distance between window border and center (in meters)
	var container = canvas_container;
	var canvas = $("#NavigatorCanvas")[0];
	var canvasFrame = $("#CanvasFrame")[0];
	var preferedCanvasSize;
	var canvasAxisLength = 500;
	var gpsnav = gpsNavigator;
	var localCoordStore = localCoordinateStore;

	/**
	 * Starts the {@link GPSRadar}
	 *
	 * @public
	 * @function start
	 * @memberOf GPSRadar
	 * @instance
	 */
	this.start = function(){
		updateCanvasSize();
		prepareCanvas();
		window.addEventListener('resize', updateCanvasSize, true);
	}

	/**
	 * Stops the {@link GPSRadar}
	 *
	 * @public
	 * @function stop
	 * @memberOf GPSRadar
	 * @instance
	 */
	this.stop = function(){
		var ctx = canvas.getContext("2d");
		clearCanvas(ctx);
		ctx.restore();
		ctx.setTransform(1, 0, 0, 1, 0, 0);
		window.removeEventListener('resize', updateCanvasSize, false);
	}

	/**
	 * Updates the HTML5 canvas with the latest information.<br />
	 * This function has to be called cyclic.
	 *
	 * @public
	 * @function update
	 * @memberOf GPSRadar
	 * @instance
	 */
	this.update = function(){
		var lastGPSPosition = gpsnav.getGPSPos();
		if(lastGPSPosition == null){return;}

		var lat = lastGPSPosition.coords.latitude;
		var lon = lastGPSPosition.coords.longitude;
		var heading = gpsnav.getHeading();
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

		ctx.fillStyle = 'black';
		drawGrid(ctx, heading);
		ctx.font = "14px Arial";
		ctx.fillStyle = 'red';
		var coords = localCoordStore.getCurrentNavigation();

		for(var key in coords) {;
			var distanceInMeter = GeoTools.calculateDistance(lon, lat, coords[key].lon, coords[key].lat) * 1000;

			var angle = GeoTools.calculateAngleTo(lon, lat, coords[key].lon, coords[key].lat);

			// Apply heading to coordinates
			angle = (angle - heading) % 360;

			//Calulate position relative to your own position
			var distInPx = convertDistance2Points(distanceInMeter, maxDisplayedDistance, canvasAxisLength)
			var relativePosition = calculateRelativePoint(angle, distInPx);

			drawPoint(ctx, relativePosition[0], relativePosition[1], 10);
			ctx.fillText(coords[key].name, relativePosition[0] - 16, relativePosition[1] - 12);
			ctx.fillText(distanceInMeter.toFixed(0) + "m", relativePosition[0], relativePosition[1] + 24);
		}

		if(gpsnav.getPreferences().debug_info){
			ctx.fillStyle = 'green';
			ctx.fillText(heading + "°", canvasAxisLength * -1, -1 * canvasAxisLength + 10);
			ctx.fillText(lat.toFixed(6), canvasAxisLength * -1, -1 * canvasAxisLength + 30);
			ctx.fillText(lon.toFixed(6), canvasAxisLength * -1, -1 * canvasAxisLength + 50);
			ctx.fillText(timestamp, canvasAxisLength * -1, -1 * canvasAxisLength + 70);
			ctx.fillText(accuracy + "m", canvasAxisLength * -1, -1 * canvasAxisLength + 90);
			if(speed != null && !isNaN(speed)){
				ctx.fillText(speed.toFixed(1) + "m/s", canvasAxisLength * -1, -1 * canvasAxisLength + 110);
			}
		}
	}

	function prepareCanvas(){
		var ctx = canvas.getContext("2d");
		ctx.font = "16px Arial";
		ctx.save();
		canvas.width = 2 * canvasAxisLength;
		canvas.height = 2 * canvasAxisLength;
		ctx.translate(canvasAxisLength, canvasAxisLength);
		drawGrid(ctx, 0);
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

	function clearCanvas(ctx){
		ctx.clearRect(-1 * canvasAxisLength, -1 * canvasAxisLength, canvas.width, canvas.height);
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

		/*ctx.moveTo(-1 * canvasAxisLength,0);
		ctx.lineTo(canvasAxisLength, 0);
		ctx.stroke();
		ctx.moveTo(0, -1 * canvasAxisLength);
		ctx.lineTo(0, canvasAxisLength);
		ctx.stroke();*/
	}

	function drawCompass(ctx, txt, heading){
		ctx.fillStyle = 'blue';
		if(heading < 0){heading = 360 + heading;} //note: heading is negative at the moment
		var point = calculateRelativePoint(heading, canvasAxisLength - 16);
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
		ctx.fillText(label, -4 * label.length, canvasAxisLength * radiusInPercent + offset);
	}

	function drawCircle(ctx, r){
		ctx.beginPath();
		ctx.arc(0, 0, r, 0, 2*Math.PI);
		ctx.stroke();
	}

	function calculateRelativePoint(angle, dist){
		var ret = new Array(2);

		var alpharad = angle * (Math.PI / 180);
		ret[0] = Math.sin(alpharad) * dist;
		ret[1] = Math.cos(alpharad) * dist * -1; // * -1 to invert y-axis
		return ret;
	}
}
