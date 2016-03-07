<?php
	require_once(__DIR__ . "/../app/pages/GeoCatPage.php");

	class Page_Home extends GeoCatPage {

		/**
		 * Add a tile for a jQuery Mobile Listview
		 * @param string $title Tile title
		 * @param string $text Tile text
		 * @param string $aside Tile description (will be displayed in the top right corner of the tile)
		 * @param string $target Hyperlink (href) for this tile
		 * @param string $imgsrc Path to the background image
		 */
		private function addTile($title, $text, $aside, $target, $imgsrc, $locale){
			print(	"\t\t\t" .
					"<li><a href=\"" . $target . "\">" .
					"<img src=\"" . $imgsrc . "\" class=\"ui-li-thumb\">" .
					"<h2>" . $locale->get($title) . "</h2>" .
					"<p>" . $locale->get($text) . "</p>" .
					"<p class=\"ui-li-aside\">" . $locale->get($aside) . "</p>" .
					"</a></li>\n");
		}

		/**
		 * Adds a (default) tile for a jQuery Mobile Listview
		 * Compared to "addTile()" this methods appends a prefix to the "tilename" parameters
		 *
		 * Prefix: "mainpage.tiles." . $tilename . ".title|text|aside"
		 * @param string $tilename The tile name - take a look at the JSON locale file.
		 * @param string $target Hyperlink (href) for this tile
		 * @param string $imgsrc Path to the background image
		 */
		private function addDefaultTile($tilename, $target, $imgsrc, $locale){
			$this->addTile(	"mainpage.tiles." . $tilename . ".title",
							"mainpage.tiles." . $tilename . ".text",
							"mainpage.tiles." . $tilename . ".aside",
							$target, $imgsrc, $locale);
		}

		public function printHead($config, $locale, $session, $pathToRoot){
		}

		public function printContent($config, $locale, $session, $pathToRoot){
?>
	<div data-role="page" id="Home" data-theme="b">
<?php self::printHeader($config["app.name"] . " - ". $locale->get("mainpage.title"), false, false, $config, $session); ?>
		<div role="main" class="ui-content my-page">
			<ul data-role="listview" data-inset="true">
<?php
				$this->addDefaultTile("info", "#", ".", $locale);
				$this->addDefaultTile("map", "#", ".", $locale);
				$this->addDefaultTile("places", "#Places", ".", $locale);
				$this->addDefaultTile("challenges", "#ChallengeBrowser", ".", $locale);
				$this->addDefaultTile("social", "#", ".", $locale);
				$this->addDefaultTile("navigator", "#GPSNavigator", ".", $locale);
				$this->addDefaultTile("account", "#", ".", $locale);
?>
			</ul>
		</div>
	</div>
<?php
		}
	}
?>
