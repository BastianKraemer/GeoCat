/* GeoCat - Geocaching and -tracking application
 * Copyright (C) 2015 Bastian Kraemer
 *
 * LocalCoordinateStore.js
 *
 * This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * This class implements a local coordinate store.<br />
 * You can use this to share coordinates with other JavaScript functions.
 * @class LocalCoordinateStore
 */
function LocalCoordinateStore(){

	var localStore = new Object();
	var currentNavigation = new Object();
	var coordinateInformation = new Object();
	var temporaryIdCounter = -1;

	this.storeCoordinate = storeCoordinate;

	/**
	 * Stores a coordinate
	 * @param coord {Coordinate} The coordinate that will be stored
	 *
	 * @public
	 * @memberOf LocalCoordinateStore
	 * @instance
	 */
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

	/**
	 * Stores a place
	 * @param coord {Coordinate} The coordinate that will be stored
	 * @param metaInformation {CoordinateInfo} Additional information to this coordinate (for example the owner)
	 *
	 * @public
	 * @memberOf LocalCoordinateStore
	 * @instance
	 */
	function storePlace(coord, metaInformation){
		var id = storeCoordinate(coord);
		coordinateInformation[id] = metaInformation;
		return id;
	}

	this.get = get;

	/**
	 * Returns a coordinate that has been stored, defined by its id
	 * @param id {Integer} The coord_id of the coordinate
	 * @returns {Coordinate|undefined} The {@link Coordinate} or <code>undefined</code> if there is no {@link Coordinate} with this id
	 *
	 * @public
	 * @memberOf LocalCoordinateStore
	 * @instance
	 */
	function get(id){
		return localStore[id];
	}

	this.getInfo = getInfo;

	/**
	 * Returns the additional information to {@link Coordinate}
	 * @param id {Integer} The coord_id of the coordinate
	 * @returns {CoordinateInfo|undefined} The {@link CoordinateInfo} or <code>undefined</code> if there are no additional information to this {@link Coordinate}
	 *
	 * @public
	 * @memberOf LocalCoordinateStore
	 * @instance
	 */
	function getInfo(id){
		return coordinateInformation[id];
	}

	this.remove = remove;

	/**
	 * Removes a stored coordinate
	 * @param coord {Coordinate} The coordinate that will be removed
	 *
	 * @public
	 * @memberOf LocalCoordinateStore
	 * @instance
	 */
	function remove(coord){
		removeById(coord.coord_id);
	}

	this.removeById = removeById;

	/**
	 * Removes a stored {@link Coordinate}, identified by its id
	 * @param id {Integer} The coord_id
	 *
	 * @public
	 * @memberOf LocalCoordinateStore
	 * @instance
	 */
	function removeById(id){
		if(localStore.hasOwnProperty(id)){
			delete localStore[id];
		}
	}

	this.isPartOfCurrentNavigation = isPartOfCurrentNavigation;

	/**
	 * Checks if a coordinate is part of the destination list
	 * @param coord {Coordinate} The coordinate
	 * @returns {Boolean} <code>true</code> if the coordinate is part of the destination list, <code>false</code> if not
	 *
	 * @public
	 * @memberOf LocalCoordinateStore
	 * @instance
	 */
	function isPartOfCurrentNavigation(coord){
		return currentNavigation.hasOwnProperty(coord.coord_id);
	}

	this.addCoordinateToNavigation = addCoordinateToNavigation;

	/**
	 * Adds a coordinate to the destination list
	 * @param coord {Coordinate} The coordinate that will be added to the list
	 *
	 * @public
	 * @memberOf LocalCoordinateStore
	 * @instance
	 */
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

	this.getCurrentNavigation = getCurrentNavigation;

	/**
	 * Returns a the current navigation list
	 * @returns {Object.<String, Coordinate>}
	 *
	 * @public
	 * @memberOf LocalCoordinateStore
	 * @instance
	 */
	function getCurrentNavigation(){
		return currentNavigation;
	}

	this.forEachNavigationEntry = forEachNavigationEntry;

	/**
	 * Iterates over all navigation items
	 * @param callback {function} The function that will be called with every coordinate.<br /><i>function( <b>{@link Coordinate}</b> )</i>
	 *
	 * @public
	 * @memberOf LocalCoordinateStore
	 * @instance
	 */
	function forEachNavigationEntry(callback){
		for(var coord in currentNavigation) {
			callback(coord);
		}
	}

	this.removeFromNavigation = removeFromNavigation;

	/**
	 * Removes a coordinate from the destination list
	 * @param coord {Coordinate} The coordinate that will be added to the list
	 *
	 * @public
	 * @memberOf LocalCoordinateStore
	 * @instance
	 */
	function removeFromNavigation(coord){
		removeFromNavigation(coord.coord_id);
	}

	this.removeFromNavigationById = removeFromNavigationById;

	/**
	 * Removes a coordinate from the destination list, identified by its id
	 * @param id {Integer} The coord_id
	 *
	 * @public
	 * @memberOf LocalCoordinateStore
	 * @instance
	 */
	function removeFromNavigationById(id){
		if(currentNavigation.hasOwnProperty(id)){
			delete currentNavigation[id];
		}
	}
}

/**
 * This class represents a location that can be send as JSON object to the server
 * @class Coordinate
 * @param {Integer} id The coordinate id
 * @param {String} name Name of this location
 * @param {Double} lat Latitude of this location
 * @param {Double} lon Logitude of this location
 * @param {String} description Description of this location
 * @param {Boolean} isPublic Is this place visible for everyone
 * @see Uplink
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
 * @param {Double} lon Longitude of this location
 * @param {String} description Description of this location
 * @returns {Coordinate} A new coordinate
 */
Coordinate.create = function(name, latitude, longitude, description){
	return new Coordinate(null, name, latitude, longitude, description, false);
};

/**
 * This class stores information about a coordinate, for example its owner
 * @class CoordinateInfo
 * @param {String} owner The owner of this {@link Coordinate}
 * @param {String} creationDate Creation date of this {@link Coordinate}
 * @param {String} modificationDate Timestamp of the last modification date
 * @see Coordinate
 */
function CoordinateInfo(owner, creationDate, modificationDate){
	this.owner = owner;
	this.creationDate = creationDate;
	this.modificationDate = modificationDate;
}
