
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
		var p = $("<p/>", {
			text : contentText
		}).appendTo($popUp);
		p.html(p.html().replace(/\\n/g, "<br />"));

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
	};

	this.sprintf = function(txt, args){
		return txt.replace(/{(\d+)}/g, function(match, number){
				return typeof args[number] != 'undefined' ? args[number] : match;
			});
	 };
}

function getCookie(cname) {
    var name = cname + "=";
    var ca = document.cookie.split(';');
    for(var i=0; i<ca.length; i++) {
        var c = ca[i];
        while (c.charAt(0)==' ') c = c.substring(1);
        if (c.indexOf(name) == 0) return c.substring(name.length,c.length);
    }
    return "";
}
