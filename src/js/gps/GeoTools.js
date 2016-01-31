/*	GeoCat - Geocaching and -Tracking platform
	Copyright (C) 2015 Bastian Kraemer

	GeoTools.js

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
 * A collection of methods which are useful when dealing with GPS coordinates
 * @namespace GeoTools
 */
var GeoTools = new function(){

	/**
	 * Convert degrees to radians
	 * @param {Number} deg Angle in degrees
	 * @returns {Number} Angle in radians
	 */
	this.toRad = function(deg){
		return deg * (Math.PI/180);
	}

	/**
	 * This function is based on an example published by Daniel Braun at
	 * http://www.daniel-braun.com/technik/distanz-zwischen-zwei-gps-koordinaten-in-java-berchenen/
	 * @param {double} lat1 latitude of position 1
	 * @param {double} lon1 longitude of position 1
	 * @param {double} lat2 latitude of position 2
	 * @param {double} lon2 longitude of position 2
	 * @returns The distance between both position in kilometers
	 */
	this.calculateDistance = function(lat1, lon1, lat2, lon2) {
		var radius = 6371; // Earth radius
		var lat = this.toRad(lat2 - lat1);
		var lon = this.toRad(lon2 - lon1);
		var a = Math.sin(lat / 2) * Math.sin(lat / 2) + Math.cos(this.toRad(lat1)) * Math.cos(this.toRad(lat2)) * Math.sin(lon / 2) * Math.sin(lon / 2);
		var c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
		var d = radius * c;
		return Math.abs(d).toFixed(4);
	};

	/**
	 * Calculates the angle between two points in degrees where 0 <= angle < 360 (counted clockwise)
	 * @param {double} x1 X-Coordinate of point 1
	 * @param {double} y1 Y-Coordinate of point 1
	 * @param {double} x2 X-Coordinate of point 2
	 * @param {double} y2 Y-Coordinate of point 2
	 * @returns The angle beteewn point 1 and point 2
	 */
	this.calculateAngleTo = function(x1, y1, x2, y2)
	{
		var angle;
		var dx = parseFloat(x2) - parseFloat(x1);
		var dy = (parseFloat(y2) - parseFloat(y1));

		if(dx == 0){if(dy > 0){return 0;}else{return 180;}}
		if(dy == 0){if(dx <= 0){return 90;}else{return 270;}}
		//return Math.atan(dy / dx) * (180 / Math.PI);
		if(dx > 0){
			if(dy > 0){
				angle = Math.atan(dx / dy) * (180 / Math.PI);
				return angle;
			}
			else
			{
				angle = Math.atan((dy * -1) / dx) * (180 / Math.PI);
				return angle + 90;
			}
		}
		else{
			if(dy < 0){
				angle = Math.atan((dx * -1) / (dy * -1)) * (180 / Math.PI);
				return angle + 180;
			}
			else{
				angle = Math.atan(dy / (dx * -1)) * (180 / Math.PI);
				return angle + 270;
			}
		}
	};
}
