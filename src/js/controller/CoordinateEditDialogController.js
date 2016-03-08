function CoordinateEditDialogController(data, options, returnToPageId, returnCallback, confirmCallback){

	var inputElements = {
		name: {id: "#EditCoordinate-name", isCheckbox: false},
		desc: {id: "#EditCoordinate-desc", isCheckbox: false},
		lat: {id: "#EditCoordinate-lat", isCheckbox: false},
		lon: {id: "#EditCoordinate-lon", isCheckbox: false},
		isPublic: {id: "#EditCoordinate-ispublic",isCheckbox: true},
	};

	var containter = {
			isPublicContainer: "#EditCoordinate-ispublic-container",
			descriptionContainer: "#EditCoordinate-desc-container",
			hintContainer: "#EditCoordinate-hint-container",
			priorityContainer: "#EditCoordinate-priority-container",
			codeContainer: "#EditCoordinate-code-container"
	}

	var buttons = {
		confirm: "#EditCoordinate-confirm",
		cancel: "#EditCoordinate-cancel"
	};

	var me = this;
	var autoClose = true;

	this.pageOpened = function(){

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
			var ret = confirmCallback(data, me);

			if(autoClose){
				returnToPreviousPage();
			}

		});

		$(buttons.cancel).click(returnToPreviousPage);
	};

	var returnToPreviousPage = function(){
		if(returnToPageId.charAt(0) != '#'){returnToPageId = "#" + returnToPageId;}
		$.mobile.changePage(returnToPageId);
	};

	this.close = function(){
		returnToPreviousPage();
	}

	this.pageClosed = function(){
		for(var key in buttons){
			$(buttons[key]).unbind();
		}
		$("#EditCoordinate-starting-point").unbind();
		clearForms();

		setTimeout(returnCallback, 500);
	};

	var evaluateOptions = function(){

		// Insert the current position in the input fields
		if(optionIsActive("getCurrentPos")){
			GPS.getOnce(function(pos){
				if($(inputElements.lat.id).val() == "" && $(inputElements.lon.id).val("")){
					$(inputElements.lat.id).val(pos.coords.latitude);
					$(inputElements.lon.id).val(pos.coords.longitude);
				}
			});
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
	}

	var optionIsActive = function(optionName){
		if(options.hasOwnProperty(optionName)){
			if(options[optionName]){return true;}
		}
		return false;
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

		return obj;
	}

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
	}
}

CoordinateEditDialogController.init = function(myPageId){

	var myPrototype = new PagePrototype(myPageId, null);

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