/*	GeoCat - Geolocation caching and tracking platform
	Copyright (C) 2015-2016 Bastian Kraemer

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

	var maxDisplayedDistance = 1000; //distance between window border and center (in meters)
	var jQueryMobileContentDiv = jQueryMobileContentDivElement;
	var canvas = targetCanvas;
	var canvasFrame = targetCanvas.parentElement;
	var preferedCanvasSize;

	var canvasOffset = 16;
	var canvasAxisLength = 500;
	var canvasRadius = canvasAxisLength - canvasOffset;
	var showDebugInfos = false;

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
			canvasRadius = canvasAxisLength - canvasOffset;
			ctx.translate(canvasAxisLength, canvasAxisLength);
		}

		if(canvasRadius < 16){
			// The canvas is to small (this may occur when the device is rotated AND the user has zoomed into the page)
			return;
		}

		drawGrid(ctx, heading);
		drawStickman(ctx, 0, 0);

		for(var key in coords) {
			var distanceInMeter = GeoTools.calculateDistance(lon, lat, coords[key].lon, coords[key].lat) * 1000;

			var angle = GeoTools.calculateAngleTo(lon, lat, coords[key].lon, coords[key].lat);

			// Apply heading to coordinates
			angle = (angle - heading) % 360;

			//Calulate position relative to your own position
			var distInPx = convertDistance2Points(distanceInMeter, maxDisplayedDistance, canvasRadius)
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
		ctx.font = "14px Arial";
		ctx.fillStyle = color;

		ctx.fillText(coordName, x - (ctx.measureText(coordName).width / 2), y - 12);

		if(coordDist < maxDisplayedDistance){
			coordDist += "m";
			ctx.font = "12px Arial";
			ctx.fillText(coordDist, x - (ctx.measureText(coordDist).width / 2), y + 22);
		}
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
		var h = window.innerHeight || document.documentElement.clientHeight || document.body.clientHeight;
		var w = jQueryMobileContentDiv.offsetWidth - 32; //Substract size for padding and margin
		h -= 144; // Substract size for header and footer

		if(h < 0){h = 128;}
		if(w < 0){w = 128;}

		if(w > h){
			canvasFrame.style.width = h + "px";
			canvasFrame.style.height = h + "px";
			preferedCanvasSize = 1.25 * h;
		}
		else{
			canvasFrame.style.width = w + "px";
			canvasFrame.style.height = w + "px";
			preferedCanvasSize = 1.25 * w;
		}
	}

	function clearCanvas(ctx){
		ctx.clearRect(-1 * canvasAxisLength, -1 * canvasAxisLength, canvas.width, canvas.height);
	}

	function drawGrid(ctx, heading){
		ctx.font = "11pt Arial";

		for(var i = 0; i < GPSRadar.display.length; i++){
			var r = canvasRadius * GPSRadar.display[i].r;
			drawFilledCircle(ctx, r, GPSRadar.display[i].color);
			drawCircleWithLabel(ctx, r, -6, GPSRadar.display[i].label, "#999", i == 0);
		}

		ctx.font = "18pt Arial";
		ctx.fillStyle = "#30383f";
		heading = 360 - heading;
		drawCompass(ctx, "N", heading);
		drawCompass(ctx, "W", heading - 90);
		drawCompass(ctx, "S", heading - 180);
		drawCompass(ctx, "E", heading - 270);
	}

	function drawCircleWithLabel(ctx, radius, offset, label, color, drawBorder){
		if(drawBorder){
			drawCircle(ctx, 0, 0, radius, color);
		}
		ctx.fillStyle = color;
		ctx.fillText(label, 0 - (ctx.measureText(label).width / 2), radius + offset);
	}

	function drawFilledCircle(ctx, radius, color){
		drawPoint(ctx, 0, 0, radius, color);
	}

	function drawCompass(ctx, txt, heading){
		if(heading < 0){heading = 360 + heading;}
		var point = calculateRelativePoint(heading, canvasRadius);
		ctx.fillText(txt, point[0] - (ctx.measureText(txt).width / 2), point[1] + (parseInt(ctx.font) / 2));
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

	function drawStickman(ctx, x, y){
		ctx.beginPath();
		ctx.arc(x, y - 8, 3, 0, 2*Math.PI);

		ctx.moveTo(x, y - 4);
		ctx.lineTo(x, y + 2);

		ctx.moveTo(x, y + 2);
		ctx.lineTo(x + 3, y + 10);

		ctx.moveTo(x, y + 2);
		ctx.lineTo(x - 3, y + 10);

		ctx.moveTo(x, y);
		ctx.lineTo(x + 5, y - 4);

		ctx.moveTo(x, y);
		ctx.lineTo(x - 5, y - 4);

		ctx.closePath();
		ctx.stroke();
	}

	function convertDistance2Points(currentDist, maxDist, axisLength){
		return convertDistance2PercentOfScreen(currentDist, maxDist) * axisLength;
	}

	function convertDistance2PercentOfScreen(currentDist, maxDist){
		if(currentDist >= maxDist){return 1;}

		var d = (currentDist/maxDist);
		var val;
		for(var i = 0; i < GPSRadar.translator.length; i++){
			val = GPSRadar.translator[i];
			if(d >= val.d){
				return val.p + ((d - val.d) / 2);
			}
		}

		return (d / val.d) * val.p;
	}

	function calculateRelativePoint(angle, dist){
		var ret = new Array(2);

		var alpharad = angle * (Math.PI / 180);
		ret[0] = Math.sin(alpharad) * dist;
		ret[1] = Math.cos(alpharad) * dist * -1; // * -1 to invert y-axis
		return ret;
	}
}

/*
 * m = Distance in m (max. 1000m)
 * d = Distance in % of max
 * p = percent of screen
 *
 * m | 0m | 40m | 200m | 500m | 1000m
 * -----------------------------------
 * d | 0% |  4% |  20% |  50% | 100%
 * -----------------------------------
 * p | 0% | 25% |  50% |  75% | 100%
 */

GPSRadar.translator = [
	{d: 0.5	, p: 0.75},
	{d: 0.2	, p: 0.5},
	{d: 0.04, p: 0.25},
];

/*
 * Definition for the circles of the Radar
 */
GPSRadar.display = [
	{r: 1.0	, label: "1000m", color: "#ffffff"},
	{r: 0.75, label: "500m"	, color: "#f8f8f8"},
	{r: 0.5	, label: "200m"	, color: "#f2f2f2"},
	{r: 0.25, label: "40m"	, color: "#ededed"}
];

GPSRadar.CoordinateIcon = {
    POINT: 0,
    CIRCLE: 1,
    CROSS: 2
};
