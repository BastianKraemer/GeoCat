function SubstanceTheme(){}

SubstanceTheme.showNotification = function(htmlContent, durationInSeconds, container, styleClasses){
	styleClasses = (typeof styleClasses === "undefined") ? "substance-blue" : styleClasses;

	var el = document.createElement("div");
	el.setAttribute("class", "substance-notification substance-notification-animated " + styleClasses);
	el.innerHTML = htmlContent;
	container.appendChild(el);

	setTimeout(function(){
		el.style.opacity = 1;
	}, 100);

	var hideTimeout = setTimeout(function(){
		el.style.opacity = 0;
	}, (durationInSeconds - 0.6) * 1000);

	var rmTimeout = setTimeout(function(){
		container.removeChild(el);
	}, durationInSeconds * 1000);

	el.onclick = function(){
		clearTimeout(hideTimeout);
		clearTimeout(rmTimeout);
		el.style.opacity = 0;
		setTimeout(function(){
			container.removeChild(el);
		}, 600);
	}
};
