/*	GeoCat - Geolocation caching and tracking platform
	Copyright (C) 2016 Bastian Kraemer

	BuddyController.js

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
 * Controller for the GeoCat buddy page
 *
 * @class BuddyController
 */
function BuddyController(){
	var htmlElements = {
		list: "#buddy-list",
		searchInput: "#buddy-search-input",
		searchButton: "#buddy-search-confirm",
		buttonShowList: "#buddies-show-list-btn",
		buttonSearchMode: "#buddies-search-mode-btn"
	}

	var hightlightStyleClass = "substance-lime";
	var regularStyleClass = "substance-blue";

	var currentMode = BuddyController.modeTypes.BUDDY_LIST;

	/**
	 * This function is called when the page is opened
	 *
	 * @public
	 * @function pageOpened
	 * @memberOf BuddyController
	 * @instance
	 */
	this.pageOpened = function(){
		if(GeoCat.loginStatus.isSignedIn){
			startup();
		}
		else{
			$.mobile.changePage("#Home");
			setTimeout(function(){
				SubstanceTheme.showNotification(
					"<p>" + GeoCat.locale.get("nologin", "Please sign in to use this feature.") + "</p>", 7,
					$.mobile.activePage[0], "substance-red no-shadow white");
			}, 200);
		}

		var searchAction = function(){
			runBuddyFilter($(htmlElements.searchInput).val());
		}

		$(htmlElements.searchButton).click(searchAction);
		$(htmlElements.searchInput).keyup(function(e){
			if(e.keyCode == 13){
				searchAction();
			}
		});

		$(htmlElements.buttonShowList).click(function(){
			changeMode(BuddyController.modeTypes.BUDDY_LIST);
		});

		$(htmlElements.buttonSearchMode).click(function(){
			changeMode(BuddyController.modeTypes.BUDDY_SEARCH);
		});
	};

	/**
	 * This function is called when the page is closed
	 *
	 * @public
	 * @function pageClosed
	 * @memberOf BuddyController
	 * @instance
	 */
	this.pageClosed = function(){
		$(htmlElements.list).empty();
		$(htmlElements.searchButton).unbind();
		$(htmlElements.searchInput).unbind();
		$(htmlElements.buttonShowList).unbind();
		$(htmlElements.buttonSearchMode).unbind();
	};

	var startup = function(){
		$(htmlElements.tabShowBuddies).addClass("ui-btn-active");
		changeMode(currentMode);
		downloadBuddyList();
	};

	var changeMode = function(mode){
		if(mode == BuddyController.modeTypes.BUDDY_LIST){
			$(BuddyController.pageId + " > div > h1").text(GeoCat.locale.get("buddies.show_list", "My buddies"));
			changeButtonStyleClass($(htmlElements.buttonShowList), hightlightStyleClass);
			changeButtonStyleClass($(htmlElements.buttonSearchMode), regularStyleClass);
		}
		else{
			$(BuddyController.pageId + " > div > h1").text(GeoCat.locale.get("buddies.search", "Find buddies"));
			changeButtonStyleClass($(htmlElements.buttonSearchMode), hightlightStyleClass);
			changeButtonStyleClass($(htmlElements.buttonShowList), regularStyleClass);
		}

		currentMode = mode;
	}

	var changeButtonStyleClass = function(btn, styleClass){
		btn.removeClass(regularStyleClass);
		btn.removeClass(hightlightStyleClass);
		btn.addClass(styleClass);
	};

	var downloadBuddyList = function(){
		$.ajax({
			type: "POST", url: "query/buddies.php",
			data: {
				task: "buddylist",
			},
			cache: false,
			success: function(response){
				buildBuddylist(JSON.parse(response));
			},
			error: function(xhr, status, error){
				SubstanceTheme.showNotification("<h3>Unable to dowload buddy list.</h3><p>Please try again later.</p>", 7,
						$.mobile.activePage[0], "substance-red no-shadow white");
			}
		});
	};

	var buildBuddylist = function(buddies){
		var list = $(htmlElements.list);
		list.empty();

		if(buddies.length > 0){
			for(var i = 0; i < buddies.length; i++){
				list.append(generateTitleLi(buddies[i]));
				list.append(generateContentLi(buddies[i]));
			}
		}
		else{
			list.append("<li>" + GeoCat.locale.get("buddies.empty_list", "You don't have added any buddies to your buddy list yet") + "</li>");
		}

		list.listview('refresh');
	};

	var generateTitleLi = function(buddy){
		var li = document.createElement("li");
		li.setAttribute("data-role", "list-divider");

		var spanName = document.createElement("span");
		spanName.className = "listview-left";
		spanName.textContent = buddy.username;

		li.appendChild(spanName);
		setLiAttributes(li, buddy);

		return li;
	};

	var generateContentLi = function(buddy){

		var li = document.createElement("li");

		var a = document.createElement("a");
		a.className = "li-clickable";
		a.onclick = function(){};

		if(buddy.firstname != null || buddy.lastname != null){
			var h = document.createElement("h2");
			h.textContent = (buddy.firstname != null ? buddy.firstname : "") + " " + (buddy.lastname != null ? buddy.lastname : "");
			a.appendChild(h);
		}

		var p = document.createElement("p");
		p.innerHTML = GeoCat.locale.get("buddies.last_pos_update", "Last position update") + ": " + (buddy.pos_timestamp == null ? "-" : budy.pos_timestamp);

		var del = document.createElement("a");
		del.onclick = function(){sendRemoveBuddy(buddy.username, li);}
		del.className = "ui-icon-delete";
		del.textContent = GeoCat.locale.get("buddies.remove", "Remove buddy from list");

		a.appendChild(p);

		li.appendChild(a);
		li.appendChild(del);
		setLiAttributes(li, buddy);

		return li;
	};

	var setLiAttributes = function(li, buddy){
		li.setAttribute("data-username", buddy.username);
		li.setAttribute("data-firstname", buddy.firstname);
		li.setAttribute("data-lastname", buddy.lastname);
	};

	var runBuddyFilter = function(searchKey){
		var liNodes = $(htmlElements.list)[0].childNodes;
		if(liNodes.length <= 1){return;}

		if(searchKey == ""){
			for(var i = 0; i < liNodes.length; i++){
				liNodes[i].style.display = "block";
			}
		}
		else{
			var regEx = new RegExp(searchKey);
			for(var i = 0; i < liNodes.length; i++){
				var li = liNodes[i];
				console.log(i + ": " + regEx.exec(li.getAttribute("data-username")));
				if(	regEx.exec(li.getAttribute("data-username")) != null ||
					regEx.exec(li.getAttribute("data-firstname")) != null ||
					regEx.exec(li.getAttribute("data-lastname")) != null){
					li.style.display = "block";
				}
				else{
					li.style.display = "none";
				}
			}
		}
	};

	var sendAddBuddy = function(username, element){
		// TODO: Send HTTP Request to server
	};

	var sendRemoveBuddy = function(username, element){

		// TODO: Send HTTP Request to server

		var titleLi = $(element).prev();

		$(titleLi).slideUp('fast', function(){ $(titleLi).remove(); });
		$(element).slideUp('fast', function(){ $(element).remove(); });
	};
}

BuddyController.modeTypes = {
	BUDDY_LIST: 0,
	BUDDY_SEARCH: 1
};

BuddyController.init = function(myPageId){
	BuddyController.pageId = myPageId;
	BuddyController.prototype = new PagePrototype(myPageId, function(){
		return new BuddyController();
	});
};
