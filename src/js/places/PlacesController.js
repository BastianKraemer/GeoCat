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

	this.onPageOpened = function(){

		requestMyPages();
		$("#Places_ShowMyPlaces").click(function(){
			requestMyPages();
		});
		$("#Places_ShowPublicPlaces").click(function(){
			requestPublicPages();
		});

		$("#Places_Next").click(function(){
			if(currentPage < maxPages - 1){
				requestPlaces(++currentPage, currentlyShowingPrivatePlaces);
			}
		});

		$("#Places_Prev").click(function(){
			if(currentPage > 0){
				requestPlaces(--currentPage, currentlyShowingPrivatePlaces);
			}
		});
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
		$("#PlacesInformation").html(Tools.sprintf(locale.get("page_of", "Page {0} of {1}"), [(currentPage + 1), maxPages]) + " " +
									 Tools.sprintf(locale.get("places_count", "(Total number: {0})"), [allPlacesCount]));
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
		updatePageInfo();
	}

	function generatePlaceItemCode(place, number){

		return 	"<li class=\"place-list-item\" data-role=\"list-divider\">" +
					"<span class=\"place-name\">#" + number + " " + place.coordinate.name + "</span>" +
					"<span class=\"place-owner\">" + place.owner + "</span></li>" +
				"<li><a>" +
					(place.coordinate.desc != null ? "<h2>"+ place.coordinate.desc + "</h2>" : "") +
					"<p><strong>Coordinates: </strong>" + place.coordinate.lat + ", " + place.coordinate.lon + "</p>" +
					"<p class=\"ui-li-aside\" title=\"Erstellt am " + place.creationDate + "\">Letze Aktualisierung:<br>" + place.modificationDate + "</p>" +
				"</a></li>\n";
	}
}