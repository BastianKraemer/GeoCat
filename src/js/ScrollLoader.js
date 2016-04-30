/* GeoCat - Geocaching and -tracking application
 * Copyright (C) 2016 Bastian Kraemer
 *
 * ScrollLoader.js
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

function ScrollLoader(container, loadCallback, bottomPanelOffset){

	// Static configuration
	var loadOnNSpaceLeft = 200;

	var containerPos = container.offsetTop;
	window.onscroll = function(){onScroll()};
	var enableElementLoading = false;

	this.destroy = function(){
		window.onscroll = null;
	};

	this.setEnable = function(value){
		enableElementLoading = value;
	};

	var loadNextElements = function(){
		loadCallback(loadCompleted);
		enableElementLoading = false;
	};

	var loadCompleted = function(success){
		if(success){
			enableElementLoading = true;
		}
	};

	var onScroll = function(){
		var totalHeight = (container.offsetHeight + containerPos + bottomPanelOffset)
		var scrollBottom = (document.documentElement.scrollTop + window.innerHeight);
		var distFromBottom = totalHeight - scrollBottom;
		if(distFromBottom < 0){distFromBottom = 0;}

		if(distFromBottom < loadOnNSpaceLeft && enableElementLoading){
			loadNextElements();
		}
	};
}
