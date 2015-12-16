<?php
	/**
	 * index.html - Startpage of GeoCat
	 */

	$config = require("./config/config.php");
	require_once "app/JSONLocale.php";
	require_once "app/content/header.php";
	require_once "app/SessionManager.php";

	$locale = JSONLocale::withBrowserLanguage($config);
	$session = new SessionManager();

	/**
	 * Add a tile for a jQuery Mobile Listview
	 * @param string $title Tile title
	 * @param string $text Tile text
	 * @param string $aside Tile description (will be displayed in the top right corner of the tile)
	 * @param string $target Hyperlink (href) for this tile
	 * @param string $imgsrc Path to the background image
	 */
	function addTile($title, $text, $aside, $target, $imgsrc){
		global $locale;
		print(	"<li><a href=\"" . $target . "\">" .
				"<img src=\"" . $imgsrc . "\" class=\"ui-li-thumb\">" .
				"<h2>" . $locale->get($title) . "</h2>" .
				"<p>" . $locale->get($text) . "</p>" .
				"<p class=\"ui-li-aside\">" . $locale->get($aside) . "</p>" .
				"</a></li>\n");
	}

	/**
	 * Add a (default) tile for a jQuery Mobile Listview
	 * Compared to "addTile()" this methods appends a prefix to the "tilename" parameters
	 *
	 * Prefix: "mainpage.tiles." . $tilename . ".title|text|aside"
	 * @param string $tilename The tile name - take a look at the JSON locale file.
	 * @param string $target Hyperlink (href) for this tile
	 * @param string $imgsrc Path to the background image
	 */
	function addDefaultTile($tilename, $target, $imgsrc){
		addTile("mainpage.tiles." . $tilename . ".title",
				"mainpage.tiles." . $tilename . ".text",
				"mainpage.tiles." . $tilename . ".aside",
				$target, $imgsrc);
	}
?>

<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>GeoCat</title>
	<!--<link rel="shortcut icon" href="../favicon.ico">-->
	<link rel="stylesheet" href="./css/jquery.mobile-1.4.5.min.css">
	<link rel="stylesheet" href="./css/listview-grid.css">
	<link rel="stylesheet" href="./css/style.css">

	<script src="./lib/jquery.js"></script>
	<script src="./lib/jquery.mobile-1.4.5.js"></script>
	<script src="./js/locale.js"></script>
	<script src="./js/tools.js"></script>
	<script src="./js/Uplink.js"></script>
	<script src="./js/LocalCoordinateStore.js"></script>
	<script src="./js/gpsnavigator/GPSNavigator.js"></script>
	<script src="./js/gpsnavigator/GPSNavigationController.js"></script>
	<script src="./js/gpsnavigator/GPSRadar.js"></script>
	<script src="./js/geotools.js"></script>
	<script src="./js/places/PlacesController.js"></script>
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
		var gpsNavigationController = new GPSNavigationController(localCoordStore, uplink);
		var placesController = new PlacesController(localCoordStore, loginStatus, uplink, gpsNavigationController);

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
		$(document).on("pageshow","#gpsnavigator", gpsNavigationController.onPageOpened);

		// When page "gpsnavigator" is closed
		$(document).on("pagebeforehide","#gpsnavigator", gpsNavigationController.onPageClosed);

		/* ====================================================================
			Places Eventhandling
		==================================================================== */

		// When page "Places" is opened
		$(document).on("pageshow","#Page_Places", placesController.onPageOpened);
		$(document).on("pagebeforehide","#Page_Places", placesController.onPageClosed);
	</script>

