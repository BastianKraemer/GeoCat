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

	var limitPerRequest = window.innerHeight / 100; // one list entry has a height of approximately 100px
	if(limitPerRequest < 10){limitPerRequest = 10;}

	var scrollLoader;
	var currentOffset = 0;
	var currentListType;

	// Collection of all important HTML elements (defined by their id)
	var htmlElement = {
		listview: "#ChallengeListView",
		keyInputField: "#ChallengeKeyInput",
		keyInputOK: "#ChallengeKeyInput-OK",
		joinChallengePopup: "#JoinChallengePopup",
		createChallengePopup: "#create-challenge-popup",
		createChallengeInput: "#create-challenge-input",
		createChallengeErrorInfo: "#create-challenge-errorinfo",
		createChallengeConfirm: "#create-challenge-confirm",
		showPublicChallenges: "#challenge-list-public",
		showJoinedChallenges: "#challenge-list-joined",
		showOwnChallenges: "#challenge-list-owner",
		tabBar: "#challenge-list-tabs"
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
	 * @function pageOpened
	 * @memberOf BrowseChallengesController
	 * @instance
	 */
	this.pageOpened = function(){

		$(htmlElement.listview).listview('refresh');

		scrollLoader = new ScrollLoader(
						$(htmlElement.listview)[0],
						function(successCallback){
							var arrayLengthCallback = function(arrLength){
								currentOffset += arrLength;
								successCallback(arrLength >= limitPerRequest);
							}
							downloadChallengesFromServer(limitPerRequest, currentOffset, false, arrayLengthCallback);
						}, 48);

		var changeListType = function(newListType){
			BrowseChallengesController.currentListType = newListType;
			currentOffset = 0;
			var callback = function(arrLength){
				currentOffset += arrLength;
				scrollLoader.setEnable(arrLength >= limitPerRequest);
			}
			downloadChallengesFromServer(limitPerRequest, currentOffset, true, callback);
		};

		$(htmlElement.showPublicChallenges).click(function(){
			changeListType("public");
		});

		$(htmlElement.showJoinedChallenges).click(function(){
			changeListType("joined");
		});

		$(htmlElement.showOwnChallenges).click(function(){
			changeListType("own");
		});

		$(htmlElement.showPublicChallenges).addClass("ui-btn-active");

		// Disable tab bar if the user is not signed in
		if(GeoCat.loginStatus.isSignedIn){
			$(htmlElement.tabBar).show();
		}
		else{
			$(htmlElement.tabBar).hide();
			BrowseChallengesController.currentListType = "public"
		}

		// Initialize this page by with the last used list type
		changeListType(BrowseChallengesController.currentListType);

		// Event handler for the buttons at the bottom
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
	 * @function pageClosed
	 * @memberOf BrowseChallengesController
	 * @instance
	 */
	this.pageClosed = function(){
		$(htmlElement.createChallengeConfirm).unbind();
		$(htmlElement.keyInputOK).unbind();
		$(htmlElement.showPublicChallenges).unbind();
		$(htmlElement.showJoinedChallenges).unbind();
		$(htmlElement.showOwnChallenges).unbind();
		$(htmlElement.listview).empty();

		scrollLoader.destroy();
	}

	/*
	 * ============================================================================================
	 * Private methods
	 * ============================================================================================
	 */

	var downloadChallengesFromServer = function(limit, offset, clearList, arrayLengthCallback){
		$.ajax({
			type: "POST", url: "query/challenge.php",
			data: {
				task: "get_challenges",
				type: BrowseChallengesController.currentListType,
				limit: limit,
				offset: offset
			},
			cache: false,
			success: function(response){
				var result = JSON.parse(response);
				updateList(result, clearList);
				if(arrayLengthCallback != null){arrayLengthCallback(result.length);}
			},
			error: function(xhr, status, error){
				SubstanceTheme.showNotification("<h3>Unable to dowload challenge list.</h3><p>Please try again later.</p>", 7,
						$.mobile.activePage[0], "substance-red no-shadow white");
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
	var updateList = function(data, clear){
		var list = $(htmlElement.listview);
		if(clear){list.empty();}

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
			list.append("<li><span>" + GeoCat.locale.get("challenge.browse.empty", "There is no public challenge at the moment.") + "</span></li>");
			list.listview('refresh');
		}
	};

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
				"<li class=\"challenge-list-item\" data-session-key=\"" + sessionKey + "\" data-icon=\"false\"><a class=\"li-clickable\">" +
					"<h3>"+ name + "</h3>" +
					"<p>" + desc + "</p>" +
					"<p class=\"ui-li-aside\"><i>" + GeoCat.locale.get("challenge.start_date", "Start time:") + "</i><br>" + start_time.replace(" ", "<br>") + "</p>" +
				"</li>\n";
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

BrowseChallengesController.currentListType = "public";

BrowseChallengesController.init = function(myPageId){
	BrowseChallengesController.prototype = new PagePrototype(myPageId, function(){
		return new BrowseChallengesController();
	});
};
