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
