/* GeoCat - Geocaching and -tracking application
 * Copyright (C) 2016 Bastian Kraemer, Raphael Harzer
 *
 * Uplink.js
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
 * This class handels the AJAX requests which are sent to the server
 * @class Uplink
 */
function Uplink(){

	function sendHTTPRequest(url, dataObj, expectStatusResponse, successCallback, errorCallback){
		$.ajax({
				type: "POST", url: url,
				data: dataObj,
				cache: false,
				success: function(response){
					if(expectStatusResponse){
						var result = JSON.parse(response);
						if(result.status == "ok"){
							successCallback(result);
						} else{
							errorCallback(result);
						}
					} else {
						successCallback(response);
					}
				},
				error: function(xhr, status, error){
					GeoCat.displayError();
				}
		});
	}

	/**
	 * Sends a <b>GET</b> or <b>GET_PUBLIC</b> command to the server
	 * @param getPrivatePlaces {Boolean} Get <i>private</i> or <i>public</i> places
	 * @param pageIndex {Integer}
	 * @param placesPerPage {Integer}
	 * @param successCallback {function} Callback for a successful request:<br /><i>function( <b>JSON-String</b> )</i>
	 *
	 * @public
	 * @function sendGetRequest
	 * @memberOf Uplink
	 * @instance
	 */
	this.sendGetRequest = function(getPrivatePlaces, offset, limit, filter, successCallback){

		var ajaxData = {
			task: getPrivatePlaces ? "get" : "get_public",
			offset: offset,
			limit: limit
		}

		if(filter != null){
			ajaxData["filter"] = filter;
		}

		sendHTTPRequest("query/places.php",
						ajaxData,
						false,
						successCallback,
						null);
	}

	/**
	 * Sends a <b>COUNT</b> or <b>COUNT_PUBLIC</b> command to the server
	 * @param countPrivatePlaces {Boolean} Count <i>private</i> or <i>public</i> places
	 * @param successCallback {function} Callback for a successful request:<br /><i>function( <b>JSON-String</b> )</i>
	 *
	 * @public
	 * @function sendCountRequest
	 * @memberOf Uplink
	 * @instance
	 */
	this.sendCountRequest = function(countPrivatePlaces, successCallback){
		sendHTTPRequest("query/places.php",
						{task: countPrivatePlaces ? "count" : "count_public"},
						true,
						successCallback,
						null);
	}

	/**
	 * Sends a <b>ADD</b> command to the server to store a new place in the database
	 * @param placeName {String} Name of this place
	 * @param placeDesc {String} Description of this place
	 * @param placeLat {Double} Latitude of this place
	 * @param placeLon {Double} Longitude of this place
	 * @param placeIsPublic {Boolean} Is the place visible for everyone?
	 * @param successCallback {function} Callback for a successful request:<br /><i>function( <b>Object</b> )</i>
	 * @param errorCallback {function} Callback if the server returns an error:<br /><i>function( <b>Object</b> )</i>
	 *
	 * @public
	 * @function sendNewCoordinate
	 * @memberOf Uplink
	 * @instance
	 */
	this.sendNewCoordinate = function(placeName, placeDesc, placeLat, placeLon, placeIsPublic, successCallback, errorCallback) {
		sendHTTPRequest("query/places.php",
						{
							task: "add",
							name: placeName,
							desc: placeDesc,
							lat: placeLat,
							lon: placeLon,
							is_public: (placeIsPublic ? 1 : 0)
						},
						true,
						successCallback,
						errorCallback);
	}

	/**
	 * Sends a <b>UPDATE</b> command to the server to update a place in the database
	 * @param coord {Coordinate} The coordinate that should be updated
	 * @param successCallback {function} Callback for a successful request:<br /><i>function( <b>Object</b> )</i>
	 * @param errorCallback {function} Callback if the server returns an error:<br /><i>function( <b>Object</b> )</i>
	 *
	 * @public
	 * @function sendCoordinateUpdate
	 * @memberOf Uplink
	 * @instance
	 */
	this.sendCoordinateUpdate = function(coord, successCallback, errorCallback) {
		sendHTTPRequest("query/places.php",
						{
							task: "update",
							coord_id: coord.coord_id,
							name: coord.name,
							desc: coord.desc,
							lat: coord.lat,
							lon: coord.lon,
							is_public: coord.is_public ? 1 : 0
						},
						true,
						successCallback,
						errorCallback);
	}

	/**
	 * Sends a <b>REMOVE</b> command to the server to remove a place from the database
	 * @param coordId {Integer}
	 * @param successCallback {function} Callback for a successful request:<br /><i>function( <b>Object</b> )</i>
	 * @param errorCallback {function} Callback if the server returns an error:<br /><i>function( <b>Object</b> )</i>
	 *
	 * @public
	 * @function sendDeleteCoordinate
	 * @memberOf Uplink
	 * @instance
	 */
	this.sendDeleteCoordinate = function(coordId, successCallback, errorCallback) {
		sendHTTPRequest("query/places.php",
						{task: "remove", coord_id: coordId},
						true,
						successCallback,
						errorCallback);
	}

	/**
	 * Sends a <b>NAV_ADD</b> command to the server to add a coordinate to the current navigation
	 * @param coordId {Integer}
	 * @param successCallback {function} Callback for a successful request:<br /><i>function( <b>Object</b> )</i>
	 * @param errorCallback {function} Callback if the server returns an error:<br /><i>function( <b>Object</b> )</i>
	 *
	 * @public
	 * @function sendNavList_Add
	 * @memberOf Uplink
	 * @instance
	 */
	this.sendNavList_Add = function(coordId, successCallback, errorCallback){
		sendHTTPRequest("query/places.php",
						{task: "nav_add", coord_id: coordId},
						true,
						successCallback,
						errorCallback);
	}

	/**
	 * Sends a <b>NAV_CREATE</b> command to the server to add a new coordinate to the current navigation
	 * @param placeName {String} Name of this place
	 * @param placeDesc {String} Description of this place
	 * @param placeLat {Double} Latitude of this place
	 * @param placeLon {Double} Longitude of this place
	 * @param successCallback {function} Callback for a successful request:<br /><i>function( <b>Object</b> )</i>
	 * @param errorCallback {function} Callback if the server returns an error:<br /><i>function( <b>Object</b> )</i>
	 *
	 * @public
	 * @function sendNavList_Create
	 * @memberOf Uplink
	 * @instance
	 */
	this.sendNavList_Create = function(placeName, placeDesc, placeLat, placeLon, successCallback, errorCallback){
		sendHTTPRequest("query/places.php",
						{
							task: "nav_create",
							name: placeName,
							desc: placeDesc,
							lat: placeLat,
							lon: placeLon
						},
						true,
						successCallback,
						errorCallback);
	}

	/**
	 * Sends a <b>NAV_GET</b> command to the server to get the current navigation list
	 * @param successCallback {function} Callback for a successful request:<br /><i>function( <b>Object</b> )</i>
	 * @param errorCallback {function} Callback if the server returns an error:<br /><i>function( <b>Object</b> )</i>
	 *
	 * @public
	 * @function sendNavList_Get
	 * @memberOf Uplink
	 * @instance
	 */
	this.sendNavList_Get = function(successCallback, errorCallback){
		sendHTTPRequest("query/places.php",
						{task: "nav_get"},
						false,
						successCallback,
						errorCallback);
	}

	/**
	 * Sends a <b>NAV_REMOVE</b> command to the server to remove a coordinate from your current navigation list
	 * @param coordId {Integer}
	 * @param successCallback {function} Callback for a successful request:<br /><i>function( <b>Object</b> )</i>
	 * @param errorCallback {function} Callback if the server returns an error:<br /><i>function( <b>Object</b> )</i>
	 *
	 * @public
	 * @function sendNavList_Remove
	 * @memberOf Uplink
	 * @instance
	 */
	this.sendNavList_Remove = function(coordId, successCallback, errorCallback){
		sendHTTPRequest("query/places.php",
						{task: "nav_remove", coord_id: coordId},
						true,
						successCallback,
						errorCallback);
	}
}
