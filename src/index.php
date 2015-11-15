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
				"#", ".");
	}
?>

<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>test</title>
	<!--<link rel="shortcut icon" href="../favicon.ico">-->
	<link rel="stylesheet" href="./css/jquery.mobile-1.4.5.min.css">
	<link rel="stylesheet" href="./css/listview-grid.css">
	<link rel="stylesheet" href="./css/style.css">

	<script src="./js/jquery.js"></script>
	<script src="./js/jquery.mobile-1.4.5.js"></script>

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
						addDefaultTile("navigator", "#", ".");
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
</body>
</html>
