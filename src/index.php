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
	<script type="text/javascript">
		var gpsnav = null;

		//Workaround: The function "getPageHeight" will return different results after the first page chage
		var pageHeightOffset = 0;
		var isFirstCreatedPage = true;
		$(document).on("pagecreate",function(event){
			if(isFirstCreatedPage){
				isFirstCreatedPage = false;
				if(window.location.hash != "#gpsnavigator"){
					pageHeightOffset = 80; // Change the pageHeightOffset, so the height will be calculated correctly
				}
			}
		});

		// When page "gpsnavigator" is opened
		$(document).on("pageshow","#gpsnavigator", function(){
			gpsnav = new GPSNavigator($("#gpsnavigator_content")[0]);
		});

		// When page "gpsnavigator" is closed
		$(document).on("pagebeforehide","#gpsnavigator", function(){
			if(gpsnav != null){
				gpsnav.destroy();
				gpsnav = null;
				pageHeightOffset = 80;
			}
		});

		function getPageHeight(){
			var screen = $.mobile.getScreenHeight();
			var header = $(".ui-header").hasClass("ui-header-fixed") ? $(".ui-header").outerHeight() - 1 : $(".ui-header").outerHeight();
			var footer = $(".ui-footer").hasClass("ui-footer-fixed") ? $(".ui-footer").outerHeight() - 1 : $(".ui-footer").outerHeight();

			var content = screen - header - footer - pageHeightOffset;
			return content;
		}
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
				<canvas id="NavigatorCanvas" width="1000" height="1000"></canvas>
			</div>
		</div>

		<div data-role="footer" data-id="navbar" data-position="fixed" data-theme="b" style="overflow:hidden;">
			<div data-role="navbar" class="navigationbar">
			<ul>
					<li><a href="#home" data-transition="none">Zurück</a></li>
					<li><a href="#" data-transition="none">Ort hinzufügen</a></li>
					<li><a href="#" data-transition="none">Ziele anzeigen</a></li>
				</ul>
			</div>
		</div>
	</div>
</body>
</html>
