<?php
/**
 * PHP file for the GeoCat start page
 * @package views
 */
	namespace views;
	require_once(__DIR__ . "/../app/pages/GeoCatPage.php");
	require_once(__DIR__ . "/../app/GeoCat.php");

	/**
	 * GeoCat start page
	 */
	class Page_Home extends \GeoCatPage {

		/**
		 * Add a tile for a jQuery Mobile Listview
		 * @param string $title Tile title
		 * @param string $text Tile text
		 * @param string $aside Tile description (will be displayed in the top right corner of the tile)
		 * @param string $target Hyperlink (href) for this tile
		 * @param string $tileId The id for this element
		 * @param JSONLocale $locale
		 */
		private function addTile($title, $text, $aside, $target, $tileId, $locale){
?>
				<div id="<?php echo $tileId; ?>" class="substance-flexitem1 tile">
					<a href="<?php echo $target; ?>" class="content">
						<h1><?php $locale->write($title); ?></h1>
						<p><?php $locale->write($text); ?></p>
						<aside><?php $locale->write($aside); ?></aside>
					</a>
				</div>
<?php
		}

		/**
		 * Adds a (default) tile for a jQuery Mobile Listview
		 * Compared to "addTile()" this methods appends a prefix to the "tilename" parameters
		 *
		 * Prefix: "mainpage.tiles." . $tilename . ".title|text|aside"
		 * @param string $tilename The tile name - take a look at the JSON locale file.
		 * @param string $target Hyperlink (href) for this tile
		 * @param string $tileId The id for this element
		 * @param JSONLocale $locale
		 */
		private function addDefaultTile($tilename, $target, $tileId, $locale){
			$this->addTile(	"mainpage.tiles." . $tilename . ".title",
							"mainpage.tiles." . $tilename . ".text",
							"mainpage.tiles." . $tilename . ".aside",
							$target, $tileId, $locale);
		}

		/**
		 * {@inheritDoc}
		 * @param JSONLocale $locale
		 * @param SessionManager $session
		 * @see GeoCatPage::printHead()
		 */
		public function printHead($locale, $session){
		}

		/**
		 * {@inheritDoc}
		 * @param JSONLocale $locale
		 * @param SessionManager $session
		 * @see GeoCatPage::printHead()
		 */
		public function printContent($locale, $session){
?>
	<div data-role="page" id="Home" data-theme="b">
<?php
	self::printHeader(\GeoCat::getConfigKey("app.name") . " - ". $locale->get("mainpage.title"), null, $locale, $session);
?>
		<div role="main" class="ui-content my-page">
			<div class="substance-horizontal-flexcontainer mainpage-grid">
<?php
				$this->addDefaultTile("info", "#About", "infoTile", $locale);
				$this->addDefaultTile("places", "#Places", "placesTile", $locale);
				$this->addDefaultTile("challenges", "#ChallengeBrowser", "challengesTile", $locale);
				$this->addDefaultTile("navigator", "#GPSNavigator", "navTile", $locale);
				$this->addDefaultTile("social", "#Buddies", "socialTile", $locale);
				$this->addDefaultTile("map", "#Map", "mapTile", $locale);
				$this->addDefaultTile("account", "#Account", "accountTile", $locale);
?>
			</div>
		</div>
	</div>
<?php
		}
	}
?>
