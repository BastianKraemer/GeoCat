<?php
	/**
	 * PHP file for the map
	 * @package views
	 */
	namespace views;

	require_once(__DIR__ . "/../app/pages/GeoCatPage.php");

	/**
	 * GeoCat map page
	 */
	class Page_Map extends \GeoCatPage {

		/**
		 * {@inheritDoc}
		 * @param JSONLocale $locale
		 * @param SessionManager $session
		 * @see GeoCatPage::printHead()
		 */
		public function printHead($locale, $session){
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
		 * @see GeoCatPage::printContent()
		 */
		public function printContent($locale, $session){
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
