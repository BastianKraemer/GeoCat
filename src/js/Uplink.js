function Uplink(contextRoot){

	var urlPrefix = contextRoot;

	function sendHTTPRequest(url, dataObj, expectStatusResponse, successCallback, errorCallback, onAjaxError){
		$.ajax({
				type: "POST", url: url,
				data: dataObj,
				cache: false,
				success: function(response){
					if(expectStatusResponse){
						var result = JSON.parse(response);
						if(result.status == "ok"){
							successCallback(result.msg);
						}
						else{
							errorCallback(result);
						}
					}
					else{
						successCallback(response);
					}
				},
				error: function(xhr, status, error){
					if(onAjaxError != null){
						onAjaxError(error)
					}
				}
		});
	}

	function ajaxERROR(msg){
		alert("Unable to send HTTP Request: " + msg);
	}

	this.sendNewCoordinate = function(placeName, placeDesc, placeLat, placeLon, placeIsPublic, expectStatusResponse, successCallback, errorCallback) {
		sendHTTPRequest(urlPrefix + "query/places.php",
						{
							cmd: "add",
							name: placeName,
							desc: placeDesc,
							lat: placeLat,
							lon: placeLon,
							is_public: placeIsPublic
						},
						expectStatusResponse,
						successCallback,
						errorCallback,
						ajaxERROR);
	}

	this.sendCoordinateUpdate = function(coord, expectStatusResponse, successCallback, errorCallback) {
		sendHTTPRequest(urlPrefix + "query/places.php",
						{cmd: "update", data_type: "json", data: JSON.stringify(coord)},
						expectStatusResponse,
						successCallback,
						errorCallback,
						ajaxERROR);
	}

	this.sendDeleteCoordinate = function(coordId, expectStatusResponse, successCallback, errorCallback) {
		sendHTTPRequest(urlPrefix + "query/places.php",
						{cmd: "remove", coord_id: coordId},
						expectStatusResponse,
						successCallback,
						errorCallback,
						ajaxERROR);
	}

}
