<?php

	/**
	 * index.php - Startpage of GeoCat
	 */

	$config = require(__DIR__ . "/config/config.php");
	require_once(__DIR__ . "/app/JSONLocale.php");
	require_once(__DIR__ . "/app/SessionManager.php");
	require_once(__DIR__ . "/app/pages/GeoCatPage.php");

	$locale = JSONLocale::withBrowserLanguage();
	$session = new SessionManager();
	$pathToRoot = "./";

	require_once(__DIR__ . "/views/Page_Home.php");
	require_once(__DIR__ . "/views/Page_Places.php");
	require_once(__DIR__ . "/views/Page_GPSNavigator.php");
	require_once(__DIR__ . "/views/Page_BrowseChallenges.php");
	require_once(__DIR__ . "/views/Page_ChallengeNavigator.php");
	require_once(__DIR__ . "/views/Page_ChallengeInfo.php");
	require_once(__DIR__ . "/views/Page_CoordinateEditDialog.php");

	use views as views;

	$allPages = array(
		new \views\Page_Home(),
		new \views\Page_Places(),
		new \views\Page_GPSNavigator(),
		new \views\Page_BrowseChallenges(),
		new \views\Page_ChallengeNavigator(),
		new \views\Page_ChallengeInfo(),
		new \views\Page_CoordinateEditDialog()
	);
?>

<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>GeoCat</title>

	<!-- build:css ./css/jquery_package.min.css -->
	<link rel="stylesheet" href="./css/jquery.mobile-1.4.5.css">
	<link rel="stylesheet" href="./css/jquery.minicolors.css" />
	<link rel="stylesheet" href="./css/listview-grid.css" />
	<!-- /build -->

	<!-- build:css ./css/geocat.min.css -->
	<link rel="stylesheet" href="./css/style.css">
	<link rel="stylesheet" href="./css/animations.css">
	<link rel="stylesheet" href="./css/substance.css">
	<link rel="stylesheet" href="./css/geocat-images.css" />
	<!-- /build -->

	<!-- build:js ./lib/jquery_package.min.js -->
	<script src="./lib/jquery.min.js"></script>
	<script src="./lib/jquery.mobile-1.4.5.min.js"></script>
	<!-- /build -->
	<script src="./lib/jquery.minicolors.min.js"></script>

	<!-- build:js ./js/geocat.min.js -->
	<script src="./js/GeoCat.js"></script>
	<script src="./js/etc/JSONLocale.js"></script>
	<script src="./js/etc/GuiToolkit.js"></script>
	<script src="./js/etc/Uplink.js"></script>
	<script src="./js/etc/LocalCoordinateStore.js"></script>
	<script src="./js/Substance.js"></script>
	<script src="./js/PagePrototype.js"></script>
	<script src="./js/Dialogs.js"></script>
	<script src="./js/ScrollLoader.js"></script>
	<!-- /build -->

	<!-- build:js ./js/controller.min.js -->
	<script src="./js/gpsnavigator/GPSNavigationController.js"></script>
	<script src="./js/challenges/BrowseChallengesController.js"></script>
	<script src="./js/challenges/ChallengeNavigatorController.js"></script>
	<script src="./js/challenges/ChallengeInfoController.js"></script>
	<script src="./js/controller/CoordinateEditDialogController.js"></script>
	<script src="./js/controller/PlacesController.js"></script>
	<!-- /build -->

	<!-- build:js ./js/gpscat.min.js -->
	<script src="./js/gps/GPSRadar.js"></script>
	<script src="./js/gps/GPS.js"></script>
	<script src="./js/gps/GeoTools.js"></script>
	<!-- /build -->

	<script type="text/javascript">
		GeoCat.init("de", "./");
		GeoCat.loginStatus = <?php $session->printLoginStatusAsJSON(); ?>;
		if(!GeoCat.loginStatus.isSignedIn && GeoCat.hasCookie("GEOCAT_LOGIN")){
			GeoCat.getCookie("GEOCAT_LOGIN");
		}
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
