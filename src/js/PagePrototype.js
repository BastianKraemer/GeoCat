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

	var onPageOpened = function(){
		if(handleEvents){
			if(constructorCallback != null){
				currentInstance = constructorCallback()
			}

			if(currentInstance != null){
				currentInstance.pageOpened();
			}
		}
	};

	var  onPageClosed = function(){
		if(handleEvents){
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
