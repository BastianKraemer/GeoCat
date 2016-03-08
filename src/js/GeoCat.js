/**
 * <p>Main class of GeoCat which allows access to the local translations, the uplink and LocalCoordinateStore objects.<br>
 * <i>Consider that this class does not have any non-static functions</i></p>
 * <p><b>Note: Before you can use this class the static method "init" had to be called.</b></p>
 * @class GeoCat
 */
function GeoCat(){}

/**
 * <p>Thie field represents the current login status</p>
 * <p>The login status is represented by two properties: <i>isSignedIn</i> and <i>username</i>,
 * which are initialized with <i>"false"</i> and <i>"null"</i> by default.</p>
 *
 * @public
 * @property loginStatus {Object} Current login status
 * @memberOf GeoCat
 * @static
 */
GeoCat.loginStatus = {isSignedIn: false, username: null}; // Default value

GeoCat.localCoordStore = null;
GeoCat.uplink = null;

/**
 * Initializes the static values
 *
 * @public
 * @function init
 * @memberOf GeoCat
 * @static
 */
GeoCat.init = function(language, pathToRootDirectory){
	GeoCat.contextRoot = pathToRootDirectory;
	GeoCat.locale = new JSONLocale(language, pathToRootDirectory);
}

/**
 * Returns an instance of the GeoCat {@link LocalCoordinateStore} class
 * @return {LocalCoordinateStore} Reference to an {@link LocalCoordinateStore} object
 *
 * @public
 * @function getLocalCoordStore
 * @memberOf GeoCat
 * @static
 */
GeoCat.getLocalCoordStore = function(){
	if(GeoCat.localCoordStore == null){
		GeoCat.localCoordStore = new LocalCoordinateStore();
	}
	return GeoCat.localCoordStore;
}

/**
 * Returns an instance of the GeoCat Uplink class
 * @return {Uplink} Reference to an {@link Uplink} object
 *
 * @public
 * @function getUplink
 * @memberOf GeoCat
 * @static
 */
GeoCat.getUplink = function(){
	if(GeoCat.uplink == null){
		GeoCat.uplink = new Uplink(GeoCat.contextRoot);
	}
	return GeoCat.uplink;
}

/**
 * Sets the current challenge for further usage (the infrmation is stored in the HTML5 sessionStorage)
 * @param sessionKey {String} The session key of the challenge
 * @return {Uplink} Reference to an {@link Uplink} object
 *
 * @public
 * @function setCurrentChallenge
 * @memberOf GeoCat
 * @static
 */
GeoCat.setCurrentChallenge = function(sessionKey){
	sessionStorage.setItem("currentChallenge", sessionKey);
}

/**
 * Returns the session key of the current challenge
 * @return {Uplink} Reference to an {@link Uplink} object
 *
 * @public
 * @function getCurrentChallenge
 * @memberOf GeoCat
 * @static
 */
GeoCat.getCurrentChallenge = function(){
	var ret = sessionStorage["currentChallenge"];
	if(ret != undefined){
		return ret;
	}
	else{
		return "";
	}
}

/**
 * Removes the session key of the current challenge from the HTML5 session store
 * @return {Uplink} Reference to an {@link Uplink} object
 *
 * @public
 * @function removeCurrentChallenge
 * @memberOf GeoCat
 * @static
 */
GeoCat.removeCurrentChallenge = function(){
	sessionStorage.removeItem("currentChallenge");
}

GeoCat.login = function(username, paswd, checkbox = false, callback, pathToRoot){
	$.ajax({
		type: "POST", url: pathToRoot + "/query/login.php",
		data: {
			task: "login", 
			user: username,
			password: paswd,
			checkbox: checkbox
		},
		cache: false,
		success: function(response){
			try{
				var jsonData = JSON.parse(response);

				if(jsonData.status == "ok"){
					GeoCat.loginStatus = {isSignedIn: true, username: jsonData.username};
					$(".login-button").text(jsonData.username);
					$(".login-button").attr("onclick", "GeoCat.logout(null, '" + pathToRoot + "');");
					callback(true);
					return;
				}
			}
			catch(e){console.log("ERROR: " + e);}
			callback(false);
		},
		error: function(xhr, status, error){alert("ERROR"); callback(false);}
	});
};

GeoCat.logout = function(callback, pathToRoot){
	$.ajax({
		type: "POST", url: pathToRoot + "/query/logout.php",
		data: {logout: "true"},
		cache: false,
		success: function(response){
			if(response.toLowerCase() == "true"){
				GeoCat.loginStatus = {isSignedIn: false, username: null};
				$(".login-button").text("Login");
				$(".login-button").attr("onclick", "Dialogs.showLoginDialog('" + pathToRoot + "');");
				if(callback != null){callback(true);}
			}
			else{
				if(callback != null){callback(false);}
			}
		},
		error: function(xhr, status, error){callback(false);}
	});
};

GeoCat.createAccount = function(userName, email, pw, callback, pathToRoot){
	$.ajax({type: "POST", url: pathToRoot + "/query/account.php",
		data: { task: "create",
				username: userName,
				email: email,
				password: pw,
		},
		cache: false,
		success: function(response){
			ajaxSent = false;
			if(response.status == "ok"){
				GeoCat.loginStatus = {isSignedIn: true, username: userName};
				$(".login-button").text(userName);
				$(".login-button").attr("onclick", "GeoCat.logout(null, '" + pathToRoot + "');");
				callback(true, "");
			}
			else{
				callback(false, response["msg"]);
			}
		},
		error: function(xhr, status, error){
			callback(false, "Server unreachable.");
	}});
};

GeoCat.hasCookie = function(cname){
	var name = cname + "=";
	var ca = document.cookie.split(';');
	for(var i=0; i<ca.length; i++) {
		var c = ca[i];
		while (c.charAt(0)==' ') c = c.substring(1);
        if (c.indexOf(name) == 0){
			return true; 
		}
	}
	return false; 
}

GeoCat.deleteLoginCookie = function(cname){
	if(GeoCat.hasCookie(cname)){
		var expires = new Date();
		expires = "expires=" + expires.toUTCString();
		document.cookie = cname + '=; ' + expires + ";path=/"; 
	}
}

GeoCat.getCookie = function(cname){
	var name = cname + "=";
	var ca = document.cookie.split(';');
	for(var i=0; i<ca.length; i++) {
		var c = ca[i];
		while (c.charAt(0)==' ') c = c.substring(1);
        if (c.indexOf(name) == 0){
			GeoCat.login_cookie(c.substring(name.length, c.length)); 
		}
	}
}

GeoCat.login_cookie = function(cookieData){
	$.ajax({
		type: "POST", url: "./query/login.php",
		data: {
			task: "login_cookie", 
			cookie: cookieData
		},
		cache: false,
		success: function(response){
			try{
				var jsonData = JSON.parse(response);

				if(jsonData.status == "ok"){
					GeoCat.loginStatus = {isSignedIn: true, username: jsonData.username};
					$(".login-button").text(jsonData.username);
					$(".login-button").attr("onclick", "GeoCat.logout(null, '" + "./" + "');");
					return;
				}
			}
			catch(e){console.log("ERROR: " + e);}
		},
		error: function(xhr, status, error){alert("ERROR");}
	});
}
