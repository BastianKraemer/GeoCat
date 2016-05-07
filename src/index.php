<?php
/**
 * index.php - Startpage of GeoCat
 */

/**
 * This php code will build the whole application based on all views
 */
	require_once(__DIR__ . "/app/GeoCat.php");
	require_once(__DIR__ . "/app/JSONLocale.php");
	require_once(__DIR__ . "/app/SessionManager.php");
	require_once(__DIR__ . "/app/pages/GeoCatPage.php");

	/**
	 * The locale for GeoCat
	 * @var JSONLocale
	 */
	$lang = JSONLocale::getBrowserLanguage();
	$locale = new JSONLocale($lang);

	/**
	 * The current GeoCat session
	 * @var SessionManager
	 */
	$session = new SessionManager();

	require_once(__DIR__ . "/views/Page_Home.php");
	require_once(__DIR__ . "/views/Page_About.php");
	require_once(__DIR__ . "/views/Page_Places.php");
	require_once(__DIR__ . "/views/Page_GPSNavigator.php");
	require_once(__DIR__ . "/views/Page_BrowseChallenges.php");
	require_once(__DIR__ . "/views/Page_ChallengeNavigator.php");
	require_once(__DIR__ . "/views/Page_ChallengeInfo.php");
	require_once(__DIR__ . "/views/Page_CoordinateEditDialog.php");
	require_once(__DIR__ . "/views/Page_Account.php");
	require_once(__DIR__ . "/views/Page_Map.php");
	require_once(__DIR__ . "/views/Page_Buddies.php");

	use views as views;

	$allPages = array(
		new \views\Page_Home(),
		new \views\Page_About(),
		new \views\Page_Places(),
		new \views\Page_GPSNavigator(),
		new \views\Page_BrowseChallenges(),
		new \views\Page_ChallengeNavigator(),
		new \views\Page_ChallengeInfo(),
		new \views\Page_CoordinateEditDialog(),
		new \views\Page_Account(),
		new \views\Page_Map(),
		new \views\Page_Buddies()
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
	<!-- /build -->

	<!-- build:css ./css/geocat.min.css -->
	<link rel="stylesheet" href="./css/style.css">
	<link rel="stylesheet" href="./css/animations.css">
	<link rel="stylesheet" href="./css/substance.css">
	<link rel="stylesheet" href="./css/geocat-images.css" />
	<link rel="stylesheet" href="./css/tiles.css" />
	<link rel="stylesheet" href="./css/theme.css" />
	<link rel="stylesheet" href="./css/ol.css" />
	<!-- /build -->

	<!-- build:js ./lib/jquery_package.min.js -->
	<script src="./lib/jquery.min.js"></script>
	<script src="./lib/jquery.mobile-1.4.5.min.js"></script>
	<!-- /build -->

	<!-- build:js ./js/geocat.min.js -->
	<script src="./js/GeoCat.js"></script>
	<script src="./js/etc/JSONLocale.js"></script>
	<script src="./js/etc/Global.js"></script>
	<script src="./js/etc/Uplink.js"></script>
	<script src="./js/etc/LocalCoordinateStore.js"></script>
	<script src="./js/Substance.js"></script>
	<script src="./js/PagePrototype.js"></script>
	<script src="./js/Dialogs.js"></script>
	<script src="./js/ScrollLoader.js"></script>
	<script src="./lib/jquery.minicolors.min.js"></script>
	<!-- /build -->

	<!-- build:js ./js/controller.min.js -->
	<script src="./js/controller/GPSNavigationController.js"></script>
	<script src="./js/controller/BrowseChallengesController.js"></script>
	<script src="./js/controller/ChallengeNavigatorController.js"></script>
	<script src="./js/controller/ChallengeInfoController.js"></script>
	<script src="./js/controller/CoordinateEditDialogController.js"></script>
	<script src="./js/controller/PlacesController.js"></script>
	<script src="./js/controller/MapController.js"></script>
	<script src="./js/controller/BuddyController.js"></script>
	<script src="./js/controller/AboutController.js"></script>
	<script src="./js/controller/AccountController.js"></script>
	<script src="./js/etc/SafariFix.js"></script>
	<!-- /build -->

	<!-- build:js ./js/gpscat.min.js -->
	<script src="./js/gps/GPSRadar.js"></script>
	<script src="./js/gps/GPS.js"></script>
	<script src="./js/gps/GeoTools.js"></script>
	<script src="./js/gps/GPSTracker.js"></script>
	<!-- /build -->

	<script type="text/javascript">
		GeoCat.init("<?php echo $lang; ?>", "<?php echo GeoCat::getConfigKey("policy.imprint"); ?>", "<?php echo GeoCat::getConfigKey("policy.data_privacy_statement"); ?>");
		GeoCat.loginStatus = <?php $session->printLoginStatusAsJSON(); ?>;
		if(!GeoCat.loginStatus.isSignedIn && GeoCat.hasCookie("GEOCAT_LOGIN")){
			GeoCat.getCookie("GEOCAT_LOGIN");
		}
	</script>

<?php
	GeoCatPage::printAllHeaders($allPages, $locale, $session);
?>
</head>
<body>
<?php
	GeoCatPage::printAllPages($allPages, $locale, $session);
?>	<div class="geocat-footer">
<?php
	$imprint = GeoCat::getConfigKey("policy.imprint");
	if($imprint != null){
?><a href="<?php echo $imprint; ?>" target="_blank" data-ajax="false" data-rel="external"><?php $locale->write("policy.imprint"); ?></a><?php
	}
	$privacyStm = GeoCat::getConfigKey("policy.data_privacy_statement");
	if($privacyStm != null){
?><a href="<?php echo $privacyStm; ?>" target="_blank" data-ajax="false" data-rel="external"><?php $locale->write("policy.data_privacy_stm"); ?></a><?php
	}
?><span>&copy; 2016</span>
	</div>
	<div id="track-indicator" class="geocat-footer"><span>Tracking läuft...</span></div>
</body>
</html>
