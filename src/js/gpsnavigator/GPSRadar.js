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
 * @param jQueryMobileContentDivElement {HTMLElement} The jQuery Mobile content area
 * @param targetCanvas {HTMLElement} The target HTML5 canvas
 */
function GPSRadar(jQueryMobileContentDivElement, targetCanvas){

	//TODO: Append a "buffer zone" between canvas and its border to prevent cut text

	var maxDisplayedDistance = 1000; //distance between window border and center (in meters)
	var jQueryMobileContentDiv = jQueryMobileContentDivElement;
	var canvas = targetCanvas;
	var canvasFrame = targetCanvas.parentElement;
	var preferedCanvasSize;
	var canvasAxisLength = 500;
	var showDebugInfos = true;

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
		GPS.start();
	};

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
		GPS.stop();
	};

	/**
	 * Updates the HTML5 canvas with the latest information.<br />
	 * This function has to be called cyclic.
	 * @param coords {Object.<String, Coordinate>} Map of all coordinates
	 * @param colors {Object.<String, String>} Map for the coordinate colors (id -> HTML color)
	 * @param colors {Object.<String, GPSRadar.CoordinateIcon>} Map for the coordinate icons (id -> icon)
	 *
	 * @public
	 * @function update
	 * @memberOf GPSRadar
	 * @instance
	 */
	this.update = function(coords, colors, icons){
		var lastGPSPosition = GPS.get();
		if(lastGPSPosition == null){return;}

		var lat = lastGPSPosition.coords.latitude;
		var lon = lastGPSPosition.coords.longitude;
		var heading = GPS.getHeading();
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

		for(var key in coords) {
			var distanceInMeter = GeoTools.calculateDistance(lon, lat, coords[key].lon, coords[key].lat) * 1000;

			var angle = GeoTools.calculateAngleTo(lon, lat, coords[key].lon, coords[key].lat);

			// Apply heading to coordinates
			angle = (angle - heading) % 360;

			//Calulate position relative to your own position
			var distInPx = convertDistance2Points(distanceInMeter, maxDisplayedDistance, canvasAxisLength)
			var relativePosition = calculateRelativePoint(angle, distInPx);

			var icon = icons.hasOwnProperty(key) ? icons[key] : GPSRadar.CoordinateIcon.POINT;
			var color = colors.hasOwnProperty(key) ? colors[key] : "#000";
			if(icon == GPSRadar.CoordinateIcon.CROSS){
				drawCross(ctx, relativePosition[0], relativePosition[1], 8, color)
			}
			else if(icon == GPSRadar.CoordinateIcon.CIRCLE){
				drawCircle(ctx, relativePosition[0], relativePosition[1], 8, color)
			}
			else{
				drawPoint(ctx, relativePosition[0], relativePosition[1], 8, color);
			}

			drawCoordinateText(ctx, coords[key].name, distanceInMeter.toFixed(0), relativePosition[0], relativePosition[1], color);
		}

		if(showDebugInfos){
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
	};

	function drawCoordinateText(ctx, coordName, coordDist, x, y, color){
		ctx.fillStyle = color;
		ctx.fillText(coordName, x - 16, y - 12);
		ctx.fillText(coordDist + "m", x, y + 24);
	}

	this.debugMode = function(value){
		showDebugInfos = value;
	};

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
		var w = jQueryMobileContentDiv.offsetWidth;
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

		drawFilledCircle(ctx, 1, -8, "#ffffff");
		drawFilledCircle(ctx, 0.734, -8, "#f8f8f8");
		drawFilledCircle(ctx, 0.463, -8, "#f2f2f2");
		drawFilledCircle(ctx, 0.215, -8, "#ededed");

		 //Radius for "1% of max distance" by default 10m
		drawCircleWithLabel(ctx, 0.215, -8, maxDisplayedDistance * 0.01 + "m", "#999", false);
		 //Radius for "10% of max distance" by default 100m
		drawCircleWithLabel(ctx, 0.463, -8, maxDisplayedDistance * 0.1 + "m", "#999", false);
		 //Radius for "40% of max distance" by default 400m
		drawCircleWithLabel(ctx, 0.734, -8, maxDisplayedDistance * 0.4 + "m", "#999", false);
		 //Radius for "100% of max distance" by default 1000m
		drawCircleWithLabel(ctx, 1, -8, maxDisplayedDistance + "m", "#999", true);

		ctx.font = "22px Arial";
		heading = 360 - heading;
		drawCompass(ctx, "N", heading);
		drawCompass(ctx, "W", heading - 90);
		drawCompass(ctx, "S", heading - 180);
		drawCompass(ctx, "E", heading - 270);
	}

	function drawCircleWithLabel(ctx, radiusInPercent, offset, label, color, drawBorder){
		if(drawBorder){
			drawCircle(ctx, 0, 0, canvasAxisLength * radiusInPercent, color);
		}
		ctx.fillStyle = color;
		ctx.fillText(label, -4 * label.length, canvasAxisLength * radiusInPercent + offset);
	}

	function drawFilledCircle(ctx, radiusInPercent, offset, color){
		drawPoint(ctx, 0, 0, canvasAxisLength * radiusInPercent, color);
	}

	function drawCompass(ctx, txt, heading){
		ctx.fillStyle = 'blue';
		if(heading < 0){heading = 360 + heading;} //note: heading is negative at the moment
		var point = calculateRelativePoint(heading, canvasAxisLength - 16);
		ctx.fillText(txt, point[0] - 8, point[1] + 8);
	}

	function drawPoint(ctx, x, y, r, color){
		ctx.beginPath();
		ctx.fillStyle = color;
		ctx.arc(x, y, r, 0, 2*Math.PI);
		ctx.closePath();
		ctx.fill();
	}

	function drawCross(ctx, x, y, r, color){
		ctx.strokeStyle = color;
		ctx.moveTo(x - r, y -r);
		ctx.lineTo(x + r, y + r);
		ctx.stroke();
		ctx.moveTo(x + r, y - r);
		ctx.lineTo(x -r, y + r);
		ctx.stroke();
	}

	function drawCircle(ctx, x, y, r, color){
		ctx.beginPath();
		ctx.arc(x, y, r, 0, 2*Math.PI);
		ctx.strokeStyle = color;
		ctx.closePath();
		ctx.stroke();
	}

	function convertDistance2Points(currentDist, maxDist, axisLength){
		if(currentDist >= maxDist){return axisLength;}
		// y = 0.215x^0.333
		return 0.215 * Math.pow((currentDist/maxDist) * 100, 0.333) * axisLength;
	}

	function calculateRelativePoint(angle, dist){
		var ret = new Array(2);

		var alpharad = angle * (Math.PI / 180);
		ret[0] = Math.sin(alpharad) * dist;
		ret[1] = Math.cos(alpharad) * dist * -1; // * -1 to invert y-axis
		return ret;
	}
}

GPSRadar.CoordinateIcon = {
    POINT: 0,
    CIRCLE: 1,
    CROSS: 2
};
