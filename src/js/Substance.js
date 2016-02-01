function SubstanceTheme(){}

SubstanceTheme.previousNotification = null;

SubstanceTheme.showNotification = function(htmlContent, durationInSeconds, container, styleClasses){
	styleClasses = (typeof styleClasses === "undefined") ? "substance-blue" : styleClasses;

	var el = document.createElement("div");
	el.setAttribute("class", "substance-notification substance-notification-animated " + styleClasses);
	el.innerHTML = htmlContent;
	container.appendChild(el);

	setTimeout(function(){
		el.style.opacity = 1;
	}, 100);

	var handler = new SubstanceNotificationHandler(el);

	if(durationInSeconds > 0){
		hideTimeout = setTimeout(function(){
			handler.hide();
		}, (durationInSeconds - 0.6) * 1000);
	}

	el.onclick = function(){
		handler.hide();
	};

	if(SubstanceTheme.previousNotification != null){
		if(SubstanceTheme.previousNotification.isShown()){
			SubstanceTheme.previousNotification.hide();
		}
	}

	SubstanceTheme.previousNotification = handler;
	return handler;
};

function SubstanceNotificationHandler(element){
	var htmlElement = element;
	var isActive = true;
	this.hide = function(){
		if(isActive){
			isActive = false;
			htmlElement.style.opacity = 0;
			setTimeout(function(){
				htmlElement.parentElement.removeChild(htmlElement);
			}, 600);
		}
	};

	this.isShown = function(){
		return isActive;
	};
}
