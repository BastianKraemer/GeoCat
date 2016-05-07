$(document).ready(function(){
	var ua = navigator.userAgent.toLowerCase();
	if(ua.indexOf("applewebkit") != -1 || ua.indexOf("safari") != -1){
	    $("#challengeinfo-about, #challengeinfo-content").removeClass("substance-horizontal-flexcontainer").css("text-align", "center");
	    $("#challengeinfo-about p").removeClass("substance-flexitem1").css("display", "inline-block").css("width", "24%");

	    $("#challengeinfo-content").removeClass("substance-horizontal-flexcontainer");
	    $("#challengeinfo-content > div.substance-container").removeClass("substance-flexitem1 substance-flexitem2 substance-flexitem3")
	}
});
