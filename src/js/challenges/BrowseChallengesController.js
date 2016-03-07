/*	GeoCat - Geolocation caching and tracking platform
	Copyright (C) 2016 Bastian Kraemer

	BrowseChallengesController.js

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
 * Event handling for the "Browse Challenges" page
 * @class BrowseChallengesController
 */
function BrowseChallengesController(){

	// Private variables
	var itemsPerPage = 10;
	var currentPage = 0;
	var challengeCount = 0;
	var maxPages = 0;
	var uplink = GeoCat.getUplink();
	var locale = GeoCat.locale;

	// Collection of all important HTML elements (defined by their id)

	var htmlElement = {
		listview: "#ChallengeListView",
		pageInfo: "#ChallengePageInformation",
		nextPageButton: "#Browse_Next",
		prevPageButton:	"#Browse_Prev",
		keyInputField: "#ChallengeKeyInput",
		keyInputOK: "#ChallengeKeyInput-OK",
		joinChallengePopup: "#JoinChallengePopup",
		createChallengePopup: "#create-challenge-popup",
		createChallengeInput: "#create-challenge-input",
		createChallengeErrorInfo: "#create-challenge-errorinfo",
		createChallengeConfirm: "#create-challenge-confirm",
		enabled: "#MyChallengesEnabled",
		notenabled: "#MyChallengesNotEnabled",
		joined: "#MyChallengesJoined"
	}

	/*
	 * ============================================================================================
	 * Public methods
	 * ============================================================================================
	 */

	/**
	 * This function should be called when the challenge browser page is opened
	 *
	 * @public
	 * @function onPageOpened
	 * @memberOf BrowseChallengesController
	 * @instance
	 */
	this.onPageOpened = function(){

		$(htmlElement.listview).listview('refresh');
		countPublicChallenges();
		loadPublicChallengeListFromServer();
		countMyChallenges();

		$(htmlElement.nextPageButton).click(function(){
			if(currentPage < maxPages - 1){
				currentPage++;
				loadPublicChallengeListFromServer();
			}
		});

		$(htmlElement.prevPageButton).click(function(){
			if(currentPage > 0){
				currentPage--;
				loadPublicChallengeListFromServer();
			}
		});

		$(htmlElement.createChallengeConfirm).click(function(){
			sendCreateChallengeRequest($(htmlElement.createChallengeInput).val(), function(status, msg, key){
				if(status){
					$(htmlElement.createChallengePopup).popup("close");
					setTimeout(function(){
						GeoCat.setCurrentChallenge(key)
						$.mobile.changePage("#ChallengeInfo");
					}, 150);
				}
				else{
					$(htmlElement.createChallengeErrorInfo).text(msg);
				}
			});
		});

		$(htmlElement.keyInputOK).click(function(){
			var key = $(htmlElement.keyInputField).val();
			if(key != ""){
				$(htmlElement.joinChallengePopup).popup("close");
				setTimeout(function(){
					$(htmlElement.keyInputField).val("");
					GeoCat.setCurrentChallenge(key)
					$.mobile.changePage("#ChallengeInfo");
				}, 150);
			}
		});
	}

	/**
	 * This function should be called when the challenge browser page is closed
	 *
	 * @public
	 * @function onPageClosed
	 * @memberOf BrowseChallengesController
	 * @instance
	 */
	this.onPageClosed = function(){
		$(htmlElement.nextPageButton).unbind();
		$(htmlElement.prevPageButton).unbind();
	}

	/*
	 * ============================================================================================
	 * Private methods
	 * ============================================================================================
	 */

	/**
	 * Sends a <b>COUNT_CHALLENGES</b> command to the server
	 *
	 * @private
	 * @memberOf BrowseChallengesController
	 * @instance
	 */
	function countPublicChallenges(){

		uplink.sendChallenge_CountPublic(
				function(response){
					try{
						var result = JSON.parse(response);

						if(result.hasOwnProperty("count")){
							challengeCount = parseInt(result.count);
							maxPages = Math.floor(challengeCount / itemsPerPage) + ((challengeCount % itemsPerPage == 0) ? 0 : 1);
							updatePageInfo();
						}
						else{
							displayError("An error occured, please try again later.");
						}
					}
					catch(e){
						displayError(GuiToolkit.sprintf("An error occured, please try again later.\\n\\n" +
												   "Details:\\n{0}", [e.message]));
					}
				});
	}

	/**
	 * Sends a request to the server to get the first page of the challenge list
	 *
	 * @private
	 * @memberOf BrowseChallengesController
	 * @instance
	 */
	function loadPublicChallengeListFromServer(){
		uplink.sendChallenge_GetPublic(
			function(response){
				try{
					updateList(JSON.parse(response));
				}
				catch(e){
					displayError(GuiToolkit.sprintf("An error occured, please try again later.\\n\\n" +
											   "Details:\\n{0}", [e.message]));
				}
			}, itemsPerPage, currentPage * itemsPerPage);
	}

	/**
	 * Sends a <b>COUNT_CHALLENGES</b> command to the server
	 *
	 * @private
	 * @memberOf BrowseChallengesController
	 * @instance
	 */
	function countMyChallenges(){

		uplink.sendChallenge_CountMyChallenges(
				function(response){
					try{
						var result = JSON.parse(response);

						if(result.hasOwnProperty("count")){
							if(parseInt(result.count) > 0){
								$('#my-challenges').removeClass('ui-state-disabled');
								loadMyChallengeListFromServer();
							}
						}
					}
					catch(e){
						displayError(GuiToolkit.sprintf("An error occured, please try again later.\\n\\n" +
												   "Details:\\n{0}", [e.message]));
					}
				});
	}

	/**
	 * Sends a request to the server to get all own (including not enabled) challenges
	 *
	 * @private
	 * @memberOf BrowseChallengesController
	 * @instance
	 */
	function loadMyChallengeListFromServer(){
		$(htmlElement.enabled).empty();
		$(htmlElement.notenabled).empty();
		$(htmlElement.joined).empty();

		uplink.sendChallenge_GetMyChallenges(
			"get_my_challenges",
			function(response){
				try{
					updateMyList(JSON.parse(response), false);
				}
				catch(e){
					displayError(GuiToolkit.sprintf("An error occured, please try again later.\\n\\n" +
											   "Details:\\n{0}", [e.message]));
				}
				uplink.sendChallenge_GetMyChallenges(
					"get_participated_challenges",
					function(response){
						try{
							updateMyList(JSON.parse(response), true);
						}
						catch(e){
							displayError(GuiToolkit.sprintf("An error occured, please try again later.\\n\\n" +
													   "Details:\\n{0}", [e.message]));
						}
					}
				);
			});
	}

	/**
	 * Updates the list with the data of the ajax request
	 * @param data {Object}
	 *
	 * @private
	 * @memberOf BrowseChallengesController
	 * @instance
	 */
	function updateList(data){
		var list = $(htmlElement.listview);
		list.empty();

		if(data.length > 0){
			for(var i = 0; i < data.length; i++){
				var c = data[i];
				list.append(generateChallengeItemCode(c.name, c.owner_name, c.sessionkey, c.description, c.type_name, c.start_time));
			}

			list.listview('refresh');
			$(htmlElement.listview + " li a.li-clickable").click(function(){
				challenge_OnClick(this);
			});
		}
		else{
			list.append("<li><span>" + locale.get("challenge.browse.empty", "There is no public challenge at the moment.") + "</span></li>");
			list.listview('refresh');
		}
		updatePageInfo();
	}

	/**
	 * Generates the HTML-Code for a single list item
	 * @param name {String} Challenge name
	 * @param owner {String} Challenge owner
	 * @param challenge_id {Integer} challenge id
	 * @param desc {String} Challenge description
	 * @param type {String} Challenge type
	 * @param start_time {String} Start time of the challenge
	 *
	 * @private
	 * @memberOf BrowseChallengesController
	 * @instance
	 */
	function generateChallengeItemCode(name, owner, sessionKey, desc, type, start_time){

		return 	"<li class=\"challenge-list-item\" data-role=\"list-divider\">" +
					"<span class=\"listview-left\">" + type + "</span>" +
					"<span class=\"listview-right\">" + owner + "</span></li>" +
				"<li class=\"challenge-list-item\" data-session-key=\"" + sessionKey + "\"><a class=\"li-clickable\">" +
					"<h3>"+ name + "</h3>" +
					"<p>" + desc + "</p>" +
					"<p class=\"ui-li-aside\"><i>" + locale.get("challenge.start_date", "Start time:") + "</i><br>" + start_time.replace(" ", "<br>") + "</p>" +
				"</li>\n";
	}

	/**
	 * Updates the own challenges list with the data of the ajax request
	 * @param data {Object}
	 * @param participated {boolean}
	 *
	 * @private
	 * @memberOf BrowseChallengesController
	 * @instance
	 */
	function updateMyList(data, participated){

		if(data.length > 0){
			for(var i = 0; i < data.length; i++){
				var c = data[i];
				var path;
				if(participated){
					path = $(htmlElement.joined)
				} else {
					path = (c.is_enabled == "1" ? $(htmlElement.enabled) : $(htmlElement.notenabled));
				}
				path.append(generateChallengeItemCode(c.name, c.username, c.sessionkey, c.description, c.full_name, c.start_time));
			}

			$(htmlElement.enabled).listview('refresh');
			$(htmlElement.notenabled).listview('refresh');
			$(htmlElement.joined).listview('refresh');

			$(htmlElement.enabled + " li a.li-clickable").click(function(){
				challenge_OnClick(this);
			});
			$(htmlElement.notenabled + " li a.li-clickable").click(function(){
				challenge_OnClick(this);
			});
			$(htmlElement.joined + " li a.li-clickable").click(function(){
				challenge_OnClick(this);
			});

		}
	}

	function displayError(message){
		GuiToolkit.showPopup("Error", message, "OK", null);
	}

	function updatePageInfo(){
		var numPages = maxPages > 0 ? maxPages : 1;
		$(htmlElement.pageInfo).html(GuiToolkit.sprintf(locale.get("page_of", "Page {0} of {1}"), [(currentPage + 1), numPages]));
	}

	function sendCreateChallengeRequest(challengeName, callback){
		$.ajax({type: "POST", url: "./query/challenge.php",
			data: {
				task: "create_challenge",
				name: challengeName
			},
			cache: false,
			success: function(response){
				ajaxSent = false;
				result = JSON.parse(response);
				if(result.status == "ok"){
					callback(true, "", result.sessionkey);
				}
				else{
					callback(false, result.msg, null);
				}
			},
			error: function(xhr, status, error){
				callback(false, "Server unreachable.", null);
		}});
	}

	/*
	 * ============================================================================================
	 * "OnClick" functions
	 * ============================================================================================
	 */

	function challenge_OnClick(el){
		GeoCat.setCurrentChallenge($(el).parent().attr("data-session-key"));
		$.mobile.pageContainer.pagecontainer("change", "#ChallengeInfo");
	}
}

BrowseChallengesController.currentInstance = null;

BrowseChallengesController.init = function(){
	$(document).on("pageshow", "#ChallengeBrowser", function(){
		BrowseChallengesController.currentInstance = new BrowseChallengesController();
		BrowseChallengesController.currentInstance.onPageOpened();
	});

	$(document).on("pagebeforehide", "#ChallengeBrowser", function(){
		BrowseChallengesController.currentInstance.onPageClosed();
		BrowseChallengesController.currentInstance = null
	});
};