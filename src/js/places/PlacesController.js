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

var PlacesController = new function(){

	var placesPerPage = 10;
	var currentPlaceList = null;
	var currentPage = 0;
	var allPlacesCount = 0;
	var maxPages = 0;
	var currentlyShowingPrivatePlaces = true;

	var idList = new Object();
	idList["popup"] = "#EditPlacePopup";
	idList["popup_title"] = "#EditPlacePopup_Title";
	idList["popup_save"] = "#EditPlacePopup_Save";
	idList["popup_delete"] = "#EditPlacePopup_Delete";
	idList["field_name"] = "#EditPlacePopup_Name";
	idList["field_lat"] = "#EditPlacePopup_Lat";
	idList["field_lon"] = "#EditPlacePopup_Lon";
	idList["field_desc"] = "#EditPlacePopup_Desc";
	idList["field_ispublic"] = "#EditPlacePopup_Public";
	idList["show_private_places"] = "#Places_ShowMyPlaces";
	idList["show_public_places"] = "#Places_ShowPublicPlaces";
	idList["prev_page"] = "#Places_Next";
	idList["next_page"] = "#Places_Prev";
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
	}

	this.onPageClose = function(){
		$(idList["show_private_places"]).unbind();
		$(idList["show_public_places"]).unbind();
		$(idList["next_page"]).unbind();
		$(idList["prev_page"]).unbind();
		$(idList["new_place"]).unbind();
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
						currentPlaceList = result;
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

		for(var i = 0; i < currentPlaceList.length; i++){
			list.append(generatePlaceItemCode(currentPlaceList[i], (currentPage * placesPerPage) + i + 1));
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

	function generatePlaceItemCode(place, number){

		return 	"<li class=\"place-list-item\" data-role=\"list-divider\" coordinate-id=\"" + place.coordinate.coord_id + "\" is-editable=\"true\">" +
					"<span class=\"place-name\">#" + number + " " + place.coordinate.name + "</span>" +
					"<span class=\"place-owner\">" + place.owner + "</span></li>" +
				"<li class=\"place-list-item\" coordinate-id=\"" + place.coordinate.coord_id + "\" is-editable=\"true\"><a class=\"li-clickable\">" +
					(place.coordinate.desc != null ? "<h2>"+ place.coordinate.desc + "</h2>" : "") +
					"<p><strong>Coordinates: </strong>" + place.coordinate.lat + ", " + place.coordinate.lon + "</p>" +
					"<p class=\"ui-li-aside\" title=\"" + locale.get("places.place_creation_date", "Creation date:") + " " + place.creationDate + "\">" + locale.get("places.last_update", "Last update:") + "<br>" + place.modificationDate + "</p>" +
				"</a><a href=\"#gpsnavigator\" coordinate-id=\"" + place.coordinate.coord_id + "\" class=\"ui-icon-navigation\">" + locale.get("places.navigateTo", "Start navigation") + "</a></li>\n";

		// Note: the class ui-icon-navigation is used to identify this objects
	}

	function placeLiOnClick(el){

		var isEditale = $(el).parent().attr("is-editable") == "true";
		var coordId = $(el).parent().attr("coordinate-id");

		var place = getPlaceFromCurrentListById(coordId);
		if(coordId !=  null){
			showPlaceEditPopup(false, coordId, place.coordinate.name, place.coordinate.desc, place.coordinate.lat, place.coordinate.lon, place.isPublic);
			return;
		}
	}

	function getPlaceFromCurrentListById(id){
		for(var i = 0; i < currentPlaceList.length; i++){
			if(currentPlaceList[i].coordinate.coord_id == id){
				return currentPlaceList[i];
			}
		}

		return null;
	}

	function navigateTo_OnClick(el){
		var place = getPlaceFromCurrentListById($(el).attr("coordinate-id"));
		if(place != null){
			var nav = GPSNavigationController.getInstance();
			nav.addDestination(place.coordinate.coord_id, new Coordinate(place.coordinate.name, place.coordinate.lat, place.coordinate.lon, place.coordinate.desc));
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

		$(idList["field_name"]).val(name);
		$(idList["field_desc"]).val(description);
		$(idList["field_lat"]).val(latitude);
		$(idList["field_lon"]).val(longitude);
		$(idList["field_ispublic"]).prop("checked", isPublic);
		$(idList["popup"]).attr("coordinate-id", coordId);
		$(idList["popup"]).attr("new-place", addNewPlace);
		$(idList["popup"]).popup("open", {positionTo: "window", transition: "none"});
	}

	this.editPlacesSaveButtonClicked = function(){
		var newPlace = $(idList["popup"]).attr("new-place");

		if(newPlace == "true"){

			var name = $(idList["field_name"]).val();
			var desc = $(idList["field_desc"]).val();
			var lat = $(idList["field_lat"]).val();
			var lon = $(idList["field_lon"]).val();
			var isPublic =  $(idList["field_ispublic"]).val();

			if(name != null && lat != null && lon != null){
				sendNewPlace(name, desc, lat, lon, isPublic);
			}
		}
		else{
			var id = $(idList["popup"]).attr("coordinate-id");
			place = getPlaceFromCurrentListById(id)

			if(place != null){
				place.coordinate.name = $(idList["field_name"]).val();
				place.coordinate.desc = $(idList["field_desc"]).val();
				place.coordinate.lat = $(idList["field_lat"]).val();
				place.coordinate.lon = $(idList["field_lon"]).val();
				place.isPublic =  $(idList["field_ispublic"]).val();
				sendUpdate(place);
			}
		}

	}

	function sendNewPlace(placeName, placeDesc, placeLat, placeLon, placeIsPublic){
		var url = "./query/places.php";

		$.ajax({type: "POST", url: url,
			data: {	cmd: "add",
					name: placeName,
					desc: placeDesc,
					lat: placeLat,
					lon: placeLon,
					is_public: placeIsPublic
			},
			cache: false,
			success: function(response){
				var result = JSON.parse(response);
				if(result.status = "ok"){
					reloadPlacesPage();
					$(idList["popup"]).popup("close");
				}
				else{
					Tools.showPopup("Error", result.msg, "OK", null);
				}

			},
			error: function(xhr, status, error){
				displayError("AJAX request failed: " + error);
			}
		});
	}

	function sendUpdate(place){
		var url = "./query/places.php";

		var data = place.coordinate;
		data["is_public"] = place.isPublic;

		$.ajax({type: "POST", url: url,
			data:{ cmd: "update", data_type: "json", data: JSON.stringify(data)},
			cache: false,
			success: function(response){
				var result = JSON.parse(response);
				if(result.status == "ok"){
					updateList();
				}
				else{
					Tools.showPopup("Error", result.msg, "OK", null);
				}
			},
			error: function(xhr, status, error){
				displayError("AJAX request failed: " + error);
			}
		});
		$(idList["popup"]).popup("close");
	}


	this.editPlacesDeleteButtonClicked = function(){
		var id = $(idList["popup"]).attr("coordinate-id");
		if(id != undefined && id >= 0){
			sendDelete(id);
		}
	}

	function sendDelete(coordId){
		var url = "./query/places.php";
		$.ajax({type: "POST", url: url,
			data:{ cmd: "remove", coord_id: coordId},
			cache: false,
			success: function(response){
				var result = JSON.parse(response);
				if(result.status = "ok"){
					reloadPlacesPage();
				}
				else{
					Tools.showPopup("Error", result.msg, "OK", null);
				}
			},
			error: function(xhr, status, error){
				displayError("AJAX request failed: " + error);
			}
		});
		$(idList["popup"]).popup("close");
	}

	this.closeEditPlacePopup = function(){
		$(idList["popup"]).popup("close");
	}
}();