</head>
<body id="MainPageBody">

	<!--
	================================================================================
	Start page
	================================================================================
	-->
 	<div data-role="page" id="home" data-theme="b">

		<?php printHeader($config["app.name"] . " - ". $locale->get("mainpage.title"), false, false, $config, $session); ?>

		<div role="main" class="ui-content my-page">
				<ul data-role="listview" data-inset="true">
					<?php
						addDefaultTile("info", "#", ".");
						addDefaultTile("map", "#", ".");
						addDefaultTile("places", "#Page_Places", ".");
						addDefaultTile("challenges", "#", ".");
						addDefaultTile("social", "#", ".");
						addDefaultTile("navigator", "#gpsnavigator", ".");
						addDefaultTile("account", "#", ".");
					?>
				</ul>
		</div><!-- /content -->
	</div>

	<!--
	================================================================================
	Places page
	================================================================================
	-->
	<div data-role="page" id="Page_Places">
		<?php printHeader($locale->get("places.title"), true, false, $config, $session); ?>

		<div role="main" class="ui-content">
			<div class="ui-field-contain places_header">
			<p id="PlacesInformation"></p>
				<button id="Places_Prev" class="ui-btn ui-btn-inline ui-icon-arrow-l ui-btn-icon-left ui-btn-icon-notext"><?php $locale->write("places.prev_page") ?></button >
				<button id="Places_Next" class="ui-btn ui-btn-inline ui-icon-arrow-r ui-btn-icon-right ui-btn-icon-notext" style="float:right"><?php $locale->write("places.next_page") ?></button >
			</div>

			<ul id="PlacesListView" data-role="listview" data-inset="true">
				<li><span><?php $locale->write("places.empty_list") ?></span></li>
			</ul>
		</div>

		<div data-role="footer" data-id="navbar" data-position="fixed" data-tap-toggle="false" data-theme="b" style="overflow:hidden;">
			<div data-role="navbar" class="navigationbar">
				<ul>
					<li><a id="Places_Find"><?php $locale->write("places.find") ?></a></li>
					<li><a id="Places_ShowMyPlaces"><?php $locale->write("places.private_places") ?></a></li>
					<li><a id="Places_ShowPublicPlaces" data-transition="none"><?php $locale->write("places.public_places") ?></a></li>
					<li><a id="Places_newPlace"><?php $locale->write("places.new_place") ?></a></li>
				</ul>
			</div>
		</div>

		<!-- Popup to add/edit places -->
		<div id="EditPlacePopup" data-role="popup" data-theme="a" data-position-to="window" class="ui-corner-all">
			<div data-role="header" data-theme="b">
				<h3 id="EditPlacePopup_Title"><?php $locale->write("places.popup_edit.title"); ?></h3>
				<button id="EditPlacePopup_Close" class="ui-btn-right ui-btn ui-btn-inline ui-mini ui-corner-all ui-btn-icon-right ui-icon-delete ui-btn-icon-notext">Close Window</button>
				<button id="EditPlacePopup_Delete" class="ui-btn-left ui-btn ui-btn-inline ui-mini ui-corner-all">
					<?php $locale->write("places.delete"); ?>
				</button>
			</div>

			<div role="main" class="ui-content">
				<label for="EditPlacePopup_Name"><?php $locale->write("places.popup_edit.name"); ?></label>
				<input id="EditPlacePopup_Name" name="EditPlacePopup_Name" placeholder="<?php $locale->write("places.placeholder.name"); ?>" data-theme="a" type="text">

				<label for="EditPlacePopup_Desc"><?php $locale->write("places.popup_edit.description"); ?></label>
				<textarea id="EditPlacePopup_Desc" name="EditPlacePopup_Desc" placeholder="<?php $locale->write("places.placeholder.description"); ?>"></textarea>

				<table>
					<tr>
						<td>
							<label for="EditPlacePopup_Lat"><?php $locale->write("latitude"); ?>:</label>
							<input id="EditPlacePopup_Lat" name="EditPlacePopup_Lat" placeholder="50.0000">
						</td>
						<td>
							<label for="EditPlacePopup_Lon"><?php $locale->write("longitude"); ?>:</label>
							<input id="EditPlacePopup_Lon" name="EditPlacePopup_Lon" placeholder="8.0000">
						</td>
					</tr>
				</table>

			    <label>
			        <input id="EditPlacePopup_Public" name="EditPlacePopup_Public" type="checkbox"><?php $locale->write("places.popup_edit.ispublic"); ?>
			    </label>

				<button id="EditPlacePopup_Save" class="ui-btn ui-corner-all ui-shadow ui-btn-icon-left ui-icon-check"><?php $locale->write("save"); ?></button>
			</div>
		</div>
	</div>

	<!--
	================================================================================
	GPS Navigator
	================================================================================
	-->
	<div data-role="page" id="gpsnavigator">

		<?php printHeader("GPS Navigator", true, false, $config, $session); ?>

		<div id="gpsnavigator_content" role="main" class="ui-content my-page">
			<div id="CanvasFrame">
				<canvas id="NavigatorCanvas"></canvas>
			</div>
		</div>

		<div data-role="footer" data-id="navbar" data-position="fixed" data-tap-toggle="false" data-theme="b" style="overflow:hidden;">
			<div data-role="navbar" class="navigationbar">
			<ul>
					<li><a href="#home" data-transition="none">Zurück</a></li>
					<li><a id="GPSNavigator_AddCoordinate" >Ort hinzufügen</a></li>
					<li><a href="#CurrentDesitionListPanel" data-transition="none">Ziele anzeigen</a></li>
					<li><a href="#GPSNavigatorPreferencesPanel" data-rel="panel" data-transition="none">Optionen</a></li>
				</ul>
			</div>
		</div>

		<!-- GPS navigator preferences panel -->
		<div data-role="panel" id="GPSNavigatorPreferencesPanel" data-position="right" data-display="overlay">
			<h2>Optionen</h2>
			<label for="GPSNavDisableRotation">Ansicht automatisch drehen</label>
			<input id="GPSNavDisableRotation" data-role="flipswitch" name="GPSNavDisableRotation" type="checkbox" checked>

			<label for="GPSNavShowDebugInfo">Debug Informationen anzeigen</label>
			<input id="GPSNavShowDebugInfo" data-role="flipswitch" name="GPSNavShowDebugInfo" type="checkbox" checked>

			<label for="GPSNavOfflineMode">Offline Modus verwenden</label>
			<input id="GPSNavOfflineMode" data-role="flipswitch" name="GPSNavOfflineMode" type="checkbox">
		</div>

		<!-- Side panel to show destination list -->
		<div data-role="panel" id="CurrentDesitionListPanel" data-position="right" data-display="overlay">
			<ul id="CurrentDestinationList" data-role="listview" data-filter="true" data-filter-placeholder="Suchen..." data-inset="true">
			</ul>
		</div>

		<!-- Popup to add/edit a destination -->
		<div id="GPSNavDestListPopup" data-role="popup" data-theme="a" class="ui-corner-all">

			<div data-role="header" data-theme="b">
				<h3>GPS Navigator</h3>
				<button id="GPSNavDestListPopup_Close" class="ui-btn-right ui-btn ui-btn-inline ui-mini ui-corner-all ui-btn-icon-right ui-icon-delete ui-btn-icon-notext">Close Window</button>
			</div>

			<div role="main" class="ui-content">
				<label for="GPSNavDestListPopup_Name"><?php $locale->write("gpsnav.popup.name"); ?></label>
				<input id="GPSNavDestListPopup_Name" name="Destination_Name" placeholder="<?php $locale->write("gpsnav.placeholder.name"); ?>" data-theme="a" type="text">

				<label for="GPSNavDestListPopup_Desc"><?php $locale->write("gpsnav.popup.description"); ?></label>
				<textarea id="GPSNavDestListPopup_Desc" name="Destination_Description" placeholder="<?php $locale->write("gpsnav.placeholder.description"); ?>" ></textarea>

				<table>
					<tr>
						<td>
							<label for="GPSNavDestListPopup_Lat"><?php $locale->write("latitude"); ?>:</label>
							<input id="GPSNavDestListPopup_Lat" name="Destination_Latitude" placeholder="50.0000">
						</td>
						<td>
							<label for="GPSNavDestListPopup_Lon"><?php $locale->write("longitude"); ?>:</label>
							<input id="GPSNavDestListPopup_Lon" name="Destination_Longitude" placeholder="8.0000">
						</td>
					</tr>
				</table>

				<label>
			        <input id="GPSNavDestListPopup_Add2OwnPlaces" name="GPSNavDestListPopup_Add2OwnPlaces" type="checkbox"><?php $locale->write("gpsnav.popup.add_to_own_places"); ?>
			    </label>

				<button id="GPSNavDestListPopup_Save" class="ui-btn ui-corner-all ui-shadow ui-btn-icon-left ui-icon-check"><?php $locale->write("gpsnav.popup.save"); ?></button>
			</div>
		</div>
	</div>
</body>
</html>
