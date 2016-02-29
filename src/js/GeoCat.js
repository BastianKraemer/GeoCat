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
	console.log(sessionKey);
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
