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
 * Event handling for the "Places" page
 * @class PlacesController
 */
function MapController(){

	var mapId = "openlayers-map";
	var htmlElements = {
			map: "#" + mapId,
	}

	var map;
	var olPointVector;

	/**
	 * This function should be called when the places page is opened
	 *
	 * @public
	 * @function pageOpened
	 * @memberOf PlacesController
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
				},
				error: function(xhr, status, error){
					alert("AJAX ERROR");
				}
			});
		}
		else{
			startOpenLayers();
		}
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
		window.onresize = null;
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
				attributionOptions: /** @type {olx.control.AttributionOptions} */ ({
					collapsible: false
				})
			}),
			view: new ol.View({
				center: [0, 0],
				zoom: 2
			})
		});

		updateMapSize();
		window.onresize = updateMapSize;

		displayCurrentNavigation();
	};

	var updateMapSize = function(){
		var mapHeight = window.innerHeight - $("#" + $.mobile.activePage[0].id + " div:first-Child")[0].offsetHeight;
		$(htmlElements.map).css("height", mapHeight + "px");
		setTimeout(function(){map.updateSize();}, 200);
	};

	var createPoint = function(coord, coordColor){
		var p = new ol.Feature({
			geometry: new ol.geom.Point(ol.proj.fromLonLat([parseFloat(coord.lon), parseFloat(coord.lat)]))
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
			    offsetY: 12,
			    fill: new ol.style.Fill({color: coordColor})
			 })
		}));

		return p;
	};

	var createPoints = function(coordArray, color){
		var ret = new Array(coordArray.length);

		for(var i = 0; i < coordArray.length; i++){
			ret[i] = this.createPoint(coordArray[i], color);
		}

		return ret;
	};

	var display = function(pointArr){
		olPointVector.addFeatures(pointArr);
	};

	var clearMap = function(){
		vectorLayer.destroyFeatures();
	};

	// DEMO

	var displayCurrentNavigation = function(){
		var navList = GeoCat.getLocalCoordStore().getCurrentNavigation();

		var points = new Array();
		for(var key in navList){
			points.push(createPoint(navList[key], "blue"));
		}

		display(points);
	}

}

MapController.openLayerLibraryLoaded = false;

MapController.init = function(myPageId){
	MapController.prototype = new PagePrototype(myPageId, function(){
		return new MapController();
	});
};
