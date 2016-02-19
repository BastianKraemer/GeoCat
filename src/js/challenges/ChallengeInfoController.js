function ChallengeInfoController(sessionKey){

	var challengeSessionKey = sessionKey;

	this.pageOpened = function(){
		downloadChallengeInfo();
	};

	this.pageClosed = function(){

	};

	var ajaxError = function(xhr, status, error){
		SubstanceTheme.showNotification("<h3>Unknown error - Ajax request failed</h3><p>" + error + "</p>", 7,
				$.mobile.activePage[0], "substance-red no-shadow white");
	};

	var downloadChallengeInfo = function(){
		$.ajax({
			type: "POST", url: "./query/challenge.php",
			encoding: "UTF-8",
			data: {task: "about", challenge: challengeSessionKey},
			cache: false,
			success: function(response){
				try{
					var responseData = JSON.parse(response);
					if(responseData.status == "ok"){
						onChallengeDataReceived(responseData);
					}
					else{
						SubstanceTheme.showNotification("<h3>" + GeoCat.locale.get("challenge.nav.error.download_info", "Unable to download challenge information") + "</h3>" +
														"<p>" + responseData.msg + "</p>", 7, $.mobile.activePage[0], "substance-red no-shadow white");
					}
				}
				catch(e){
					SubstanceTheme.showNotification("<h3>Unable to download challenge information</h3><p>Server returned:<br>" + response + "</p>", 7,
													$.mobile.activePage[0], "substance-red no-shadow white");
				}
			},
			error: ajaxError
		});
	};

	var downloadCoordData = function(){
		$.ajax({
			type: "POST", url: "./query/challenge.php",
			encoding: "UTF-8",
			data: {task: "coord_info", challenge: challengeSessionKey},
			cache: false,
			success: function(response){
				try{
					var responseData = JSON.parse(response);
					if(responseData.status == "ok"){
						onCoordinateDataReceived(responseData);
					}
					else{
						// The only error that could oocur ist that the challenge has not started yet...
						$("#challengeinfo-cache-list").html("<tr><td colspan=4>" + GeoCat.locale.get("challenge.info.not_started", "The challenge has not started yet.") + "</td></tr>");
					}
				}
				catch(e){
					SubstanceTheme.showNotification("<h3>Unable to download cache positions</h3><p>Server returned:<br>" + response + "</p>", 7,
													$.mobile.activePage[0], "substance-red no-shadow white");
				}
			},
			error: ajaxError});
	};

	var onChallengeDataReceived = function(data){

		$("#challengeinfo-title").html(data["name"]);
		$("#challengeinfo-description").html(data["description"]);

		$("#challengeinfo-owner").html(data["owner_name"]);
		$("#challengeinfo-type").html(data["type_name"]);
		$("#challengeinfo-start-time").html(data["start_time"]);
		$("#challengeinfo-end-time").html(data["end_time"] == null ? "-" : data["end_time"]);

		updateTeamList(data);

		if(data["is_enabled"]){
			downloadCoordData();
		}
	};

	var onCoordinateDataReceived = function(data){

		if(data["coords"].length > 0){
			$("#challengeinfo-cache-list").html("");
			data["coords"].forEach(function(coord){
				$("#challengeinfo-cache-list").append(
					"<tr>" +
						"<td>" + coord.name + "</td>" +
						"<td>" + (coord.hint == null ? "-" : coord.hint) + "</td>" +
						"<td>" + (coord.code_required == 1 ? GeoCat.locale.get("yes", "Yes") : GeoCat.locale.get("no", "No")) + "</td>" +
						"<td>" + coord.latitude + ", " + coord.longitude + "</td>" +
					"</tr>");
			});
		}
		else{
			$("#challengeinfo-cache-list").html("<tr><td colspan=4>" + GeoCat.locale.get("challenge.info.no_caches", "There are no caches assigned to this challenge") + ".</td></tr>");
		}
	};

	var updateCacheList = function(data){
		$("#challengeinfo-team-list").html("");

		data["team_list"].forEach(function(teamData) {
			$("#challengeinfo-team-list").append(
				"<tr>" +
					"<td style=\"background-color: " + teamData.color + "; width: 0px;\"></td>" +
					"<td>" + teamData.name + (teamData.has_code == 1 ? "*" : "") + "</td>" +
					"<td>" + teamData.member_cnt + "/" + data["max_team_members"] + "</td>" +
				"</tr>");
		});
	};

	var updateTeamList = function(data){
		$("#challengeinfo-team-list").html("");

		data["team_list"].forEach(function(teamData) {
			var teamName;
			if(teamData.team_id == data["your_team"]){
				teamName = "<b><i>" + teamData.name + (teamData.has_code == 1 ? "*" : "") + "</i></b>"
			}
			else{
				teamName = teamData.name + (teamData.has_code == 1 ? "*" : "")
			}

			$("#challengeinfo-team-list").append(
				"<tr>" +
					"<td style=\"background-color: " + teamData.color + "; width: 0px;\"></td>" +
					"<td>" + teamName + "</td>" +
					"<td>" + teamData.member_cnt + "/" + data["max_team_members"] + "</td>" +
				"</tr>");
		});
	};

}

ChallengeInfoController.currentInstance = null;

ChallengeInfoController.openPage = function(){

	var key = GeoCat.getCurrentChallenge();

	if(key == ""){
		key = location.search;
		if(key != ""){
			key = key.slice(1);
		}
		else{
			SubstanceTheme.showNotification("<h3>Unknown Sessionkey</h3>", 7, $.mobile.activePage[0], "substance-red no-shadow white")
			return;
		}
	}

	ChallengeInfoController.currentInstance = new ChallengeInfoController(key);
	ChallengeInfoController.currentInstance.pageOpened();
};

ChallengeInfoController.closePage = function(){
	ChallengeInfoController.currentInstance.pageClosed();
	ChallengeInfoController.currentInstance = null;
};

ChallengeInfoController.init = function(){
	$(document).on("pageshow", "#ChallengeInfo", ChallengeInfoController.openPage);
	$(document).on("pagebeforehide", "#ChallengeInfo", ChallengeInfoController.closePage);
};
