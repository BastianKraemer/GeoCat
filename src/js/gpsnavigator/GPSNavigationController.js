/*	GeoCat - Geolocation caching and tracking platform
	Copyright (C) 2015 Bastian Kraemer

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

function GPSNavigationController(localCoordinateStore, login_Status, myuplink ){

	// Define HTML-Element IDs
	var idList = new Object();
	idList["list"] = "#CurrentDestinationList";
	idList["panel"] = "#CurrentDesitionListPanel";
	idList["popup"] = "#GPSNavDestListPopup";
	idList["popup_add2own"]  = "#GPSNavDestListPopup_Add2OwnPlaces";
	idList["popup_save"] = "#GPSNavDestListPopup_Save";
	idList["popup_close"] = "#GPSNavDestListPopup_Close";
	idList["add_coordinate"] = "#GPSNavigator_AddCoordinate";
	idList["field_name"] = "#GPSNavDestListPopup_Name";
	idList["field_lat"] = "#GPSNavDestListPopup_Lat";
	idList["field_lon"] = "#GPSNavDestListPopup_Lon";
	idList["field_desc"] = "#GPSNavDestListPopup_Desc";
	idList["preferences"] = "#GPSNavigatorPreferencesPanel";
	idList["pref_rotate"] = "#GPSNavDisableRotation";
	idList["pref_debuginfo"] = "#GPSNavShowDebugInfo";
	idList["pref_offline_mode"] = "#GPSNavOfflineMode";

	var localCoordStore = localCoordinateStore;
	var uplink = myuplink;
	var login_status = login_Status;

	this.getNavigatorInstance = getNavigatorInstance;

	function getNavigatorInstance(){
		if(pages["gpsnavigator"] == null){
			pages["gpsnavigator"] = new GPSNavigator($("#gpsnavigator_content")[0]);
		}

		return pages["gpsnavigator"];
	}

	this.onPageOpened = function(){

		var nav = getNavigatorInstance();
		nav.startNavigator(localCoordStore);

		// Append some event handler

		/*
		 * On panel opened
		 */
		$(idList["panel"]).on("panelbeforeopen", function(){
			updateCurrentDestinationList();
		});

		/*
		 * Add coordinate button
		 */
		$(idList["add_coordinate"]).click(function(e){
			if(pages["gpsnavigator"] == null){return;}

			var lastGPSPos = pages["gpsnavigator"].getGPSPos();
			if(lastGPSPos != null){
				showCoordinateEditDialog(null, "", "", lastGPSPos.coords.latitude, lastGPSPos.coords.longitude, true);
			}
			else{
				Tools.showPopup("Notification", "Unable to get current GPS position.", "OK", function(){resetActiveButtonState(idList["add_coordinate"]);});
			}
		});

		function loadNavListFromServer(){
			if(login_status.isSignedIn){
				uplink.sendNavList_Get(
							false,
							function(response){
								var result = JSON.parse(response);
								for(var i = 0; i < result.length; i++){
									result[i].is_public = false;
									localCoordStore.addCoordinateToNavigation(result[i]);
								}
							},
							function(response){
								alert(Tools.sprintf("Unable to perform this operation. (Status {0})\n" +
													"Server returned: {1}", [response["status"], response["msg"]]));
							});
			}
		};

		// This is part of the constructor

		// Download the latest nav list from the server
		loadNavListFromServer();

		/*
		 * Save button in popup dialog is clicked
		 */
		$(idList["popup_save"]).click(function(e) {
			var id = $(idList["popup"]).attr("dest-id");

			var name = $(idList["field_name"]).val();
			var desc = $(idList["field_desc"]).val();

			var msg = "%s enthält ungültige Zeichen. Bitte verwenden Sie nur 'A-Z', '0-9' sowie einige Sonderzeichen ('!,;.#_-*')."
			if(!localCoordStore.verifyString(name)){
				alert(msg.replace("%s", "Der Name"));
				return;
			}

			if(desc != ""){
				if(!localCoordStore.verifyDescriptionString(desc)){
					alert(msg.replace("%s", "Die Beschreibung"));
					return;
				}
			}

			var lat = parseFloat($(idList["field_lat"]).val());
			var lon = parseFloat($(idList["field_lon"]).val());

			if(name == "" || isNaN(lat) || isNaN(lon)){
				alert("Please enter a valid name and values for latitude and longitude.");
			}
			else if(login_status.isSignedIn){
				// Everything ok

				if(id == undefined){
					// It is a new place
					var add2OwnPlaces = $(idList["popup_add2own"]).is(":checked");

					if(add2OwnPlaces){
						// This place will be added to your own places
						uplink.sendNewCoordinate(name, desc, lat, lon, false, true,
								function(result){
									var coord = new Coordinate(result["coord_id"], name, lat, lon, desc, false)
									addCoordToNavList(coord, true);
								},
								function(response){
									alert(Tools.sprintf("Unable to perform this operation. (Status {0})\n" +
														"Server returned: {1}", [response["status"], response["msg"]]));
								});
					}
					else{
						// This place has to be added to the navigation list only
						addCoordToNavList(new Coordinate.create(name, lat, lon, desc), false);
					}
				}
				else{
					if(id > 0){
						var coord = localCoordStore.get(id);
						coord.name = name;
						coord.desc = desc;
						coord.lat = lat;
						coord.lon = lon;

						uplink.sendCoordinateUpdate(coord, true,
								function(result){
									localCoordStore.storePlace(coord);

									// Update label text
									$(idList["list"] + " li[dest-id=" + id + "] a[href='#']").text($(idList["field_name"]).val());
								},
								function(response){
									alert(Tools.sprintf("Unable to perform this operation. (Status {0})\n" +
														"Server returned: {1}", [response["status"], response["msg"]]));
								});
					}
					else{
						localCoordStore.storePlace(coord);
					}
				}

				// Close popup
				$(idList["popup"]).popup("close");
			}
			else{
				// The user is not signed in - just add the coordinate to the navigation
				localCoordStore.addCoordinateToNavigation(new Coordinate.create(name, lat, lon, desc));
			}
		});

		function addCoordToNavList(coord, coordinateAlreadyExistsOnServer){

			if(coordinateAlreadyExistsOnServer){
				uplink.sendNavList_Add(coord.coord_id, true,
						function(result){
							localCoordStore.addCoordinateToNavigation(coord);
						},
						function(response){
							alert(Tools.sprintf("Unable to perform this operation. (Status {0})\n" +
												"Server returned: {1}", [response["status"], response["msg"]]));
						});
			}
			else{
				uplink.sendNavList_Create(coord.name, coord.desc, coord.lat, coord.lon, true,
						function(result){
							coord.coord_id = result.coord_id; //Chacnge the coord_id to the id that has been returned from the server
							localCoordStore.addCoordinateToNavigation(coord);
						},
						function(response){
							alert(Tools.sprintf("Unable to perform this operation. (Status {0})\n" +
												"Server returned: {1}", [response["status"], response["msg"]]));
						});
			}
		}

		$(idList["popup"]).on("popupafterclose", function(event, ui){
			resetActiveButtonState(idList["add_coordinate"]);
		});

		/*
		 * Before Preferences panel is opened
		 */
		$(idList["preferences"]).on("panelafteropen", function(){
			$(idList["pref_rotate"]).val(getPreference("rotate")).slider("refresh");
			$(idList["pref_debuginfo"]).val(getPreference("debug_info")).slider("refresh");
			$(idList["pref_offline_mode"]).val(getPreference("offline_mode")).slider("refresh");
		});

		$(idList["popup_close"]).click(function(){
			$(idList["popup"]).popup("close");
		});

		bindPreferenceChangeEvent(idList["pref_rotate"], "rotate");
		bindPreferenceChangeEvent(idList["pref_debuginfo"], "debug_info");
		bindPreferenceChangeEvent(idList["pref_offline_mode"], "offline_mode");

		//$(idList["panel"]).on("panelbeforeclose", function(){});
	}

	function getPreference(key){
		var val = pages["gpsnavigator"].getPreference("rotate");
		if(val != undefined){
			return val == true ? "on" : "off";
		}
		return false;
	}

	function bindPreferenceChangeEvent(id, preferenceKey){
		$(id).bind( "change", function(event, ui) {
			pages["gpsnavigator"].setPreference(preferenceKey, $(id).is(":checked"));
		});
	}

	function resetActiveButtonState(buttonId){
		$(buttonId).removeClass($.mobile.activeBtnClass);
	}

	this.onPageClosed = function(){
		if(pages["gpsnavigator"] != null){

			// Remove all event handler
			$(idList["panel"]).off();
			$(idList["popup"]).off();
			$(idList["pref_rotate"]).unbind();
			$(idList["pref_debuginfo"]).unbind();
			$(idList["pref_offline_mode"]).unbind();
			$(idList["popup_save"]).unbind();
			$(idList["add_coordinate"]).unbind();
			$(idList["popup_close"]).unbind();

			pages["gpsnavigator"].stopNavigator();

			pageHeightOffset = 80; //global variable
		}
	}

	this.showCoordinateEditDialogForExistingDestination = function(destID){
		var dest = localCoordStore.get(destID);
		showCoordinateEditDialog(destID, dest.name, dest.desc, dest.lat, dest.lon, false);
	}

	function showCoordinateEditDialog(id, name, description, latitude, longitude, showAdd2OwnPlaces){

		$(idList["field_name"]).val(name);
		$(idList["field_desc"]).val(description);
		$(idList["field_lat"]).val(latitude);
		$(idList["field_lon"]).val(longitude);
		$(idList["popup"]).css("width", window.innerWidth - (window.innerWidth * 0.1) + "px");

		if(id == null){
			$(idList["popup"]).removeAttr("dest-id");
		}
		else{
			$(idList["popup"]).attr("dest-id", id);
		}

		$(idList["popup_add2own"]).parent().css("display", showAdd2OwnPlaces ? "block" : "none" );

		$(idList["popup"]).popup("open", {positionTo: "window", transition: "pop"});
	}

	function updateCurrentDestinationList(){
		var destList = $(idList["list"]);
		destList.empty()

		var list = localCoordStore.getCurrentNavigation();
		for(var key in list){
			// TODO: Replace onclick handler by jQuery $(...) call
			destList.append("<li dest-id=\"" + key + "\">" +
							"<a onclick=\"gpsNavigationController.showCoordinateEditDialogForExistingDestination('" + key + "');\" href=\"#\">" + list[key].name + "</a>" +
							"<a onclick=\"gpsNavigationController.deleteListItem('" + key + "');\" class=\"ui-icon-delete\">Remove</a>" +
							"</li>");
		}

		destList.listview('refresh');
	}

	this.deleteListItem = function(key){
		if(login_status.isSignedIn){
			uplink.sendNavList_Remove(key, true,
						function(response){
							localCoordStore.removeFromNavigationById(key);
							updateCurrentDestinationList();
						},
						function(response){
							alert(Tools.sprintf("Unable to perform this operation. (Status {0})\n" +
												"Server returned: {1}", [response["status"], response["msg"]]));
						});
		}
		else{
			localCoordStore.removeFromNavigationById(key);
			updateCurrentDestinationList();
		}
	}
}
