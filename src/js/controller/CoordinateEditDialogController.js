/* GeoCat - Geocaching and -tracking application
 * Copyright (C) 2016 Bastian Kraemer
 *
 * CoordinateEditDialogController.js
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

function CoordinateEditDialogController(data, options, returnToPageId, returnCallback, confirmCallback){

	var inputElements = {
		name: {id: "#EditCoordinate-name", isCheckbox: false},
		desc: {id: "#EditCoordinate-desc", isCheckbox: false},
		lat: {id: "#EditCoordinate-lat", isCheckbox: false},
		lon: {id: "#EditCoordinate-lon", isCheckbox: false},
		isPublic: {id: "#EditCoordinate-ispublic", isCheckbox: true},
	};

	var containter = {
			isPublicContainer: "#EditCoordinate-ispublic-container",
			descriptionContainer: "#EditCoordinate-desc-container",
			hintContainer: "#EditCoordinate-hint-container",
			priorityContainer: "#EditCoordinate-priority-container",
			codeContainer: "#EditCoordinate-code-container",
			gpsStatus: "#EditCoordinate-wait-for-gps"
	}

	var buttons = {
		confirm: "#EditCoordinate-confirm",
		cancel: "#EditCoordinate-cancel",
		getGPS: "#EditCoordinate-get-gps",
		getMap: "#EditCoordinate-get-map"
	};

	var me = this;
	var autoClose = true;
	var waitScreen = null;

	this.pageOpened = function(){

		// Most times this value is called 'isPublic', but sometimes it is also 'is_public'
		if(data.hasOwnProperty("is_public") && !data.hasOwnProperty("isPublic")){
			data["isPublic"] = data["is_public"];
			delete data["is_public"];
		}

		evaluateOptions();

		for(var key in inputElements){
			if(!inputElements[key].isCheckbox){
				$(inputElements[key].id).val(data.hasOwnProperty(key) ? data[key] : "");
			}
			else{
				var checkVal = false;
				if(data.hasOwnProperty(key)){
					if(data[key]){checkVal = true;}
				}
				$(inputElements[key].id).prop('checked', checkVal).checkboxradio('refresh');
			}
		}

		$(buttons.confirm).click(function(){
			var data = genDataObject();
			waitScreen = SubstanceTheme.showWaitScreen(GeoCat.locale.get("saving_data", "Please wait..."), $.mobile.activePage[0]);

			setTimeout(function(){
				confirmCallback(data, me);

				if(autoClose){
					returnToPreviousPage();
				}
			}, 200);
		});

		$(buttons.cancel).click(returnToPreviousPage);
		$(buttons.getGPS).click(getGPSPosition);
		$(buttons.getMap).click(selectPositionFromMap);
	};

	var returnToPreviousPage = function(){
		if(returnToPageId.charAt(0) != '#'){returnToPageId = "#" + returnToPageId;}
		$.mobile.changePage(returnToPageId);
	};

	this.close = function(){
		returnToPreviousPage();
	}

	this.hideWaitScreen = function(){
		if(waitScreen != null){
			waitScreen.hide();
			waitScreen = null;
		}
	}

	this.pageClosed = function(){
		for(var key in buttons){
			$(buttons[key]).unbind();
		}
		$("#EditCoordinate-starting-point").unbind();
		clearForms();

		this.hideWaitScreen();
		setTimeout(returnCallback, 500);
	};

	var evaluateOptions = function(){

		// Insert the current position in the input fields
		if(optionIsActive("getCurrentPos")){
			getGPSPosition();
		}

		if(optionIsActive("showHintField")){
			$(containter.hintContainer).show();
			inputElements["hint"] = {id: "#EditCoordinate-hint", isCheckbox: false};
		}
		else{
			$(containter.hintContainer).hide();
		}

		if(optionIsActive("showPriorityField")){

			$(containter.priorityContainer).show();
			inputElements["priority"] = {id: "#EditCoordinate-priority", isCheckbox: false};

			$("#EditCoordinate-starting-point").bind( "change", function(event, ui){
				if($("#EditCoordinate-starting-point").is(":checked")){
					 $(inputElements["priority"].id).val(0);
					 $(inputElements["priority"].id).textinput('disable');
				}
				else{
					 $(inputElements["priority"].id).val(1);
					 $(inputElements["priority"].id).textinput('enable');
				}
			});

			if(data.hasOwnProperty("priority")){
				if(data.priority == 0){
					$("#EditCoordinate-starting-point").prop('checked', true).checkboxradio('refresh');
					 $(inputElements["priority"].id).textinput('disable');
				}
			}
		}
		else{
			$(containter.priorityContainer).hide();
		}

		if(optionIsActive("showCodeField")){
			$(containter.codeContainer).show();
			inputElements["code"] = {id: "#EditCoordinate-code", isCheckbox: false};
		}
		else{
			$(containter.codeContainer).hide();
		}

		if(optionIsActive("showAddToOwnPlaces")){
			$("#EditCoordinate-add-to-own-places-container").show();
			inputElements["add2ownplaces"] = {id: "#EditCoordinate-add-to-own-places", isCheckbox: true};
		}
		else{
			$("#EditCoordinate-add-to-own-places-container").hide();
		}

		if(optionIsActive("hideIsPublicField")){
			$(containter.isPublicContainer).hide();
			delete inputElements.desc;
		}
		else{
			$(containter.isPublicContainer).show();
		}

		if(optionIsActive("hideDescriptionField")){
			$(containter.descriptionContainer).hide();
			delete inputElements.isPublic;
		}
		else{
			$(containter.descriptionContainer).show();
		}

		if(optionIsActive("noAutoClose")){
			autoClose = false;
		}
	};

	var optionIsActive = function(optionName){
		if(options.hasOwnProperty(optionName)){
			if(options[optionName]){return true;}
		}
		return false;
	};

	var getGPSPosition = function(){
		$(inputElements.lat.id).val("");
		$(inputElements.lon.id).val("");
		$(buttons.getGPS)[0].disabled = true;
		$(containter.gpsStatus).slideDown("slow");

		GPS.getOnce(function(pos){
			if($(inputElements.lat.id).val() == "" && $(inputElements.lon.id).val("")){
				$(inputElements.lat.id).val(pos.coords.latitude);
				$(inputElements.lon.id).val(pos.coords.longitude);
			}
			$(buttons.getGPS)[0].disabled = false;
			$(containter.gpsStatus).slideUp("slow");
		},
		function(p){
			$(containter.gpsStatus + " span").text("(" + p + "%)");
		});
	};

	var selectPositionFromMap = function(){
		me.ignoreNextEvent();
		MapController.showMap(
			MapController.MapTask.GET_POSITION,
			{
				callback: function(lat, lon){
					$(inputElements.lat.id).val(lat);
					$(inputElements.lon.id).val(lon);
				},
				coords: [{
					lat: $(inputElements.lat.id).val(),
					lon: $(inputElements.lon.id).val(),
				}],
				returnTo: CoordinateEditDialogController.pageId
			}
		);
	};

	var genDataObject = function(clear){
		var obj = {};

		for(var key in inputElements){
			if(!inputElements[key].isCheckbox){
				obj[key] = $(inputElements[key].id).val();
				if(clear){$(inputElements[key].id).val("");}
			}
			else{
				obj[key] = $(inputElements[key].id).is(':checked');
				if(clear){$(inputElements[key].id).prop('checked', false).checkboxradio('refresh');}
			}
		}

		if(obj.hasOwnProperty("lat")){obj["lat"] = obj["lat"].replace(",", ".");}
		if(obj.hasOwnProperty("lon")){obj["lon"] = obj["lon"].replace(",", ".");}

		return obj;
	};

	var clearForms = function(){
		for(var key in inputElements){
			if(!inputElements[key].isCheckbox){
				$(inputElements[key].id).val("");
			}
			else{
				$(inputElements[key].id).prop('checked', false).checkboxradio('refresh');
			}
		}

		if(inputElements.hasOwnProperty("priority")){
			$("#EditCoordinate-starting-point").prop('checked', false).checkboxradio('refresh');
			$(inputElements["priority"].id).textinput('enable');
		}
	};
}



CoordinateEditDialogController.init = function(myPageId){

	var myPrototype = new PagePrototype(myPageId, null);
	CoordinateEditDialogController.pageId = myPageId;

	CoordinateEditDialogController.prototype = myPrototype;

	CoordinateEditDialogController.showDialog = function(returnToPageId, returnCallback, confirmCallback, data, options){
		var controller = new CoordinateEditDialogController(data, options, returnToPageId, returnCallback, confirmCallback);
		myPrototype.setInstance(controller);
		$.mobile.changePage(myPageId);
	}
};

CoordinateEditDialogController.genDefaultDataObject = function(name, description, latitude, longitude, isPublic){
	return {name: name, desc: desc, lat: latitude, lon: longitude, is_public: isPublic};
};

CoordinateEditDialogController.genCacheDataObject = function(name, description, latitude, longitude, cacheHint, cachePriority, code){
	return {
		name: name,
		desc: description,
		lat: latitude,
		lon: longitude,
		hint: cacheHint,
		priority: cachePriority,
		code: code
	};
};

CoordinateEditDialogController.genDataObjectWithCoordinate = function(coord){
	CoordinateEditDialog.genDefaultDataObject(coord.name, coord.desc, coord.lat, coord.lon, coord.is_public);
};

CoordinateEditDialogController.convertToCoordinate = function(coordId, data){
	return new Coordinate(coordId, data.name, data.lat,	data.lon, data.desc, data.is_public);
};