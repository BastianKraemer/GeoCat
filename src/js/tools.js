
var Tools = new function(){

	/**
	 * Shows a jQuery Mobile popup
	 * @param title
	 * @param contentText
	 * @param buttonText
	 * @param onClose
	 */
	this.showPopup = function(title, contentText, buttonText, onClose){

		/* The following code is based on a example written by StackOverflow (stackoverflow.com) user Gajotres and is licensed under CC BY-SA 3.0
		 * "Creative Commons Attribution-ShareAlike 3.0 Unported", http://creativecommons.org/licenses/by-sa/3.0/)
		 *
		 * Source: http://stackoverflow.com/questions/16015810/jquery-mobile-best-way-to-create-pop-up-and-content-dynamically
		 * The code has been modified.
		 */

		//create a div for the popup
		var $popUp = $("<div/>").popup({
			dismissible : false,
			theme : "a",
			overlyaTheme : "a",
			transition : "pop"
		}).on("popupafterclose", function() {
			//remove the popup when closing
			$(this).remove();
		}).css({
			'min-width': '250px',
			'padding': '5px'
		});

		//create a title for the popup
		$("<h2/>", {
			text : title
		}).appendTo($popUp);

		//create a message for the popup
		$("<p/>", {
			text : contentText
		}).appendTo($popUp);

		//Create a submit button (fake)
		$("<a>", {
			text : buttonText
		}).buttonMarkup({
			mini: true
		}).bind("click", function() {
			$popUp.popup("close");
			if(onClose != null && onClose != undefined){
				onClose();
			}
		}).appendTo($popUp);

		$popUp.popup("open").trigger("create");
	}
}
