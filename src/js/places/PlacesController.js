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

function PlacesController(localCoordinateStore, myuplink, gpsNavigationControler){

	var placesPerPage = 10;
	var currentlyDisplayedCoordinates = new Array();
	var currentPage = 0;
	var allPlacesCount = 0;
	var maxPages = 0;
	var currentlyShowingPrivatePlaces = true;
	var localCoordStore = localCoordinateStore;
	var uplink = myuplink;
	var gpsNav = gpsNavigationControler;

	var idList = new Object();
	idList["popup"] = "#EditPlacePopup";
	idList["popup_title"] = "#EditPlacePopup_Title";
	idList["popup_save"] = "#EditPlacePopup_Save";
	idList["popup_delete"] = "#EditPlacePopup_Delete";
	idList["popup_close"] = "#EditPlacePopup_Close";
	idList["field_name"] = "#EditPlacePopup_Name";
	idList["field_lat"] = "#EditPlacePopup_Lat";
	idList["field_lon"] = "#EditPlacePopup_Lon";
	idList["field_desc"] = "#EditPlacePopup_Desc";
	idList["field_ispublic"] = "#EditPlacePopup_Public";
	idList["show_private_places"] = "#Places_ShowMyPlaces";
	idList["show_public_places"] = "#Places_ShowPublicPlaces";
	idList["next_page"] = "#Places_Next";
	idList["prev_page"] = "#Places_Prev";
	idList["new_place"] = "#Places_newPlace";

	this.onPageOpened = function(){

		requestMyPages();
		$(idList["show_private_places"]).click(function(){
			requestMyPages();
		});
		$(idList["show_public_places"]).click(function(){
			requestPublicPages();
		});

		$(idList["next_page"]).click(function(){
			if(currentPage < maxPages - 1){
				requestPlaces(++currentPage, currentlyShowingPrivatePlaces);
			}
		});

		$(idList["prev_page"]).click(function(){
			if(currentPage > 0){
				requestPlaces(--currentPage, currentlyShowingPrivatePlaces);
			}
		});

		$(idList["new_place"]).click(function(){
			showPlaceEditPopup(true, -1, "", "", "", "", false);
		});

		$(idList["popup_save"]).click(editPlacesSaveButtonClicked);

		$(idList["popup_close"]).click(function(){
			$(idList["popup"]).popup("close");
		});

		$(idList["popup_delete"]).click(editPlacesDeleteButtonClicked);
	}

	this.onPageClosed = function(){
		$(idList["show_private_places"]).unbind();
		$(idList["show_public_places"]).unbind();
		$(idList["next_page"]).unbind();
		$(idList["prev_page"]).unbind();
		$(idList["new_place"]).unbind();
		$(idList["popup_save"]).unbind();
		$(idList["popup_delete"]).unbind();
		$(idList["popup_close"]).unbind();
	}

	function requestMyPages(){
		currentPage = 0;
		currentlyShowingPrivatePlaces = true;
		sendRequest();
	}

	function requestPublicPages(){
		currentPage = 0;
		currentlyShowingPrivatePlaces = false;
		sendRequest();
	}

	function sendRequest(){
		countPlaces(currentlyShowingPrivatePlaces);
		requestPlaces(currentPage, currentlyShowingPrivatePlaces);
	}

	function reloadPlacesPage(){
		requestPlaces(currentPage, currentlyShowingPrivatePlaces);
		countPlaces(currentlyShowingPrivatePlaces);
	}

	function countPlaces(privatePlaces){
		var url = "./query/places.php";
		$.ajax({type: "POST", url: url,
			data:{	cmd: privatePlaces ? "count" : "count_public"},
			cache: false,
			success: function(response){
				try{
					var result = JSON.parse(response);

					if(result.hasOwnProperty("status")){
						displayError(Tools.sprintf("Unable to download the requested information. (Status {0})\\n" +
				   									"Server returned: {1}", [result.status, result.msg]));
					}
					else if(result.hasOwnProperty("count")){
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
			},
			error: function(xhr, status, error){
				displayError("AJAX request failed. Unable to get '" + url + "'");
			}
		});
	}

	function displayError(message){
		Tools.showPopup("Error", message, "OK", null);
	}

	function updatePageInfo(){
		$("#PlacesInformation").html(Tools.sprintf(locale.get("places.page_of", "Page {0} of {1}"), [(currentPage + 1), maxPages]) + " " +
									 Tools.sprintf(locale.get("places.count", "(Total number: {0})"), [allPlacesCount]));
	}

	function requestPlaces(pageIndex, privatePlaces){
		$.ajax({type: "POST", url: "./query/places.php",
			data:{	cmd: privatePlaces ? "get" : "get_public",
					limit: placesPerPage,
					offset: placesPerPage * pageIndex},
			cache: false,
			success: function(response){
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
			},
			error: function(xhr, status, error){
				Tools.showPopup("Error", "Ajax request failed.", "OK", null);
		}});
	}

	function updateList(){
		var list = $("#PlacesListView");
		list.empty();

		for(var i = 0; i < currentlyDisplayedCoordinates.length; i++){
			list.append(generatePlaceItemCode(	localCoordinateStore.get(currentlyDisplayedCoordinates[i]),
												localCoordinateStore.getInfo(currentlyDisplayedCoordinates[i]),
												(currentPage * placesPerPage) + i + 1));
		}

		list.listview('refresh');
		$("#PlacesListView li a.li-clickable").click(function(){
			placeLiOnClick(this);
		});

		$("#PlacesListView li a.ui-icon-navigation").click(function(){
			navigateTo_OnClick(this);
		});
		updatePageInfo();
	}

	function generatePlaceItemCode(coord, coord_info, number){

		return 	"<li class=\"place-list-item\" data-role=\"list-divider\" coordinate-id=\"" + coord.coord_id + "\" is-editable=\"true\">" +
					"<span class=\"place-name\">#" + number + " " +coord.name + "</span>" +
					"<span class=\"place-owner\">" + coord_info.owner + "</span></li>" +
				"<li class=\"place-list-item\" coordinate-id=\"" + coord.coord_id + "\" is-editable=\"true\"><a class=\"li-clickable\">" +
					(coord.desc != null ? "<h2>"+ coord.desc + "</h2>" : "") +
					"<p><strong>Coordinates: </strong>" + coord.lat + ", " + coord.lon + "</p>" +
					"<p class=\"ui-li-aside\" title=\"" + locale.get("places.place_creation_date", "Creation date:") + " " + coord_info.creationDate + "\">" + locale.get("places.last_update", "Last update:") + "<br>" + coord_info.modificationDate + "</p>" +
				"</a><a href=\"#gpsnavigator\" coordinate-id=\"" + coord.coord_id + "\" class=\"ui-icon-navigation\">" + locale.get("places.navigateTo", "Start navigation") + "</a></li>\n";

		// Note: the class ui-icon-navigation is used to identify this objects
	}

	function placeLiOnClick(el){

		var isEditale = $(el).parent().attr("is-editable") == "true";
		var coordId = $(el).parent().attr("coordinate-id");

		if(isEditale){
			var place = localCoordStore.get(coordId);
			if(coordId !=  null){
				showPlaceEditPopup(false, coordId, place.name, place.desc, place.lat, place.lon, place.is_public);
				return;
			}
		}
	}

	function navigateTo_OnClick(el){
		var coord = localCoordStore.get($(el).attr("coordinate-id"));
		if(coord != null){
			localCoordStore.addCoordinateToNavigation(coord);
		}
	}

	function showPlaceEditPopup(addNewPlace, coordId, name, description, latitude, longitude, isPublic){

		$(idList["popup_delete"]).css("visibility", addNewPlace ? "hidden" : "visible");

		if(addNewPlace){
			$(idList["popup_title"]).html(locale.get("places.add_place", "Add Place"));
		}
		else{
			$(idList["popup_title"]).html(locale.get("places.edit_place", "Edit Place"));
		}

		if(latitude == "" && longitude == ""){
			var gpspos = gpsNav.getNavigatorInstance().getGPSPos();
			if(gpspos != null){
				latitude = gpspos.coords.latitude;
				longitude = gpspos.coords.longitude;
			}
		}
		disableSaveButton(false);
		$(idList["field_name"]).val(name);
		$(idList["field_desc"]).val(description);
		$(idList["field_lat"]).val(latitude);
		$(idList["field_lon"]).val(longitude);
		$(idList["field_ispublic"]).prop("checked", (isPublic == true || isPublic == 1)).checkboxradio('refresh');

		$(idList["popup"]).attr("coordinate-id", coordId);
		$(idList["popup"]).attr("new-place", addNewPlace);
		$(idList["popup"]).popup("open", {positionTo: "window", transition: "none"});
	}

	function editPlacesSaveButtonClicked(){
		var newPlace = $(idList["popup"]).attr("new-place");

		var name = $(idList["field_name"]).val();
		var desc = $(idList["field_desc"]).val();
		var lat = parseFloat($(idList["field_lat"]).val());
		var lon = parseFloat($(idList["field_lon"]).val());
		var isPublic = $(idList["field_ispublic"]).is(":checked");

		if(name == "" || isNaN(lat) || isNaN(lon)){
			alert("Please enter a valid name and values for latitude and longitude.");
			return;
		}

		if(newPlace == "true"){
			uplink.sendNewCoordinate(name, desc, lat, lon, isPublic, true,
										function(msg){
											$(idList["popup"]).popup("close");
											reloadPlacesPage();
										},
										function(response){
											alert(Tools.sprintf("Unable to perform this operation. (Status {0})\\n" +
																"Server returned: {1}", [response["status"], response["msg"]]));
										});
			disableSaveButton(true);
		}
		else{
			var id = $(idList["popup"]).attr("coordinate-id");
			place = localCoordStore.get(id)

			if(place != null){
				place.name = name;
				place.desc = desc;
				place.lat = lat;
				place.lon = lon;
				place.is_public = isPublic;
				uplink.sendCoordinateUpdate(place, true,
						function(msg){
							$(idList["popup"]).popup("close");
							updateList();
						},
						function(response){
							$(idList["popup"]).popup("close");

							setTimeout(function(){
								displayError(Tools.sprintf(	"Unable to perform this operation. (Status {0})\\n" +
															"Server returned: {1}", [response["status"], response["msg"]]));
							}, 500);
						});
			}
		}

	}

	function disableSaveButton(disable){
		if(disable){
			$(idList["popup_save"]).attr("disabled", "");
		}
		else{
			$(idList["popup_save"]).removeAttr("disabled");
		}
	}

	function editPlacesDeleteButtonClicked(){
		var id = $(idList["popup"]).attr("coordinate-id");
		if(id != undefined && id >= 0){
			disableSaveButton(true);
			uplink.sendDeleteCoordinate(id, true,
						function(msg){
							$(idList["popup"]).popup("close");
							reloadPlacesPage();
						},
						function(response){
							$(idList["popup"]).popup("close");
							setTimeout(function(){
								displayError(Tools.sprintf(	"Unable to perform this operation. (Status {0})\\n" +
															"Server returned: {1}", [response["status"], response["msg"]]));
							}, 500);
						});
		}
	}
}
