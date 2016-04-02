/*	GeoCat - Geolocation caching and tracking platform
	Copyright (C) 2016 Bastian Kraemer

	MapController.js

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
 * Controller for the GeoCat map page
 * @class MapController
 *
 * @param mapTask {MapController.MapTask} Task for this map controlelr instance
 * @param mapCoords {Coordinate[]} The coordinates that will be displayed when the cotroller is used in "SHOW_COORDS" mode, otherwise this parameter can be null
 */
function MapController(mapTask, mapCoords){

	var mapId = "openlayers-map";
	var htmlElements = {
			map: "#" + mapId
	}

	var map;
	var olPointVector;

	/**
	 * This function should be called when the page is opened
	 *
	 * @public
	 * @function pageOpened
	 * @memberOf MapController
	 * @instance
	 */
	this.pageOpened = function(){

		// Download the OpenLayers JavaScript library
		if(!MapController.openLayerLibraryLoaded){
			$.ajax({
				type: "GET", url: "./lib/ol.js",
				cache: true,
				success: function(response){
					MapController.openLayerLibraryLoaded = true;
					eval(response);
					startOpenLayers();
					startup(mapTask, mapCoords);
				},
				error: function(xhr, status, error){
					alert("AJAX ERROR");
				}
			});
		}
		else{
			setTimeout(function(){
				startOpenLayers();
				startup(mapTask, mapCoords);
			}, 200);
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
		window.onresize = null;
		if(MapController.openLayerLibraryLoaded){
			clearMap();
			map.setTarget(null);
		    map = null;
		    olPointVector = null;
		}
	};

	var startOpenLayers = function(){
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
				center: ol.proj.transform([8.000, 50.000], 'EPSG:4326', 'EPSG:3857'),
				zoom: 5
			})
		});

		updateMapSize();
		window.onresize = updateMapSize;

		map.on('click', function(event) {
			var feature = map.forEachFeatureAtPixel(event.pixel, function(feature, layer){return feature;});
			if(feature){
				var coord = feature.getGeometry().getCoordinates();
				coord = ol.proj.transform(coord, 'EPSG:3857', 'EPSG:4326');
				SubstanceTheme.showNotification("<h3 style='margin: 8px 0 4px 0'>" + feature.get("coord_name") + "</h3>" +
												"<p style='margin: 4px'>" + feature.get("coord_desc") + "</p>" +
												"<p style='margin: 2px; font-size: 10px'><i>" + coord[1].toFixed(6) + ", " + coord[0].toFixed(6) + "</i></p>", 7, $.mobile.activePage[0], "substance-blue no-shadow white");
			}
		});
	};

	var startup = function(task, coords){
		switch(task){
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
				displayCoordinates(coords, "#c82323", true, function(c){return c;});
				break;
			case MapController.MapTask.GET_POSITION:
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
}

MapController.openLayerLibraryLoaded = false;

MapController.init = function(myPageId){
	var myPrototype = new PagePrototype(myPageId, function(){
		return new MapController(GeoCat.loginStatus.isSignedIn ? MapController.MapTask.SHOW_ALL : MapController.MapTask.SHOW_PUBLIC, null);
	});

	MapController.prototype = myPrototype;

	MapController.showMap = function(mapTask, coordinates){
		var mapController = new MapController(mapTask, coordinates);
		myPrototype.setInstance(mapController);
		myPrototype.ignoreNextEvent();
		$.mobile.changePage(myPageId);
		mapController.pageOpened();
	}
};

MapController.MapTask = {
	SHOW_PUBLIC: 0,
	SHOW_PRIVATE: 1,
	SHOW_ALL: 2,
	SHOW_COORDS: 3,
	GET_POSITION : 4
};
