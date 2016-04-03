/*	GeoCat - Geolocation caching and tracking platform
	Copyright (C) 2015-2016 Bastian Kraemer

	GPSNavigationController.js

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
 * Event handling for the "GPS Navigtor" page
 * @class GPSNavigationController
 */
function GPSNavigationController(){

	// Private variables
	var localCoordStore = GeoCat.getLocalCoordStore();
	var uplink = GeoCat.getUplink();
	var gpsRadar = null;
	var updateTimer = null; // Interval to update the gps Radar
	var me = this;

	// Collection (Map) of all important HTML elements (defeined by their id)
	var htmlElements = {
		contentDiv: "#gpsnav-content",
		canvas: "#gpsnav-canvas",
		coordinateList: "#gpsnav-destination-list",
		coordinatePanel: "#gpsnav-destination-list-panel",
		addCoordButton: "#gpsnavigagtor-add-place",
		showMapButton: "#gpsnavigagtor-show-map"
	}

	/*
	 * ============================================================================================
	 * Public methods
	 * ============================================================================================
	 */

	/**
	 * This function should be called when the "GPS navigator" page is opened
	 *
	 * @public
	 * @function pageOpened
	 * @memberOf GPSNavigationController
	 * @instance
	 */
	this.pageOpened = function(){

		// Download the latest navigation list from the server
		downloadNavListFromServer();

		$(htmlElements.coordinatePanel).on("panelbeforeopen", function(){
			updateCurrentDestinationList();
		});

		$(htmlElements.addCoordButton).click(function(e){
			var lastGPSPos = GPS.get();
			if(lastGPSPos != null){
				showCoordinateEditDialog(null, "", "", lastGPSPos.coords.latitude, lastGPSPos.coords.longitude, true);
			}
			else{
				SubstanceTheme.showNotification("<p>" + GeoCat.locale.get("gpsnav.no_gps_fix", "Unable to get current GPS position") + ".</p>", 7,
						$.mobile.activePage[0], "substance-skyblue no-shadow white");
			}
		});

		$(htmlElements.showMapButton).click(function(e){
			var currentNav = localCoordStore.getCurrentNavigation();
			var coordList = new Array(Object.keys(currentNav).length);
			var i = 0;
			for(var key in currentNav){
				coordList[i++] = currentNav[key];
			}
			MapController.showMap(MapController.MapTask.SHOW_COORDS, coordList);
		});

		gpsRadar = new GPSRadar($(htmlElements.contentDiv)[0], $(htmlElements.canvas)[0]);
		gpsRadar.start();
		startTimer();
	};

	/**
	 * This function should be called when the "GPS Navigator" page is closed
	 *
	 * @public
	 * @function pageClosed
	 * @memberOf GPSNavigationController
	 * @instance
	 */
	this.pageClosed = function(){
		stopTimer();
		gpsRadar.stop();

		// Remove all event handler
		$(htmlElements.coordinatePanel).off();
		$(htmlElements.addCoordButton).unbind();
		$(htmlElements.showMapButton).unbind();
	};

	function startTimer(){
		if(updateTimer == null){
			updateTimer = setInterval(function(){
				gpsRadar.update(localCoordStore.getCurrentNavigation(), {}, {});
			}, 2000);
		}
	}

	function stopTimer(){
		if(updateTimer != null){
			clearInterval(updateTimer);
			updateTimer = null;
		}
	}

	/*
	 * ============================================================================================
	 * Private methods
	 * ============================================================================================
	 */

	/**
	 * Downloads the latest navigation list from the server
	 *
	 * @private
	 * @function
	 * @memberOf GPSNavigationController
	 * @instance
	 */
	function downloadNavListFromServer(){
		if(GeoCat.loginStatus.isSignedIn){
			uplink.sendNavList_Get(
						function(response){
							var result = JSON.parse(response);
							for(var i = 0; i < result.length; i++){
								result[i].is_public = false;
								localCoordStore.addCoordinateToNavigation(result[i]);
							}
						},
						uplinkOnError);
		}
	}

	/**
	 * Appends a coordinate to the current naviagtion list.<br />
	 * This method will send a request to the server too.
	 * @param coord {Coordinate} The coordinate
	 * @param coordinateAlreadyExistsOnServer {Boolean} Specify if the coordinate is already stored on the server
	 *
	 * @private
	 * @function
	 * @memberOf GPSNavigationController
	 * @instance
	 */
	function addCoordToNavList(coord, coordinateAlreadyExistsOnServer){

		if(coordinateAlreadyExistsOnServer){
			uplink.sendNavList_Add(coord.coord_id,
					function(result){
						localCoordStore.addCoordinateToNavigation(coord);
					},
					uplinkOnError);
		}
		else{
			uplink.sendNavList_Create(coord.name, coord.desc, coord.lat, coord.lon,
					function(result){
						coord.coord_id = result.coord_id; //Change the coord_id to the id that has been returned from the server
						localCoordStore.addCoordinateToNavigation(coord);
					},
					uplinkOnError);
		}
	}

	function uplinkOnError(response){
		SubstanceTheme.showNotification(GuiToolkit.sprintf(	"Unable to perform this operation. (Status {0})<br>" +
															"Server returned: {1}", [response["status"], response["msg"]]), 10,
															$.mobile.activePage[0], "substance-red no-shadow white");
	}

	/**
	 * Shows the edit dialog
	 * @param id {integer} Identifier of the coordinate
	 * @param name {String} Name of the coordinate
	 * @param description {String} Description of the coordinate
	 * @param latitude {Double} Latitude of the coordinate
	 * @param longitude {Double} Longitude of the coordinate
	 * @param showAdd2OwnPlaces {Boolean} Show option "Add to own places"?
	 *
	 * @private
	 * @function
	 * @memberOf GPSNavigationController
	 * @instance
	 */
	function showCoordinateEditDialog(id, name, description, latitude, longitude, showAdd2OwnPlaces){

		me.ignoreNextEvent();

		CoordinateEditDialogController.showDialog(
			$.mobile.activePage.attr("id"),
			null,
			function(data, editDialog){
				sendCoordUpdate(id, data.name, data.lat, data.lon, data.add2ownplaces)
			},
			{
				name: name,
				dest: description,
				lat: latitude,
				lon: longitude,
				isPublic: false
			},
			{
				hideIsPublicField: true,
				showAddToOwnPlaces: showAdd2OwnPlaces,
				hideDescriptionField: true,
				getCurrentPos: false
			}
		);
	}

	/**
	 * Updates the current destination list based on tha values stored in the {@link LocalCoordinateStore}
	 *
	 * @private
	 * @function
	 * @memberOf GPSNavigationController
	 * @instance
	 */
	function updateCurrentDestinationList(){
		var destList = $(htmlElements.coordinateList);
		destList.empty()

		var list = localCoordStore.getCurrentNavigation();
		for(var key in list){
			destList.append("<li dest-id=\"" + key + "\">" +
							"<a>" + list[key].name + "</a>" +
							"<a class=\"ui-icon-delete\">Remove</a>" +
							"</li>");
		}

		$(htmlElements.coordinateList + " li a:first-child").click(function(){coordinateListItem_OnClick(this);});
		$(htmlElements.coordinateList + " li a.ui-icon-delete").click(function(){deleteListItem_OnClick(this);});

		destList.listview('refresh');
	}

	/*
	 * ============================================================================================
	 * "OnClick" functions
	 * ============================================================================================
	 */

	function coordinateListItem_OnClick(element){
		var dest = localCoordStore.get($(element).parent().attr("dest-id"));
		showCoordinateEditDialog(dest.coord_id, dest.name, dest.desc, dest.lat, dest.lon, false);
	}

	function deleteListItem_OnClick(element){
		var key = $(element).parent().attr("dest-id");
		if(GeoCat.loginStatus.isSignedIn){
			uplink.sendNavList_Remove(key,
						function(response){
							localCoordStore.removeFromNavigationById(key);
							updateCurrentDestinationList();
						},
						uplinkOnError);
		}
		else{
			localCoordStore.removeFromNavigationById(key);
			updateCurrentDestinationList();
		}
	}

	/*
	 * ============================================================================================
	 * "Uplink" handler
	 * ============================================================================================
	 */

	function sendCoordUpdate(id, name, lat, lon, add2OwnPlaces){
		if(GeoCat.loginStatus.isSignedIn){
			if(id == undefined){
				// It is a new place

				if(add2OwnPlaces){
					// This place will be added to your own places
					uplink.sendNewCoordinate(name, "", lat, lon, 0,
							function(result){
								var coord = new Coordinate(result["coord_id"], name, lat, lon, "", false)
								addCoordToNavList(coord, true);
							},
							uplinkOnError);
				}
				else{
					// This place has to be added to the navigation list only
					addCoordToNavList(new Coordinate.create(name, lat, lon, ""), false);
				}
			}
			else{
				if(id > 0){
					var coord = localCoordStore.get(id);
					coord.name = name;
					coord.lat = lat;
					coord.lon = lon;

					uplink.sendCoordinateUpdate(coord,
							function(result){
								localCoordStore.storePlace(coord);

								// Update label text
								$(htmlElements.coordinateList + " li[dest-id=" + id + "] a[href='#']").text(name);
							},
							uplinkOnError);
				}
				else{
					// Only store this coordinate local
					localCoordStore.storePlace(coord);
				}
			}
		}
		else{
			// The user is not signed in - just add the coordinate to the navigation
			localCoordStore.addCoordinateToNavigation(new Coordinate.create(name, lat, lon, ""));
		}
	}
}

GPSNavigationController.init = function(myPageId){
	GPSNavigationController.prototype = new PagePrototype(myPageId, function(){
		return new GPSNavigationController();
	});
};
