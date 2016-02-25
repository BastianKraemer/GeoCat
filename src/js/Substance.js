function SubstanceTheme(){}

SubstanceTheme.previousNotification = null;

SubstanceTheme.showYesNoDialog = function(htmlContent, container, yesCallback, noCallback, styleClasses){

	var darkBg = document.createElement("div");
	darkBg.setAttribute("class", "substance-cover-dark substance-animated");

	var el = document.createElement("div");
	el.setAttribute("class", "substance-notification " + styleClasses);
	el.innerHTML = htmlContent;

	SubstanceTheme.hideCurrentNotification();
	var handler = new SubstanceNotificationHandler(darkBg);

	var btnContainer = document.createElement("div");
	btnContainer.setAttribute("class", "center");

	var yesBtn = document.createElement("span");
	yesBtn.setAttribute("class", "substance-button substance-small-button substance-lime substance-animated");
	yesBtn.style.backgroundImage= "url('./img/check.png')";
	yesBtn.onclick = function(){
		handler.hide();
		if(yesCallback != null){yesCallback();}
	}

	var noBtn = document.createElement("span");
	noBtn.setAttribute("class", "substance-button substance-small-button substance-red substance-animated");
	noBtn.style.backgroundImage= "url('./img/delete.png')";
	noBtn.onclick = function(){
		handler.hide();
		if(noCallback != null){noCallback();};
	}



	btnContainer.appendChild(noBtn);
	btnContainer.appendChild(yesBtn);
	el.appendChild(btnContainer);
	darkBg.appendChild(el);
	container.appendChild(darkBg);

	SubstanceTheme.previousNotification = handler;

	setTimeout(function(){
		var pxPerPercentOfScreen = 100 / window.outerHeight;
		el.style.bottom = (50 - (el.offsetHeight / 2) * pxPerPercentOfScreen) + "%";
		darkBg.style.opacity = 1;
	}, 100);

	return handler;
}

SubstanceTheme.showNotification = function(htmlContent, durationInSeconds, container, styleClasses){
	styleClasses = (typeof styleClasses === "undefined") ? "substance-blue" : styleClasses;

	var el = document.createElement("div");
	el.setAttribute("class", "substance-notification substance-animated " + styleClasses);
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

	SubstanceTheme.hideCurrentNotification();

	SubstanceTheme.previousNotification = handler;
	return handler;
};

SubstanceTheme.hideCurrentNotification = function(){
	if(SubstanceTheme.previousNotification != null){
		if(SubstanceTheme.previousNotification.isShown()){
			SubstanceTheme.previousNotification.hide();
		}
	}
}

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
