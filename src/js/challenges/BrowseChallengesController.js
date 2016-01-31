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

	// Collection (Map) of all important HTML elements (defeined by their id)

	var htmlElement = new Object();
	htmlElement["listview"] = "#ChallengeListView";
	htmlElement["page_info"] = "#ChallengePageInformation";
	htmlElement["button_next_page"] = "#Browse_Next";
	htmlElement["button_prev_page"] = "#Browse_Prev";

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

		$(htmlElement["listview"]).listview('refresh');
		countPublicChallenges();
		loadPublicChallengeListFromServer();

		$(htmlElement["button_next_page"]).click(function(){
			if(currentPage < maxPages - 1){
				currentPage++;
				loadPublicChallengeListFromServer();
			}
		});

		$(htmlElement["button_prev_page"]).click(function(){
			if(currentPage > 0){
				currentPage--;
				loadPublicChallengeListFromServer();
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
		$(htmlElement["button_next_page"]).unbind();
		$(htmlElement["button_prev_page"]).unbind();
	}

	/*
	 * ============================================================================================
	 * Private methods
	 * ============================================================================================
	 */

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
	 * Updates the list with the data of the ajax request
	 * @param data {Object}
	 *
	 * @private
	 * @memberOf BrowseChallengesController
	 * @instance
	 */
	function updateList(data){
		var list = $(htmlElement["listview"]);
		list.empty();

		if(data.length > 0){
			for(var i = 0; i < data.length; i++){
				var c = data[i];
				list.append(generateChallengeItemCode(c.name, c.owner_name, c.challenge_id, c.description, c.type_name, c.start_time));
			}

			list.listview('refresh');
			$(htmlElement["listview"] + " li a.li-clickable").click(function(){
				challenge_OnClick(this);
			});
		}
		else{
			list.append("<li><span>" + locale.get("challenge.browse.empty", "There is no public challenge at the moment.") + "</span></li>");
			list.listview('refresh');
		}
		updatePageInfo();
	}

	function displayError(message){
		GuiToolkit.showPopup("Error", message, "OK", null);
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
	function generateChallengeItemCode(name, owner, challenge_id, desc, type, start_time){

		return 	"<li class=\"challenge-list-item\" data-role=\"list-divider\">" +
					"<span class=\"listview-left\">" + type + "</span>" +
					"<span class=\"listview-right\">" + owner + "</span></li>" +
				"<li class=\"challenge-list-item\" challenge-id=\"" + challenge_id + "\"><a class=\"li-clickable\">" +
					"<h3>"+ name + "</h3>" +
					"<p>" + desc + "</p>" +
					"<p class=\"ui-li-aside\"><i>" + locale.get("challenge.start_date", "Start time:") + "</i><br>" + start_time.replace(" ", "<br>") + "</p>" +
				"</li>\n";
	}

	function updatePageInfo(){
		var numPages = maxPages > 0 ? maxPages : 1;
		$(htmlElement["page_info"]).html(GuiToolkit.sprintf(locale.get("page_of", "Page {0} of {1}"), [(currentPage + 1), numPages]));
	}

	/*
	 * ============================================================================================
	 * "OnClick" functions
	 * ============================================================================================
	 */

	function challenge_OnClick(el){
		var cId = $(el).parent().attr("challenge-id");
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
