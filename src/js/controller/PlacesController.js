/* GeoCat - Geocaching and -tracking application
 * Copyright (C) 2015-2016 Bastian Kraemer
 *
 * PlacesController.js
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
 * Event handling for the "Places" page
 * @class PlacesController
 */
function PlacesController(){

	var limitPerRequest = window.innerHeight / 100;
	if(limitPerRequest < 10){limitPerRequest = 10;}

	var me = this;
	var currentlyDisplayedCoordinates = new Array();
	var currentlyShowingPrivatePlaces = true;
	var localCoordStore = GeoCat.getLocalCoordStore();
	var uplink = GeoCat.getUplink();
	var scrollLoader;

	// Collection (Map) of all important HTML elements
	var buttons = {
		addPlace: "#places-add",
		showPrivate: "#places-show-private",
		showPublic: "#places-show-public",
		showSearch: "#places-find",
		searchButton: "#places-search-confirm"
	}

	var htmlElements = {
		list: "#places-list",
		searchContainer: "#places-search-container",
		searchInput: "#places-search-input"
	}

	/*
	 * ============================================================================================
	 * Public methods
	 * ============================================================================================
	 */

	/**
	 * This function should be called when the places page is opened
	 *
	 * @public
	 * @function pageOpened
	 * @memberOf PlacesController
	 * @instance
	 */
	this.pageOpened = function(){
		if(GeoCat.loginStatus.isSignedIn){
			currentlyShowingPrivatePlaces = true;
			$(buttons.addPlace).show();
			$(buttons.showPrivate).show();
			$(buttons.showPublic).show();
		}
		else{
			currentlyShowingPrivatePlaces = false;
			$(buttons.addPlace).hide();
			$(buttons.showPrivate).hide();
			$(buttons.showPublic).hide();
		}

		downloadCoordinates(currentlyShowingPrivatePlaces, null);
		highlightButton(buttons.showPrivate, currentlyShowingPrivatePlaces);
		highlightButton(buttons.showPublic, !currentlyShowingPrivatePlaces);

		GeoCat.removeCurrentChallenge();

		$(buttons.addPlace).click(function(){
			me.ignoreNextEvent();
			CoordinateEditDialogController.showDialog(
					$.mobile.activePage.attr("id"),
					null,
					function(data, editDialog){
						sendUpdateCoordinate(null, data.name, data.desc, data.lat, data.lon, data.isPublic, editDialog);
					},
					{},
					{
						noAutoClose: true,
						getCurrentPos: true
					}
				);
		});

		$(buttons.showPrivate).click(function(){
			highlightButton(buttons.showPrivate, true);
			highlightButton(buttons.showPublic, false);
			downloadCoordinates(true, null);
		});

		$(buttons.showPublic).click(function(){
			highlightButton(buttons.showPrivate, false);
			highlightButton(buttons.showPublic, true);
			downloadCoordinates(false, null);

		});

		$(buttons.showSearch).click(function(){
			$(htmlElements.searchContainer).fadeToggle('fast');
			$(htmlElements.searchInput).val("");
		});

		var startSearch = function(){downloadCoordinates(currentlyShowingPrivatePlaces, null);};

		$(buttons.searchButton).click(startSearch);
		$(htmlElements.searchInput).keydown(function (e) {
			  if (e.keyCode == 13) {
				  startSearch();
			  }
		});

		scrollLoader = new ScrollLoader(
						$(htmlElements.list)[0],
						function(successCallback){
							var callback = function(n){
								successCallback(n >= limitPerRequest);
							};

							downloadCoordinates(currentlyShowingPrivatePlaces, callback);
						}, 48);
	};

	/**
	 * This function should be called when the places page is closed
	 *
	 * @public
	 * @function pageClosed
	 * @memberOf PlacesController
	 * @instance
	 */
	this.pageClosed = function(){
		for(var key in buttons){
			$(buttons[key]).unbind();
		}
		$(htmlElements.searchInput).unbind();
		$(htmlElements.searchContainer).hide();
		$(htmlElements.searchInput).val("");
		scrollLoader.destroy();
	};

	var highlightButton = function(button, highlight){
		if(highlight){
			$(button).removeClass("substance-blue");
			$(button).addClass("substance-orange");
		}
		else{
			$(button).removeClass("substance-orange");
			$(button).addClass("substance-blue");
		}
	}

	/*
	 * ============================================================================================
	 * Download coordinates
	 * ============================================================================================
	 */

	/**
	 * Sends a <b>GET</b> or <b>GET_PUBLIC</b> command to the server
	 * @param privatePlaces {boolean} Count private or public places
	 * @param numberOfElementsCallback {function} A callback that contains the number of places that have been returned from the server as parameter
	 *
	 * @private
	 * @memberOf PlacesController
	 * @instance
	 */
	var downloadCoordinates = function(privatePlaces, numberOfElementsCallback){

		var clearList = (currentlyShowingPrivatePlaces != privatePlaces) || numberOfElementsCallback == null;
		var offset = clearList ? 0 : currentlyDisplayedCoordinates.length;

		uplink.sendGetRequest(privatePlaces, offset, limitPerRequest, $(htmlElements.searchInput).val(),
				function(response){
						var result;
						try{
							result = JSON.parse(response);
						}
						catch(e){
							SubstanceTheme.showNotification("<h3>An error occured, please try again later.</h3><p>" + e.message + "</p>", 7,
															$.mobile.activePage[0], "substance-red no-shadow white");
							return;
						}

						if(result.hasOwnProperty("status")){
							// Error
							SubstanceTheme.showNotification("<h3>Unable to download the requested information</h3><p>" + result["msg"] + "</p>", 7,
															$.mobile.activePage[0], "substance-red no-shadow white");
						}
						else{
							currentlyShowingPrivatePlaces = privatePlaces;
							onPlacesListReceived(result, privatePlaces, clearList);
							if(numberOfElementsCallback != null){
								numberOfElementsCallback(result.length);
							}
							else{
								scrollLoader.setEnable(true);
							}
						}

				});
	}

	var onPlacesListReceived = function(result, isPrivatePlaceList, clearList){
		if(clearList){
			currentlyDisplayedCoordinates.length = 0; //Clear array
		}

		var offset = currentlyDisplayedCoordinates.length;

		for(var i = 0; i < result.length; i++){
			var coord = result[i].coordinate;
			coord["is_public"] = result[i].isPublic;
			var coordInfo = new CoordinateInfo(result[i].owner, result[i].creationDate, result[i].modificationDate);
			localCoordStore.storePlace(coord, coordInfo);
			currentlyDisplayedCoordinates.push(coord.coord_id);
		}

		updateList(clearList, offset);
	}

	/*
	 * ============================================================================================
	 * Build listview
	 * ============================================================================================
	 */

	/**
	 * Updates the list on the page by using the coordinates from the {@link LocalCoordinateStore}
	 *
	 * @private
	 * @memberOf PlacesController
	 * @instance
	 */
	var updateList = function(clear, offset){
		var list = $(htmlElements.list);
		if(clear){
			list.empty();
			offset = 0;
		}

		if(currentlyDisplayedCoordinates.length > 0){
			for(var i = offset; i < currentlyDisplayedCoordinates.length; i++){
				addListItem(list, i);
			}

			list.listview('refresh');
		}
		else{
			list.append("<li><span>" + GeoCat.locale.get("places.empty_list", "No places found.") + "</span></li>");
			list.listview('refresh');
		}
	}

	var addListItem = function(listView, index){

		var coord = localCoordStore.get(currentlyDisplayedCoordinates[index]);
		var coordInfo = localCoordStore.getInfo(currentlyDisplayedCoordinates[index]);

		listView.append(generateTitleLi(coord.name, coordInfo.owner));
		listView.append(generateContentLi(coord, coordInfo));
	}

	var generateTitleLi = function(coordName, owner){
		var li = document.createElement("li");
		li.className = "place-list-item";
		li.setAttribute("data-role", "list-divider");

		var spanName = document.createElement("span");
		spanName.className = "listview-left";
		spanName.textContent = coordName;

		var spanOwner = document.createElement("span");
		spanOwner.className = "listview-right";
		spanOwner.textContent = owner;

		li.appendChild(spanName);

		if(owner == GeoCat.loginStatus.username){
			var spanX = document.createElement("span");
			spanX.className = "listview-right";
			spanX.innerHTML = "&#x2716;"
			spanX.onclick = function(){delete_OnClick(li);}
			li.appendChild(spanX);
		}

		li.appendChild(spanOwner);
		return li;
	};

	var generateContentLi = function(coord, coord_info){

		var isEditable = (coord_info.owner == GeoCat.loginStatus.username) ? "true" : "false";

		var li = document.createElement("li");
		li.className = "place-list-item";
		li.setAttribute("coordinate-id", coord.coord_id);
		li.setAttribute("is-editable", isEditable);
		var a = document.createElement("a");
		if(GeoCat.loginStatus.isSignedIn){
			a.className = "li-clickable";
			if(GeoCat.loginStatus.username == coord_info.owner){
				a.onclick = function(){place_OnClick(a, coord.is_public);};
			}
			else{
				a.onclick = function(){
					SubstanceTheme.showNotification(
						"<p>" + sprintf(GeoCat.locale.get("places.not_authorized", "You cannot edit place '{0}' of {1}"), [coord.name, coord_info.owner]) + "</p>",
						7, $.mobile.activePage[0], "substance-red no-shadow white");
				};
			}
		}

		if(coord.desc != null){
			var h = document.createElement("h2");
			h.textContent = coord.desc;
			a.appendChild(h);
		}

		var p = document.createElement("p");
		p.innerHTML = "<strong>Coordinates: </strong>" + coord.lat + ", " + coord.lon;

		var dateInfo = document.createElement("p");
		dateInfo.className = "ui-li-aside";
		dateInfo.setAttribute("title", GeoCat.locale.get("places.last_update", "Last update:") + " " + coord_info.modificationDate);
		dateInfo.textContent = coord_info.modificationDate.split(" ")[0];

		var navTo = document.createElement("a");
		navTo.onclick = function(){navigateTo_OnClick(this);};
		navTo.href = "#GPSNavigator";
		navTo.setAttribute("coordinate-id", coord.coord_id);
		navTo.className = "ui-icon-navigation";
		navTo.textContent = GeoCat.locale.get("places.navigateTo", "Start navigation")

		a.appendChild(p);
		a.appendChild(dateInfo);

		li.appendChild(a);
		li.appendChild(navTo);

		return li;
	}

	var uplinkErrorHander = function(response){
		SubstanceTheme.showNotification("<h3>Unable to perform operation.</h3><p>" + response["msg"] + "</p>", 7,
										$.mobile.activePage[0], "substance-red no-shadow white");
	}

	/*
	 * ============================================================================================
	 * "OnClick" functions
	 * ============================================================================================
	 */

	var place_OnClick = function(el, isPublic){

		var isEditale = $(el).parent().attr("is-editable") == "true";
		var coordId = $(el).parent().attr("coordinate-id");

		var editData = localCoordStore.get(coordId);
		if(coordId !=  null){

			me.ignoreNextEvent();
			CoordinateEditDialogController.showDialog(
				$.mobile.activePage.attr("id"),
				null,
				function(data, editDialog){
					sendUpdateCoordinate(coordId, data.name, data.desc, data.lat, data.lon, data.isPublic, editDialog, el);
				},
				editData,
				{
					noAutoClose: true,
					getCurrentPos: false
				}
			);
		}
		if(!isEditale){
			disableSaveButton(true);
		}
	}

	var navigateTo_OnClick = function(el){
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

	var delete_OnClick = function(el){
		var element = $(el).next();
		sendDelete(element.attr("coordinate-id"), element);
	}

	/*
	 * ============================================================================================
	 * "AJAX" functions
	 * ============================================================================================
	 */

	var sendUpdateCoordinate = function(id, name, desc, lat, lon, isPublic, editDialog, element){
		// Update the database

		if(id == null){
			uplink.sendNewCoordinate(
				name, desc, lat, lon, isPublic,
				function(result){
					editDialog.close();
					var coord = new Coordinate(result.coord_id, name, lat, lon, desc, isPublic);
					var now = new Date().toISOString().replace("T", " ").replace("Z", "");
					var coordInfo = new CoordinateInfo(GeoCat.loginStatus.username, now, now);
					localCoordStore.storePlace(coord, coordInfo);
					currentlyDisplayedCoordinates.push(result.coord_id);
					addListItem($(htmlElements.list), currentlyDisplayedCoordinates.length - 1);
					$(htmlElements.list).listview('refresh');
				},
				function(response){
					editDialog.hideWaitScreen();
					SubstanceTheme.showNotification("<p>" + response["msg"]+ "</p>", 7,	$.mobile.activePage[0], "substance-red no-shadow white");
				});
		}
		else{
			coord = localCoordStore.get(id)

			if(coord != null){
				coord.name = name;
				coord.desc = desc;
				coord.lat = lat;
				coord.lon = lon;
				coord.is_public = isPublic;
				uplink.sendCoordinateUpdate(coord,
						function(result){
							editDialog.close();

							var list = $(htmlElements.list);
							var info = localCoordStore.getInfo(id);
							localCoordStore.storeCoordinate(coord);

							var li1 = generateTitleLi(name, info.owner);
							var li2 = generateContentLi(coord, info);

							$(element).parent().prev().replaceWith(li1);
							$(element).parent().replaceWith(li2);

							list.listview('refresh');
						},
						function(){
							editDialog.hideWaitScreen();
							uplinkErrorHander();
						});
			}
		}
	}

	var sendDelete = function(id, element){
		if(id != undefined && id >= 0){
			uplink.sendDeleteCoordinate(id,
						function(result){
							if(result.status.toLowerCase() == "ok"){
								var prevEl = element.prev()
								prevEl.slideUp('slow', function(){prevEl.remove();});
								element.slideUp('slow', function(){element.remove();});

								for(var i = 0; i < currentlyDisplayedCoordinates.length; i++){
									if(currentlyDisplayedCoordinates[i] == id){
										currentlyDisplayedCoordinates.splice(i, 1);
										if(currentlyDisplayedCoordinates.length < limitPerRequest){
											 downloadCoordinates(currentlyShowingPrivatePlaces, function(n){});
										}
										return;
									}
								}

							}
						},
						uplinkErrorHander);
		}
	}
}

PlacesController.init = function(myPageId){
	PlacesController.prototype = new PagePrototype(myPageId, function(){
		return new PlacesController();
	});
};
