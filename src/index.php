<?php
	/**
	 * index.php - Startpage of GeoCat
	 */

	$config = require(__DIR__ . "/config/config.php");
	require_once(__DIR__ . "/app/JSONLocale.php");
	require_once(__DIR__ . "/app/SessionManager.php");
	require_once(__DIR__ . "/app/pages/GeoCatPage.php");

	$locale = JSONLocale::withBrowserLanguage($config);
	$session = new SessionManager();
	$pathToRoot = "./";

	require_once(__DIR__ . "/views/Page_Home.php");
	require_once(__DIR__ . "/views/Page_Login.php");
	require_once(__DIR__ . "/views/Page_Places.php");
	require_once(__DIR__ . "/views/Page_GPSNavigator.php");
	require_once(__DIR__ . "/views/Page_BrowseChallenges.php");
	require_once(__DIR__ . "/views/Page_ChallengeNavigator.php");

	$allPages = array(
		new Page_Home(),
		new Page_Login(),
		new Page_Places(),
		new Page_GPSNavigator(),
		new Page_BrowseChallenges(),
		new Page_ChallengeNavigator()
	);
?>

<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>GeoCat</title>

	<link rel="stylesheet" href="./css/jquery.mobile-1.4.5.min.css">
	<link rel="stylesheet" href="./css/style.css">
	<link rel="stylesheet" href="./css/animations.css">

	<!-- <## ./lib/jquery_package.min.js ##> -->
	<script src="./lib/jquery.min.js"></script>
	<script src="./lib/jquery.mobile-1.4.5.min.js"></script>
	<!-- </## ./lib/jquery_package.min.js ##> -->

	<!-- <## ./js/geocat.min.js ##> -->
	<script src="./js/locale.js"></script>
	<script src="./js/tools.js"></script>
	<script src="./js/Uplink.js"></script>
	<script src="./js/LocalCoordinateStore.js"></script>
	<script src="./js/places/PlacesController.js"></script>
	<!-- </## ./js/geocat.min.js ##> -->

	<!-- <## ./js/gpscat.min.js ##> -->
        <script src="./js/gpsnavigator/GPSNavigator.js"></script>
        <script src="./js/gpsnavigator/GPSNavigationController.js"></script>
        <script src="./js/gpsnavigator/GPSRadar.js"></script>
        <script src="./js/geotools.js"></script>
	<!-- </## ./js/gpscat.min.js ##> -->

	<script src="./js/LoginController.js"></script>
	<script src="./js/Logout.js"></script>
	<script src="./js/challenges/browse.js"></script>
	<script src="./js/challenges/ChallengeNavigatorController.js"></script>
	<script src="./js/GPS.js"></script>
	<script src="./js/Substance.js"></script>
	<link rel="stylesheet" href="./css/substance.css">

	<script type="text/javascript">

		// Global variales

		var loginStatus = <?php $session->printLoginStatusAsJSON(); ?>;

		var pages = new Object();
		var locale = new JSONLocale("de", "./");
		//Workaround: The function "getPageHeight" will return different results after the first page chage
		var pageHeightOffset = 0;
		var isFirstCreatedPage = true;

		var uplink = new Uplink("./");
		var localCoordStore = new LocalCoordinateStore();

		var gpsNavigationController = new GPSNavigationController(localCoordStore, loginStatus, uplink);
		var placesController = new PlacesController(localCoordStore, loginStatus, uplink, gpsNavigationController);
		var challengeBrowserController = new BrowseChallengesController(loginStatus, uplink);

		// Some useful (public) methods
		function getPageHeight(){
			var screen = $.mobile.getScreenHeight();
			var header = $(".ui-header").hasClass("ui-header-fixed") ? $(".ui-header").outerHeight() - 1 : $(".ui-header").outerHeight();
			var footer = $(".ui-footer").hasClass("ui-footer-fixed") ? $(".ui-footer").outerHeight() - 1 : $(".ui-footer").outerHeight();

			var content = screen - header - footer - pageHeightOffset;
			return content;
		}

		$(document).on("pagecreate",function(event){
			if(isFirstCreatedPage){
				isFirstCreatedPage = false;
				if(window.location.hash != "#gpsnavigator"){
					pageHeightOffset = 80; // Change the pageHeightOffset, so the height will be calculated correctly
				}
			}
		});

		/* ====================================================================
			 GPS Navigator Eventhandling
		 ==================================================================== */

		// When page "gpsnavigator" is opened
		$(document).on("pageshow","#GPSNavigator", gpsNavigationController.onPageOpened);

		// When page "gpsnavigator" is closed
		$(document).on("pagebeforehide","#GPSNavigator", gpsNavigationController.onPageClosed);

		/* ====================================================================
			Places Eventhandling
		==================================================================== */

		// When page "Places" is opened
		$(document).on("pageshow","#Places", placesController.onPageOpened);
		$(document).on("pagebeforehide","#Places", placesController.onPageClosed);

		/* ====================================================================
		Challenges event handling
		==================================================================== */

		// When page "Places" is opened
		$(document).on("pageshow","#ChallengeBrowser", challengeBrowserController.onPageOpened);
		$(document).on("pagebeforehide","#ChallengeBrowser", challengeBrowserController.onPageClosed);
	</script>

<?php
	GeoCatPage::printAllHeaders($allPages, $config, $locale, $session, $pathToRoot);
?>
</head>
<body>
<?php
	GeoCatPage::printAllPages($allPages, $config, $locale, $session, $pathToRoot);
?>
</body>
</html>
