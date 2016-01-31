/*	GeoCat - Geolocation caching and tracking platform
	Copyright (C) 2015 Bastian Kraemer

	PlacesController.js

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
 * Event handling for the "Places" page
 * @class PlacesController
 */
function PlacesController(){

	// Private variables
	var placesPerPage = 10;
	var currentlyDisplayedCoordinates = new Array();
	var currentPage = 0;
	var allPlacesCount = 0;
	var maxPages = 0;
	var currentlyShowingPrivatePlaces = true;
	var localCoordStore = GeoCat.getLocalCoordStore();
	var uplink = GeoCat.getUplink();
	var locale = GeoCat.locale;

	// Collection (Map) of all important HTML elements (defeined by their id)

	var htmlElement = new Object();
	htmlElement["popup"] = "#EditPlacePopup";
	htmlElement["popup_title"] = "#EditPlacePopup_Title";
	htmlElement["popup_save"] = "#EditPlacePopup_Save";
	htmlElement["popup_delete"] = "#EditPlacePopup_Delete";
	htmlElement["field_name"] = "#EditPlacePopup_Name";
	htmlElement["field_lat"] = "#EditPlacePopup_Lat";
	htmlElement["field_lon"] = "#EditPlacePopup_Lon";
	htmlElement["field_desc"] = "#EditPlacePopup_Desc";
	htmlElement["field_ispublic"] = "#EditPlacePopup_Public";
	htmlElement["button_show_private_places"] = "#Places_ShowMyPlaces";
	htmlElement["button_show_public_places"] = "#Places_ShowPublicPlaces";
	htmlElement["button_next_page"] = "#Places_Next";
	htmlElement["button_prev_page"] = "#Places_Prev";
	htmlElement["button_new_place"] = "#Places_newPlace";

	/*
	 * ============================================================================================
	 * Public methods
	 * ============================================================================================
	 */

	/**
	 * This function should be called when the places page is opened
	 *
	 * @public
	 * @function onPageOpened
	 * @memberOf PlacesController
	 * @instance
	 */
	this.onPageOpened = function(){

		if(GeoCat.loginStatus.isSignedIn){
			requestMyPlaces();
		}
		else{
			requestPublicPlaces();
		}

		$(htmlElement["button_show_private_places"]).click(function(){
			requestMyPlaces();
		});
		$(htmlElement["button_show_public_places"]).click(function(){
			requestPublicPlaces();
		});

		$(htmlElement["button_next_page"]).click(function(){
			if(currentPage < maxPages - 1){
				requestPlaces(++currentPage, currentlyShowingPrivatePlaces);
			}
		});

		$(htmlElement["button_prev_page"]).click(function(){
			if(currentPage > 0){
				requestPlaces(--currentPage, currentlyShowingPrivatePlaces);
			}
		});

		$(htmlElement["button_new_place"]).click(function(){
			showEditPlaceDialog(true, -1, "", "", "", "", false);
		});

		$(htmlElement["popup_save"]).click(editPlace_SaveButton_OnClick);

		$(htmlElement["popup_delete"]).click(editPlace_DeleteButton_OnClick);
	}

	/**
	 * This function should be called when the places page is closed
	 *
	 * @public
	 * @function onPageClosed
	 * @memberOf PlacesController
	 * @instance
	 */
	this.onPageClosed = function(){
		$(htmlElement["button_show_private_places"]).unbind();
		$(htmlElement["button_show_public_places"]).unbind();
		$(htmlElement["button_next_page"]).unbind();
		$(htmlElement["button_prev_page"]).unbind();
		$(htmlElement["button_new_place"]).unbind();
		$(htmlElement["popup_save"]).unbind();
		$(htmlElement["popup_delete"]).unbind();
	}

	/*
	 * ============================================================================================
	 * Private methods
	 * ============================================================================================
	 */

	/**
	 * Sends a request to the server to get the first page of the private places list
	 *
	 * @private
	 * @memberOf PlacesController
	 * @instance
	 */
	function requestMyPlaces(){
		currentPage = 0;
		currentlyShowingPrivatePlaces = true;
		sendHTTPRequest();
	}

	/**
	 * Sends a request to the server to get the first page of the public places list
	 *
	 * @private
	 * @memberOf PlacesController
	 * @instance
	 */
	function requestPublicPlaces(){
		currentPage = 0;
		currentlyShowingPrivatePlaces = false;
		sendHTTPRequest();
	}

	/**
	 * Sends two requests to the the server (<b>COUNT</b> and <b>GET</b>)<br />
	 * The requested page index and the private/public places parameter are taken from the
	 * private class variables <i>currentPage</i> and <i>currentlyShowingPrivatePlaces</i><br />
	 * <br />
	 * The requests will be sent by the methods <i>countPlaces()</i> and <i>requestPlaces()</i>
	 * @see countPlaces()
	 * @see requestPlaces()
	 *
	 * @private
	 * @memberOf PlacesController
	 * @instance
	 */
	function sendHTTPRequest(){
		countPlaces(currentlyShowingPrivatePlaces);
		requestPlaces(currentPage, currentlyShowingPrivatePlaces);
		highlightButtons();
	}

	/**
	 * Reloads the current places page (this will send a <b>GET</b> or <b>GET_PUBLIC</b> to the server)<br />
	 * The requested page index and the private/public places parameters are taken from the
	 * private class variables <i>currentPage</i> and <i>currentlyShowingPrivatePlaces</i>
	 *
	 * @private
	 * @memberOf PlacesController
	 * @instance
	 */
	function reloadPlacesPage(){
		requestPlaces(currentPage, currentlyShowingPrivatePlaces);
		countPlaces(currentlyShowingPrivatePlaces);
	}

	/**
	 * Sends a <b>COUNT</b> or <b>COUNT_PUBLIC</b> command to the server
	 * @param privatePlaces {boolean} Count private or public places
	 *
	 * @private
	 * @memberOf PlacesController
	 * @instance
	 */
	function countPlaces(privatePlaces){

		uplink.sendCountRequest(privatePlaces,
				function(response){
					try{
						var result = JSON.parse(response);

						if(result.hasOwnProperty("count")){
							allPlacesCount = parseInt(result.count);
							maxPages = Math.floor(allPlacesCount / placesPerPage) + ((allPlacesCount % placesPerPage == 0) ? 0 : 1);
							updatePageInfo();
						}
						else{
							displayError("An error occured, please try again later.");
						}
					}
					catch(e){
						displayError(Tools.sprintf("An error occured, please try again later.\\n\\n" +
												   "Details:\\n{0}", [e.message]));
					}
				});
	}

	/**
	 * Sends a <b>GET</b> or <b>GET_PUBLIC</b> command to the server
	 * @param pageIndex {integer} The number of places that should be returned from the server.<br />(<u>Offset:</u> <i>pageIndex * placesPerPage</i>, <u>Limit:</u> <i>placesPerPage</i>)
	 * @param privatePlaces {boolean} Count private or public places
	 *
	 * @private
	 * @memberOf PlacesController
	 * @instance
	 */
	function requestPlaces(pageIndex, privatePlaces){

		uplink.sendGetRequest(privatePlaces, pageIndex, placesPerPage,
				function(response){
					try{
						var result = JSON.parse(response);
						if(result.hasOwnProperty("status")){
							displayError(Tools.sprintf("Unable to download the requested information. (Status {0})\\n" +
													   "Server returned: {1}", [response["status"], response["msg"]]))
						}
						else{
							currentlyDisplayedCoordinates.length = 0; //Clear array
							for(var i = 0; i < result.length; i++){
								var coord = result[i].coordinate;
								coord["is_public"] = result[i].isPublic;
								var coordInfo = new CoordinateInfo(result[i].owner, result[i].creationDate, result[i].modificationDate);
								localCoordStore.storePlace(coord, coordInfo);
								currentlyDisplayedCoordinates.push(coord.coord_id);
							}

							updateList();
						}
					}
					catch(e){
						displayError(Tools.sprintf("An error occured, please try again later.\\n\\n" +
												   "Details:\\n{0}", [e.message]));
					}
				});
	}

	/**
	 * Updates the list on the page by using the coordinates from the {@link LocalCoordinateStore}
	 *
	 * @private
	 * @memberOf PlacesController
	 * @instance
	 */
	function updateList(){
		var list = $("#PlacesListView");
		list.empty();

		if(currentlyDisplayedCoordinates.length > 0){

			for(var i = 0; i < currentlyDisplayedCoordinates.length; i++){
				list.append(generatePlaceItemCode(	localCoordStore.get(currentlyDisplayedCoordinates[i]),
													localCoordStore.getInfo(currentlyDisplayedCoordinates[i]),
													(currentPage * placesPerPage) + i + 1));
			}

			list.listview('refresh');
			$("#PlacesListView li a.li-clickable").click(function(){
				place_OnClick(this);
			});

			$("#PlacesListView li a.ui-icon-navigation").click(function(){
				navigateTo_OnClick(this);
			});
		}
		else{
			list.append("<li><span>" + locale.get("places.empty_list", "No places found.") + "</span></li>");
			list.listview('refresh');
		}
		updatePageInfo();
	}

	function ajaxError(xhr, status, error){
		Tools.showPopup("Error", "Ajax request failed.", "OK", null);
	}

	function displayError(message){
		Tools.showPopup("Error", message, "OK", null);
	}

	/**
	 * Generates the HTML-Code for a single place
	 * @param coord {Coordinate} The coordinate for this place
	 * @param coord_info {CoordinateInfo} Additional information to this {@link Coordinate}
	 * @param number {Integer} Number of this place
	 *
	 * @private
	 * @memberOf PlacesController
	 * @instance
	 */
	function generatePlaceItemCode(coord, coord_info, number){

		var isEditable = (coord_info.owner == GeoCat.loginStatus.username) ? "true" : "false";

		return 	"<li class=\"place-list-item\" data-role=\"list-divider\">" +
					"<span class=\"listview-left\">#" + number + " " +coord.name + "</span>" +
					"<span class=\"listview-right\">" + coord_info.owner + "</span></li>" +
				"<li class=\"place-list-item\" coordinate-id=\"" + coord.coord_id + "\" is-editable=\"" + isEditable + "\"><a class=\"li-clickable\">" +
					(coord.desc != null ? "<h2>"+ coord.desc + "</h2>" : "") +
					"<p><strong>Coordinates: </strong>" + coord.lat + ", " + coord.lon + "</p>" +
					"<p class=\"ui-li-aside\" title=\"" + locale.get("places.place_creation_date", "Creation date:") + " " + coord_info.creationDate + "\">" + locale.get("places.last_update", "Last update:") + "<br>" + coord_info.modificationDate + "</p>" +
				"</a><a href=\"#GPSNavigator\" coordinate-id=\"" + coord.coord_id + "\" class=\"ui-icon-navigation\">" + locale.get("places.navigateTo", "Start navigation") + "</a></li>\n";

		// Note: the class ui-icon-navigation is used to identify this objects
	}

	function highlightButtons(){
		if(currentlyShowingPrivatePlaces){
			$(htmlElement["button_show_private_places"]).addClass($.mobile.activeBtnClass);
			$(htmlElement["button_show_public_places"]).removeClass($.mobile.activeBtnClass);
		}
		else{
			$(htmlElement["button_show_private_places"]).removeClass($.mobile.activeBtnClass);
			$(htmlElement["button_show_public_places"]).addClass($.mobile.activeBtnClass);
		}
	}

	function updatePageInfo(){
		var numPages = maxPages > 0 ? maxPages : 1;
		$("#PlacesInformation").html(Tools.sprintf(locale.get("page_of", "Page {0} of {1}"), [(currentPage + 1), numPages]) + " " +
									 Tools.sprintf(locale.get("places.count", "(Total number: {0})"), [allPlacesCount]));
	}

	/**
	 * Shows the edit dialog for a place.<br />
	 * This function can be used for new coordinates too.
	 * @param addNewPlace {Boolean} Create a new place or edit an existing one. If this parameter is <code>true</code> all other parameters can be ignored.
	 * @param coordId {Integer} The coordinate_id
	 * @param name {String} Name of this place
	 * @param description {String} Description of this place
	 * @param latitude {Double} The latitude of this place
	 * @param longitude {Double} The longitude of this place
	 * @param isPublic {Boolean} Is the place visible for everyone?
	 *
	 * @private
	 * @memberOf PlacesController
	 * @instance
	 */
	function showEditPlaceDialog(addNewPlace, coordId, name, description, latitude, longitude, isPublic){

		// Hide the delete button of addNewPlace is true
		$(htmlElement["popup_delete"]).css("visibility", addNewPlace ? "hidden" : "visible");

		if(addNewPlace){
			$(htmlElement["popup_title"]).html(locale.get("places.add_place", "Add Place"));
		}
		else{
			$(htmlElement["popup_title"]).html(locale.get("places.edit_place", "Edit Place"));
		}

		// If latitude and longitude are empty, try to get the latest coordinate from the GPSNavigatior
		if(latitude == "" && longitude == ""){
			$(htmlElement["field_lat"]).val("");
			$(htmlElement["field_lon"]).val("");

			GPS.getOnce(function(pos){
				if($(htmlElement["field_lat"]).val() == "" && $(htmlElement["field_lon"]).val("")){
					$(htmlElement["field_lat"]).val(pos.coords.latitude);
					$(htmlElement["field_lon"]).val(pos.coords.longitude);
				}
			});
		}

		// Insert the values into the HTML form
		disableSaveButton(false);
		$(htmlElement["field_name"]).val(name);
		$(htmlElement["field_desc"]).val(description);

		$(htmlElement["field_ispublic"]).prop("checked", (isPublic == true || isPublic == 1)).checkboxradio('refresh');

		$(htmlElement["popup"]).attr("coordinate-id", coordId);
		$(htmlElement["popup"]).attr("new-place", addNewPlace);
		$(htmlElement["popup"]).popup("open", {positionTo: "window", transition: "none"});
	}

	function disableSaveButton(disable){
		if(disable){
			$(htmlElement["popup_save"]).attr("disabled", "");
			$(htmlElement["popup_delete"]).attr("disabled", "");
		}
		else{
			$(htmlElement["popup_save"]).removeAttr("disabled");
			$(htmlElement["popup_delete"]).removeAttr("disabled");
		}
	}

	function closePopup(){
		$(htmlElement["popup"]).popup("close");
		$(htmlElement["button_new_place"]).removeClass($.mobile.activeBtnClass);
		highlightButtons();
	}

	function uplinkErrorHander(response){
		closePopup();
		setTimeout(function(){
			displayError(Tools.sprintf(	"Unable to perform this operation. (Status {0})\\n" +
										"Server returned: {1}", [response["status"], response["msg"]]));
			disableSaveButton(false);
		}, 500);
	}

	/*
	 * ============================================================================================
	 * "OnClick" functions
	 * ============================================================================================
	 */

	function place_OnClick(el){

		var isEditale = $(el).parent().attr("is-editable") == "true";
		var coordId = $(el).parent().attr("coordinate-id");

		var place = localCoordStore.get(coordId);
		if(coordId !=  null){
			showEditPlaceDialog(false, coordId, place.name, place.desc, place.lat, place.lon, place.is_public);
		}
		if(!isEditale){
			disableSaveButton(true);
		}
	}

	function navigateTo_OnClick(el){
		var coord = localCoordStore.get($(el).attr("coordinate-id"));
		if(coord != null){
			if(!localCoordStore.isPartOfCurrentNavigation(coord)){
				uplink.sendNavList_Add(coord.coord_id,
					function(result){
						localCoordStore.addCoordinateToNavigation(coord);
					},
					uplinkErrorHander);
			}
		}
	}

	function editPlace_SaveButton_OnClick(){

		// This attribute in the HTML element specifies if a new place should be added
		var newPlace = $(htmlElement["popup"]).attr("new-place");

		// Read the values from the input fields
		var name = $(htmlElement["field_name"]).val();
		var desc = $(htmlElement["field_desc"]).val();
		var lat = parseFloat($(htmlElement["field_lat"]).val());
		var lon = parseFloat($(htmlElement["field_lon"]).val());
		var isPublic = $(htmlElement["field_ispublic"]).is(":checked");

		// Verfiy the data
		if(name == "" || isNaN(lat) || isNaN(lon)){
			alert("Please enter a valid name and values for latitude and longitude.");
			return;
		}

		var msg = "%s contains invalid characters. Only 'A-Z', '0-9' and some special characters like ('!,;.#_-*') are allowed."
		if(!localCoordStore.verifyString(name)){
			alert(msg.replace("%s", "The name"));
			return;
		}

		if(desc != ""){
			if(!localCoordStore.verifyDescriptionString(desc)){
				alert(msg.replace("%s", "The description"));
				return;
			}
		}

		// Update the database

		if(newPlace == "true"){
			uplink.sendNewCoordinate(name, desc, lat, lon, isPublic,
										function(result){
											closePopup();
											reloadPlacesPage();
										},
										function(response){
											alert(Tools.sprintf("Unable to perform this operation. (Status {0})\n" +
																"Server returned: {1}", [response["status"], response["msg"]]));
											disableSaveButton(false);
										});
			disableSaveButton(true);
		}
		else{
			var id = $(htmlElement["popup"]).attr("coordinate-id");
			place = localCoordStore.get(id)

			if(place != null){
				place.name = name;
				place.desc = desc;
				place.lat = lat;
				place.lon = lon;
				place.is_public = isPublic;
				uplink.sendCoordinateUpdate(place,
						function(result){
							closePopup();
							updateList();
						},
						uplinkErrorHander);
			}
		}
	}

	function editPlace_DeleteButton_OnClick(){
		var id = $(htmlElement["popup"]).attr("coordinate-id");
		if(id != undefined && id >= 0){
			disableSaveButton(true);
			uplink.sendDeleteCoordinate(id,
						function(result){
							closePopup();
							reloadPlacesPage();
						},
						uplinkErrorHander);
		}
	}
}

PlacesController.currentInstance = null;

PlacesController.init = function(){
	$(document).on("pageshow", "#Places", function(){
		PlacesController.currentInstance = new PlacesController();
		PlacesController.currentInstance.onPageOpened();
	});

	$(document).on("pagebeforehide", "#Places", function(){
		PlacesController.currentInstance.onPageClosed();
		PlacesController.currentInstance = null
	});
};
