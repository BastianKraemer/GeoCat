<?php
	$config = require("./config/config.php");
	$translations = json_decode(file_get_contents("./locale/de.json"), true);
	
	function getTranslation($key){
		global $translations;
		return array_key_exists($key, $translations) ? $translations[$key] : "Undefined key: " . $key;
	}

	function printTranslation($key){
		global $translations;
		print(array_key_exists($key, $translations) ? $translations[$key] : "Undefined key: " . $key);
	}

	function addTile($title, $text, $aside, $target, $imgsrc){
		print(	"<li><a href=\"" . $target . "\">" .
				"<img src=\"" . $imgsrc . "\" class=\"ui-li-thumb\">" .
				"<h2>" . getTranslation($title) . "</h2>" .
				"<p>" . getTranslation($text) . "</p>" .
				"<p class=\"ui-li-aside\">" . getTranslation($aside) . "</p>" .
				"</a></li>\n");
	}

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

	<script src="./js/jquery.js"></script>
	<script src="./js/jquery.mobile-1.4.5.js"></script>
	<script src="./js/gpsnavigator.js"></script>
	<script src="./js/geotools.js"></script>
	<script src="./js/gpsnavigator/GPSNavigationController.js"></script>
	<script src="./js/gpsnavigator/GPSRadar.js"></script>
	<script type="text/javascript">

		// Global variales
		var pages = new Object();

		//Workaround: The function "getPageHeight" will return different results after the first page chage
		var pageHeightOffset = 0;
		var isFirstCreatedPage = true;

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
		$(document).on("pageshow","#gpsnavigator", GPSNavigationController.onPageOpened);

		// When page "gpsnavigator" is closed
		$(document).on("pagebeforehide","#gpsnavigator", GPSNavigationController.onPageClosed);
	</script>

</head>
<body id="MainPageBody">

	<!--
	================================================================================
	Start page
	================================================================================
	-->
 	<div data-role="page" id="home" data-theme="b">

	<div data-role="header" data-id="page_header">
		<?php print("<h1>" . $config["app.name"] . " - ". getTranslation("mainpage.title") . "</h1>"); ?>
		<a href="#" class="ui-btn-right ui-btn ui-btn-inline ui-mini ui-corner-all ui-btn-icon-right ui-icon-user">Login</a>
	</div>

		<div role="main" class="ui-content my-page">
				<ul data-role="listview" data-inset="true">
					<?php 
						addDefaultTile("info", "#", ".");
						addDefaultTile("map", "#", ".");
						addDefaultTile("places", "#", ".");
						addDefaultTile("challenges", "#", ".");
						addDefaultTile("social", "#", ".");
						addDefaultTile("navigator", "#gpsnavigator", ".");
						addDefaultTile("account", "#", ".");
					?>
				</ul>
		</div><!-- /content -->

		<?php
			require("./app/main/navigation.php");
		?>
	</div>

	<!--
	================================================================================
	Places page
	================================================================================
	-->
	<div data-role="page" id="places">
		<div data-role="header" data-id="page_header" data-theme="b">
			<?php print("<h1>" . getTranslation("places.title") . "</h1>"); ?>
		</div>

		<ul data-role="listview" data-filter="true" data-filter-placeholder="<?php printTranslation("places.find") ?>" data-inset="true">
		</ul>

		<?php
			require("./app/main/navigation.php");
		?>
	</div>

	<!--
	================================================================================
	GPS Navigator
	================================================================================
	-->
	<div data-role="page" id="gpsnavigator">

		<div data-role="header" data-id="page_header" data-theme="b">
			<h1>GPS Navigator</h1>
		</div>

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
		<div id="GPSNavDestListPopup" data-role="popup" data-theme="a" class="ui-corner-all" style="width: 80%;">
			<div style="padding:10px 20px;">
				<h3>GPS Navigator</h3>
				<label for="GPSNavDestListPopup_Name" class="ui-hidden-accessible">Name:</label>
				<input id="GPSNavDestListPopup_Name" name="Destination_Name" placeholder="Name" data-theme="a" type="text">

				<label for="GPSNavDestListPopup_Desc">Description:</label>
				<textarea id="GPSNavDestListPopup_Desc" name="Destination_Description" placeholder="Description" ></textarea>

				<label for="GPSNavDestListPopup_Lat">Latitude:</label>
				<input id="GPSNavDestListPopup_Lat" name="Destination_Latitude" placeholder="50.0000">

				<label for="GPSNavDestListPopup_Lon">Longitude:</label>
				<input id="GPSNavDestListPopup_Lon" name="Destination_Longitude" placeholder="8.0000">
				<a id="GPSNavDestListPopup_Save" class="ui-btn ui-corner-all ui-shadow ui-btn-b ui-btn-icon-left ui-icon-check">Save</a>
			</div>
		</div>
	</div>
</body>
</html>
