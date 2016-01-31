/**
 * This class handels the AJAX requests which are sent to the server
 * @class Uplink
 * @param pathToRootDirectory {String} Path to the root root directory (for example './')
 */
function Uplink(pathToRootDirectory){

	var urlPrefix = pathToRootDirectory;

	function sendHTTPRequest(url, dataObj, expectStatusResponse, successCallback, errorCallback, onAjaxError){
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
					if(onAjaxError != null){
						onAjaxError(error)
					}
				}
		});
	}

	function ajaxERROR(msg){
		alert("Unable to send HTTP Request: " + msg);
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
	this.sendGetRequest = function(getPrivatePlaces, pageIndex, placesPerPage, successCallback){
		sendHTTPRequest(urlPrefix + "query/places.php",
						{
							cmd: getPrivatePlaces ? "get" : "get_public",
							limit: placesPerPage,
							offset: placesPerPage * pageIndex
						},
						false,
						successCallback,
						null,
						ajaxERROR);
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
		sendHTTPRequest(urlPrefix + "query/places.php",
						{cmd: countPrivatePlaces ? "count" : "count_public"},
						false,
						successCallback,
						null,
						ajaxERROR);
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
		sendHTTPRequest(urlPrefix + "query/places.php",
						{
							cmd: "add",
							name: placeName,
							desc: placeDesc,
							lat: placeLat,
							lon: placeLon,
							is_public: placeIsPublic
						},
						true,
						successCallback,
						errorCallback,
						ajaxERROR);
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
		sendHTTPRequest(urlPrefix + "query/places.php",
						{cmd: "update", data_type: "json", data: JSON.stringify(coord)},
						true,
						successCallback,
						errorCallback,
						ajaxERROR);
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
		sendHTTPRequest(urlPrefix + "query/places.php",
						{cmd: "remove", coord_id: coordId},
						true,
						successCallback,
						errorCallback,
						ajaxERROR);
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
		sendHTTPRequest(urlPrefix + "query/places.php",
						{cmd: "nav_add", coord_id: coordId},
						true,
						successCallback,
						errorCallback,
						ajaxERROR);
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
		sendHTTPRequest(urlPrefix + "query/places.php",
						{
							cmd: "nav_create",
							name: placeName,
							desc: placeDesc,
							lat: placeLat,
							lon: placeLon
						},
						true,
						successCallback,
						errorCallback,
						ajaxERROR);
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
		sendHTTPRequest(urlPrefix + "query/places.php",
						{cmd: "nav_get"},
						false,
						successCallback,
						errorCallback,
						ajaxERROR);
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
		sendHTTPRequest(urlPrefix + "query/places.php",
						{cmd: "nav_remove", coord_id: coordId},
						true,
						successCallback,
						errorCallback,
						ajaxERROR);
	}

	/*
	 * Challenges
	 */
	this.sendChallenge_GetPublic = function(successCallback, response_limit, response_offset){
		sendHTTPRequest(urlPrefix + "query/challenge.php",
						{
							task: "get_challenges",
							limit: response_limit,
							offset: response_offset
						},
						false,
						successCallback,
						null,
						ajaxERROR);
	}

	this.sendChallenge_CountPublic = function(successCallback){
		sendHTTPRequest(urlPrefix + "query/challenge.php",
						{task: "count_challenges"},
						false,
						successCallback,
						null,
						ajaxERROR);
	}
}

function sendRequest(toUrl, body, cfunc){
	var xhttp = new XMLHttpRequest();
	xhttp.onreadystatechange = function(){
		if(xhttp.readyState == 4 && xhttp.status == 200){
			if(typeof cfunc == 'function'){ cfunc(xhttp); }
		}
	}
	xhttp.open("POST", toUrl);
	xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
	xhttp.send(body);
}
