/* GeoCat - Geocaching and -tracking application
 * Copyright (C) 2016 Bastian Kraemer
 *
 * PagePrototype.js
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
 * <p>This class can be used as prototype for the controller classes of the JQM Pages.<br>
 * To use this class it is necessary that the controller classes implements the following public methods:</p>
 * <ul>
 * <li>pageOpened()
 * <li>pageClosed()
 * </ul>
 * @class PagePrototype
 * @param pageId {String} The page id of the JQuery Mobile Page
 * @param constructorCallback {function} A callback that creates a new instance of your controller class
 */
function PagePrototype(pageId, constructorCallback){

	var currentInstance = null;
	var handleEvents = true;
	var ignoreNextPageChange = false;
	var onComebackCallback = null;

	var onPageOpened = function(){
		if(handleEvents){
			if(constructorCallback != null){
				currentInstance = constructorCallback()
			}

			if(currentInstance != null){
				currentInstance.pageOpened();
			}
			else{
				GeoCat.noInstanceOfPage(pageId);
			}
		}
		else if(ignoreNextPageChange){
			ignoreNextPageChange = false;
			handleEvents = true;

			if(onComebackCallback != null){
				onComebackCallback();
				onComebackCallback = null;
			}
		}
	};

	var  onPageClosed = function(){
		if(handleEvents && currentInstance != null){
			currentInstance.pageClosed();
			currentInstance = null;
		}
	};

	/**
	 * Enable or disable events for "onPageOpened" or "onPageClosed"
	 *
	 * @public
	 * @function enableEvents
	 * @param value {Boolean}
	 * @memberOf PagePrototype
	 * @instance
	 */
	this.enableEvents = function(value){
		handleEvents = value;
	};

	this.ignoreNextEvent = function(){
		handleEvents = false;
		ignoreNextPageChange = true;
	};

	this.showSubPage = function(onComeback){
		this.ignoreNextEvent();
		onComebackCallback = onComeback;
	};

	/**
	 * Enable or disable events for "onPageOpened" or "onPageClosed"
	 *
	 * @public
	 * @function enableEvents
	 * @param value {Boolean}
	 * @memberOf PagePrototype
	 * @instance
	 */
	this.setInstance = function(obj){
		currentInstance = obj;
	}

	$(document).on("pageshow", pageId, onPageOpened);
	$(document).on("pagebeforehide", pageId, onPageClosed);
}
