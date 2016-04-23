function SubstanceTheme(){}

SubstanceTheme.previousNotification = null;

SubstanceTheme.showYesNoDialog = function(htmlContent, container, yesCallback, noCallback, styleClasses, autoHide){

	if(autoHide != false){
		autoHide = true;
	}

	var darkBg = document.createElement("div");
	darkBg.setAttribute("class", "substance-cover-dark substance-animated");

	var el = document.createElement("div");
	el.setAttribute("class", "substance-notification " + styleClasses);
	el.style.paddingTop = "4px";

	if(typeof htmlContent === 'string' || htmlContent instanceof String){
		el.innerHTML = htmlContent;
	}
	else{
		el.appendChild(htmlContent);
	}

	SubstanceTheme.hideCurrentNotification();

	// This function is called 100ms after displaying and when the widow is resized
	var vCenterCalculateFx = function(){SubstanceTheme.calculateVerticalCenter(el);};

	var handler = new SubstanceNotificationHandler(darkBg, function(){window.removeEventListener('resize', vCenterCalculateFx);});

	var btnContainer = document.createElement("div");
	btnContainer.setAttribute("class", "center");

	var yesBtn = document.createElement("span");
	var yesButtonEnableFx = function(enable){yesBtn.setAttribute("data-disabled", enable ? 0 : 1);}

	yesBtn.setAttribute("class", "substance-button substance-small-button substance-lime substance-animated img-check");
	yesBtn.onclick = function(){
		if(yesBtn.getAttribute("data-disabled") != "1"){
			if(autoHide){
				handler.hide();
			}
			else{
				yesButtonEnableFx(false);
			}
			if(yesCallback != null){yesCallback(yesButtonEnableFx);}
		}
	}

	var noBtn = document.createElement("span");
	noBtn.setAttribute("class", "substance-button substance-small-button substance-red substance-animated img-delete");
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
		vCenterCalculateFx();
		darkBg.style.opacity = 1;
	}, 100);

	window.addEventListener('resize', vCenterCalculateFx);

	return yesBtn.onclick;
}

SubstanceTheme.showWaitScreen = function(msg, container){
	var darkBg = document.createElement("div");
	darkBg.setAttribute("class", "substance-cover-dark");

	var el = document.createElement("div");
	el.setAttribute("class", "substance-notification substance-black");
	el.innerHTML = "<p class=\"white\">" + msg + "</p>";

	darkBg.appendChild(el);
	container.appendChild(darkBg);

	SubstanceTheme.calculateVerticalCenter(el);

	SubstanceTheme.hideCurrentNotification();
	var handler = new SubstanceNotificationHandler(darkBg);
	SubstanceTheme.previousNotification = handler;
	return handler;
}

SubstanceTheme.showNotification = function(htmlContent, durationInSeconds, container, styleClasses){
	styleClasses = (typeof styleClasses === "undefined") ? "substance-blue" : styleClasses;

	var el = document.createElement("div");
	el.setAttribute("class", "substance-notification substance-animated " + styleClasses);

	if(typeof htmlContent === 'string' || htmlContent instanceof String){
		el.innerHTML = htmlContent;
	}
	else{
		el.appendChild(htmlContent);
	}
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

SubstanceTheme.calculateVerticalCenter = function(el){
	var pxPerPercentOfScreen = 100 / window.innerHeight;
	var value = (100 - ((el.offsetHeight) * pxPerPercentOfScreen)) / 2;
	if(value < 0){value = 0};
	el.style.bottom = "auto";
	el.style.top = value + "%";
};

SubstanceTheme.hideCurrentNotification = function(){
	if(SubstanceTheme.previousNotification != null){
		if(SubstanceTheme.previousNotification.isShown()){
			SubstanceTheme.previousNotification.hide();
		}
	}
}

function SubstanceNotificationHandler(element, postHideCallback){
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

		if(postHideCallback != null){
			postHideCallback();
		}
	};

	this.isShown = function(){
		return isActive;
	};
}
