<?php
	/**
	 * PHP file for the map
	 * @package views
	 */
	namespace views;

	require_once(__DIR__ . "/../app/pages/GeoCatPage.php");

	class Page_Map extends \GeoCatPage {

		/**
		 * {@inheritDoc}
		 * @param JSONLocale $locale
		 * @param SessionManager $session
		 * @param string $pathToRoot
		 * @see GeoCatPage::printHead()
		 */
		public function printHead($locale, $session, $pathToRoot){
?>
	<script type="text/javascript">
		MapController.init("#Map");
	</script>
<?php
		}

		/**
		 * {@inheritDoc}
		 * @param JSONLocale $locale
		 * @param SessionManager $session
		 * @param string $pathToRoot
		 * @see GeoCatPage::printContent()
		 */
		public function printContent($locale, $session, $pathToRoot){
?>
	<div data-role="page" id="Map" data-theme="a">
<?php self::printHeader($locale->get("map.title"), "#Home", $locale, $session); ?>
		<div role="main" class="ui-content" style="padding: 0">
			<div id="openlayers-map" class="map"></div>
		</div>
	</div>
<?php
		}
	}
?>
