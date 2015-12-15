/*	GeoCat - Geolocation caching and tracking platform
	Copyright (C) 2015 Bastian Kraemer

	LocalCoordinateStore.js

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

function LocalCoordinateStore(){

	var localStore = new Object();
	var currentNavigation = new Object();
	var coordinateInformation = new Object();
	var temporaryIdCounter = -1;

	this.storeCoordinate = storeCoordinate;

	function storeCoordinate(coord){
		if(coord.coord_id == null){
			var ret = temporaryIdCounter;
			localStore[temporaryIdCounter--] = coord;
			return ret;
		}
		else{
			localStore[coord.coord_id] = coord;
			return coord.coord_id;
		}
	}

	this.storePlace = storePlace;

	function storePlace(coord, metaInformation){
		var id = storeCoordinate(coord);
		coordinateInformation[id] = metaInformation;
		return id;
	}

	this.addCoordinateToNavigation = addCoordinateToNavigation;

	function addCoordinateToNavigation(coord){
		if(coord.coord_id == null){
			currentNavigation[this.storeCoordinate(coord)] = coord;
		}
		else{
			if(localStore.hasOwnProperty(coord.coord_id)){
				currentNavigation[coord.coord_id] = coord;
			}
			else{
				currentNavigation[this.storeCoordinate(coord)] = coord;
			}
		}
	}

	this.get = get;

	function get(id){
		return localStore[id];
	}

	this.getCurrentNavigation = getCurrentNavigation;

	function getCurrentNavigation(){
		return currentNavigation;
	}

	this.getInfo = getInfo;

	function getInfo(id){
		return coordinateInformation[id];
	}

	this.forEachNavigationEntry = forEachNavigationEntry;

	function forEachNavigationEntry(callback){
		for(var coord in currentNavigation) {
			callback(coord);
		}
	}

	this.remove = remove;

	function remove(coord){
		removeById(coord.coord_id);
	}

	this.removeById = removeById;

	function removeById(id){
		if(localStore.hasOwnProperty(id)){
			delete localStore[id];
		}
	}

	this.removeFromNavigation = removeFromNavigation;

	function removeFromNavigation(coord){
		removeFromNavigation(coord.coord_id);
	}

	this.removeFromNavigationById = removeFromNavigationById;

	function removeFromNavigationById(id){
		if(currentNavigation.hasOwnProperty(id)){
			delete currentNavigation[id];
		}
	}
}


/**
 * This class represents a location an can be send as JSON object to the server
 * @class Coordinate
 * @param {Integer} id The coordinate id
 * @param {String} name Name of this location
 * @param {Double} lat Latitude of this location
 * @param {Double} lon Logitude of this location
 * @param {String} description Description of this location
 * @param {Boolean} isPublic Is this place visible fr everyone
 */
function Coordinate(id, name, latitude, longitude, description, isPublic){
	this.coord_id = id;
	this.lat = latitude;
	this.lon = longitude;
	this.name = name;
	this.desc = description;
	this.is_public = isPublic;
}

/**
 * Creates a new coordinate without a 'coordinate id'
 * @param {String} name Name of this location
 * @param {Double} lat Latitude of this location
 * @param {Double} lon Logitude of this location
 * @param {String} description Description of this location
 * @returns {Coordinate} New coordinate
 */
Coordinate.create = function(name, latitude, longitude, description){
	return new Coordinate(null, name, latitude, longitude, description, false);
};

/**
 * This class stores information about a coordinate, for example its owner
 * @class CoordinateInfo
 * @param {Integer} id The coordinate id
 * @param {String} owner The owner of this coordinate
 * @param {String} creationDate Creation date of this coordinate (or place)
 * @param {String} modificationDate Timestamp of the last modification date
 */
function CoordinateInfo(owner, creationDate, modificationDate){
	this.owner = owner;
	this.creationDate = creationDate;
	this.modificationDate = modificationDate;
}
