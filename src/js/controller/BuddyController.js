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
		tabShowBuddies: "#buddies-show",
		tabSearch: "#buddies-search"
	}

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
	};

	var startup = function(){
		$(htmlElements.tabShowBuddies).addClass("ui-btn-active");
		downloadBuddyList();
	}

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
	}

	var buildBuddylist = function(buddies){
		var list = $(htmlElements.list);
		list.empty();

		for(var i = 0; i < buddies.length; i++){
			list.append(generateTitleLi(buddies[i].username));
			list.append(generateContentLi(buddies[i].firstname, buddies[i].lastname, buddies[i].pos_timestamp));
		}

		list.listview('refresh');
	}

	var generateTitleLi = function(username){
		var li = document.createElement("li");
		li.className = "place-list-item";
		li.setAttribute("data-role", "list-divider");

		var spanName = document.createElement("span");
		spanName.className = "listview-left";
		spanName.textContent = username;

		li.appendChild(spanName);

		return li;
	};

	var generateContentLi = function(firstname, lastname, posTimestamp){

		var li = document.createElement("li");
		li.className = "place-list-item";

		var a = document.createElement("a");
		a.className = "li-clickable";
		a.onclick = function(){};

		if(firstname != null || lastname != null){
			var h = document.createElement("h2");
			h.textContent = (firstname != null ? firstname : "") + " " + (lastname != null ? lastname : "");
			a.appendChild(h);

		}

		var p = document.createElement("p");
		p.innerHTML = GeoCat.locale.get("buddies.last_pos_update", "Last position update") + ": " + (posTimestamp == null ? "-" : posTimestamp);

		var del = document.createElement("a");
		del.onclick = function(){};
		del.className = "ui-icon-delete";
		del.textContent = GeoCat.locale.get("buddies.remove", "Remove buddy from list");

		a.appendChild(p);

		li.appendChild(a);
		li.appendChild(del);

		return li;
	}
}

BuddyController.init = function(myPageId){
	BuddyController.prototype = new PagePrototype(myPageId, function(){
		return new BuddyController();
	});
};
