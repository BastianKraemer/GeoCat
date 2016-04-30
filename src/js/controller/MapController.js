/* GeoCat - Geocaching and -tracking application
 * Copyright (C) 2016 Bastian Kraemer
 *
 * MapController.js
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
 * Controller for the GeoCat map page
 *
 * Example of the callback object for task 'GET_POSITION'
 * {callback: function(){...}, lat: null, lon: null, returnTo: "#pageid"}
 * Note: The coordinates for lat and lon are displayed on the map (maybe the user want to edit the coorindates?)
 *
 * taskParam object examples:
 *
 * {returnTo: "#PageId"}
 * {returnTo: "#PageId", coords: [{lat: ???, lon: ???}]}
 * {coords: [{lat: ???, lon: ???},{lat: ???, lon: ???}]}
 *
 * @class MapController
 *
 * @param mapTask {MapController.MapTask} Task for this map controller instance
 * @param taskParam {Object} The coordinates that will be displayed (on task "SHOW_COORDS") or the callback for the selected coordinates (on task "GET_POSITON"), otherwise this parameter can be null
 */
function MapController(mapTask, taskParam){

	var mapId = "openlayers-map";
	var htmlElements = {
			map: "#" + mapId
	}

	var map;
	var olPointVector = null;

	var coordSelectInProgressFlag = false;
	var coordSelectionTimeout = null;
	var startupCancelFlag = false;

	/**
	 * This function should be called when the page is opened
	 *
	 * @public
	 * @function pageOpened
	 * @memberOf MapController
	 * @instance
	 */
	this.pageOpened = function(){
		$("#openlayers-map").html("<p class=\"center\" style=\"margin-top: 40px\">" + GeoCat.locale.get("map.loading", "Loading map") + "...</p>");

		var notEmpty = function(val){
			if(val == null){return false;}
			return val != "";
		}
		var startOL = function(){
			if(!startupCancelFlag){ // Verify that the user has not already left the page
				$("#openlayers-map").html("");
				if(mapTask == MapController.MapTask.GET_POSITION && notEmpty(taskParam.coords[0].lat) && notEmpty(taskParam.coords[0].lon)){
					startOpenLayers(taskParam.coords[0].lat, taskParam.coords[0].lon, 16);
				}
				else if(mapTask == MapController.MapTask.SHOW_COORDS && taskParam.coords.length > 0){
					startOpenLayers(taskParam.coords[0].lat, taskParam.coords[0].lon, 14);
				}
				else{
					startOpenLayers(MapController.initialPosition.lat, MapController.initialPosition.lon, MapController.initialPosition.zoom);
				}
				startup();
			}
		}

		var homeButton = $("#Map div a");
		var loginButton = $("#Map div button");

		$(homeButton).unbind();
		if(taskParam != null && taskParam.hasOwnProperty("returnTo")){
			$(homeButton)[0].href = "";
			if(MapController.regularHomeButtonText == null){
				MapController.regularHomeButtonText = $(homeButton).text();
			}
			$(homeButton).text(GeoCat.locale.get("cancel", "Cancel"));
			$(homeButton).removeClass("ui-icon-home").addClass("ui-icon-arrow-l");
			$(homeButton).click(function(){$.mobile.changePage(taskParam.returnTo);});
			loginButton.hide();
		}
		else{
			$(homeButton).removeClass("ui-icon-arrow-l").addClass("ui-icon-home");
			if(MapController.regularHomeButtonText != null){
				$(homeButton).text(MapController.regularHomeButtonText);
			}
			$(homeButton)[0].href = "#Home";
			loginButton.show();
		}

		// Download the OpenLayers JavaScript library
		if(!MapController.openLayerLibraryLoaded){
			$.ajax({
				type: "GET", url: "./lib/ol.js",
				cache: true,
				success: function(response){
					MapController.openLayerLibraryLoaded = true;
					eval(response);
					setTimeout(startOL, 200); // Without this timeout, the map is sometimes higher than the browser window
				},
				error: function(xhr, status, error){
					GeoCat.displayError();
				}
			});
		}
		else{
			setTimeout(startOL, 200);
		}
	};

	/**
	 * This function should be called when the page is closed
	 *
	 * @public
	 * @function pageClosed
	 * @memberOf MapController
	 * @instance
	 */
	this.pageClosed = function(){
		window.removeEventListener('resize', updateMapSize);

		startupCancelFlag = true;
		if(MapController.openLayerLibraryLoaded){
			if(olPointVector != null){
				clearMap();
				map.setTarget(null);
			}

			// Run this later, to avoid conflicts with OpenLayers if the page is left immediately after opening
			setTimeout(function(){
			    map = null;
			    olPointVector = null;
			}, 200);
		}

		if(coordSelectInProgressFlag){
			clearTimeout(coordSelectionTimeout);
		}
	};

	/**
	 * Update the displayed coordinates
	 * @param coords {Object} Array of coordinates (for examples: [{name: ???, lat: ???, lon: ???}, {name: ???, lat: ???, lon: ???}]
	 * @public
	 * @function updateCoords
	 * @memberOf MapController
	 * @instance
	 */
	this.updateCoords = function(coords){
		if(olPointVector != null){
			displayCoordinates(coords, "#c82323", true, function(c){return c;});
		}
		else{
			SubstanceTheme.showNotification("<p>" + GeoCat.locale.get("map.notready", "Preparing map. Please wait...") + "</p>", 10, $.mobile.activePage[0], "substance-skyblue no-shadow white");
		}
	};

	var startOpenLayers = function(centerLat, centerLon, initialZoom){
		olPointVector = new ol.source.Vector({
			features: []
		});

		var vectorLayer = new ol.layer.Vector({
			source: olPointVector
		});

		map = new ol.Map({
			layers: [
				new ol.layer.Tile({
					source: new ol.source.OSM()
				}),
				vectorLayer
			],
			target: mapId,
			controls: ol.control.defaults({
				attributionOptions: ({
					collapsible: false
				})
			}),
			view: new ol.View({
				projection: 'EPSG:3857',
				center: ol.proj.transform([parseFloat(centerLon), parseFloat(centerLat)], 'EPSG:4326', 'EPSG:3857'),
				zoom: initialZoom
			})
		});

		updateMapSize();
		window.addEventListener('resize', updateMapSize);

		map.on('click', function(event) {
			if(mapTask == MapController.MapTask.GET_POSITION){
				verifySelectedCoodinates(ol.proj.transform(event.coordinate, 'EPSG:3857', 'EPSG:4326'));
			}
			else{
				var feature = map.forEachFeatureAtPixel(event.pixel, function(feature, layer){return feature;});

				if(feature){
					var coord = feature.getGeometry().getCoordinates();
					coord = ol.proj.transform(coord, 'EPSG:3857', 'EPSG:4326');
					var desc = feature.get("coord_desc");
					SubstanceTheme.showNotification("<h3 style='margin: 8px 0 4px 0'>" + feature.get("coord_name") + "</h3>" +
													(desc != null? "<p style='margin: 4px'>" + feature.get("coord_desc") + "</p>" : "") +
													"<p style='margin: 2px; font-size: 10px'><i>" + coord[1].toFixed(6) + ", " + coord[0].toFixed(6) + "</i></p>", 7, $.mobile.activePage[0], "substance-blue no-shadow white");
				}
			}
		});
	};

	var startup = function(){
		switch(mapTask){
			case MapController.MapTask.SHOW_ALL:
				downloadPlaces(false, true);
				downloadPlaces(true, false);
				break;
			case MapController.MapTask.SHOW_PUBLIC:
				downloadPlaces(false, true);
				break;
			case MapController.MapTask.SHOW_PRIVATE:
				downloadPlaces(true, true);
				break;
			case MapController.MapTask.SHOW_COORDS:
				displayCoordinates(taskParam.coords, "#c82323", true, function(c){return c;});
				break;
			case MapController.MapTask.GET_POSITION:
				if(taskParam.lat != null && taskParam.lon != null){
					display([createPoint(new Coordinate(null, taskParam.coords[0].lat + ", " + taskParam.coords[0].lon, taskParam.coords[0].lon, taskParam.coords[0].lat, "", false), "#0c5c76")]);
				}
				break;
		}
	};

	var updateMapSize = function(){
		var mapHeight = window.innerHeight - $("#" + $.mobile.activePage[0].id + " div:first-Child")[0].offsetHeight;
		$(htmlElements.map).css("height", mapHeight + "px");
		setTimeout(function(){map.updateSize();}, 200);
	};

	var createPoint = function(coord, coordColor){
		var p = new ol.Feature({
			geometry: new ol.geom.Point(ol.proj.fromLonLat([parseFloat(coord.lon), parseFloat(coord.lat)])),
			coord_name: coord.name.toString(),
			coord_desc: coord.desc
		});

		p.setStyle(new ol.style.Style({
			image: new ol.style.Circle({
				radius: 7,
				fill: new ol.style.Fill({
					color: coordColor
				})
			}),
			text: new ol.style.Text({
				text: coord.name,
				offsetY: 15,
				fill: new ol.style.Fill({color: coordColor}),
			 })
		}));

		return p;
	};

	var display = function(pointArr){
		olPointVector.addFeatures(pointArr);
	};

	var clearMap = function(){
		olPointVector.clear();
	};

	var displayCurrentNavigation = function(){
		var navList = GeoCat.getLocalCoordStore().getCurrentNavigation();

		var points = new Array();
		for(var key in navList){
			points.push(createPoint(navList[key], "blue"));
		}

		display(points);
	};

	var downloadPlaces = function(privatePlaces, clear){
		GeoCat.getUplink().sendGetRequest(privatePlaces, 0, 100, null,
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
							displayCoordinates(result, privatePlaces ? "#ff7700" : "#00aeff", clear, function(c){return c.coordinate;});
						}
				});
	};

	var displayCoordinates = function(coords, color, clear, getCoordCallback){
		if(clear){clearMap();}

		var points = new Array();
		for(var i = 0; i < coords.length; i++){
			points.push(createPoint(getCoordCallback(coords[i]), color));
		}

		display(points);
	};

	var verifySelectedCoodinates = function(coords){
		if(!coordSelectInProgressFlag){
			coordSelectInProgressFlag = true;
		}
		else{
			clearTimeout(coordSelectionTimeout);
		}

		clearMap();
		coords[0] = coords[0].toFixed(6);
		coords[1] = coords[1].toFixed(6);
		display([createPoint(new Coordinate(null, coords[1] + ", " + coords[0], coords[1], coords[0], "", false), "#0c5c76")]);

		coordSelectionTimeout = setTimeout(function(){
			SubstanceTheme.showYesNoDialog(
				"<h3 style='margin-bottom: 4px; font-weight: 500;'>" + GeoCat.locale.get("map.select.confirm", "Do you want to continue with the selected coordinate?") +  "</h3>" +
				"<p style='margin-top: 0; color: #646464'>" + coords[1] + ", " + coords[0] + "</p>", $.mobile.activePage[0],
				function(){
					MapController.initialPosition = {lat: coords[1], lon: coords[0], zoom: 16}
					taskParam.callback(coords[1],coords[0]);
					$.mobile.changePage(taskParam.returnTo);},
				null,
				"substance-white no-shadow", true);
			coordSelectInProgressFlag = false;
		}, 1000);
	}
}

MapController.openLayerLibraryLoaded = false;
MapController.initialPosition = {lat: 50.000, lon: 8.000, zoom: 5};
MapController.regularHomeButtonText = null;

MapController.init = function(myPageId){
	var myPrototype = new PagePrototype(myPageId, function(){
		return new MapController(GeoCat.loginStatus.isSignedIn ? MapController.MapTask.SHOW_ALL : MapController.MapTask.SHOW_PUBLIC, null);
	});

	MapController.prototype = myPrototype;

	MapController.prepareMap = function(mapTask, taskParam){
		var mapController = new MapController(mapTask, taskParam);
		myPrototype.setInstance(mapController);
		myPrototype.ignoreNextEvent();
		return mapController;
	};

	MapController.showPreparedMap = function(mapController){
		$.mobile.changePage(myPageId);
		mapController.pageOpened();
	};

	MapController.showMap = function(mapTask, taskParam){
		var controller = MapController.prepareMap(mapTask, taskParam);
		MapController.showPreparedMap(controller);
		return controller;
	};
};

MapController.MapTask = {
	SHOW_PUBLIC: 0,
	SHOW_PRIVATE: 1,
	SHOW_ALL: 2,
	SHOW_COORDS: 3,
	GET_POSITION : 4
};
