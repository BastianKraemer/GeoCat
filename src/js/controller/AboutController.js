/* GeoCat - Geocaching and -tracking application
 * Copyright (C) 2016 Bastian Kraemer
 *
 * AboutController.js
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

/**
 * This page is used to display custom content loaded via AJAX
 * @class AboutController
 */
function AboutController(){

	/**
	 * This function should be called when the page is opened
	 *
	 * @public
	 * @function pageOpened
	 * @memberOf MapController
	 * @instance
	 */
	this.pageOpened = function(){
		if(!AboutController.downloadStatus){
			var url = "locale/sites/" + AboutController.language + "_about.html";

			$.ajax({
				type: "GET", url: url,
				cache: true,
				success: function(response){
					AboutController.downloadStatus = true;
					$("#about-content").html(response);
				},
				error: function(xhr, status, error){
					SubstanceTheme.showNotification(sprintf("<h3>Cannot display page '{0}'</h3><p>Please try again later.</p>", relativeUrl),
													7, $.mobile.activePage[0], "substance-red no-shadow white");
				}
			});
		}
	};

	/**
	 * This function should be called when the page is closed
	 *
	 * @public
	 * @function pageClosed
	 * @memberOf MapController
	 * @instance
	 */
	this.pageClosed = function(){
	};
}

AboutController.language = "en";
AboutController.downloadStatus = false;

AboutController.init = function(myPageId){
	AboutController.prototype = new PagePrototype(myPageId, function(){
		return new AboutController();
	});
};
