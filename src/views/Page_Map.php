<?php
	/**
	 * PHP file for the map
	 * @package views
	 */
	namespace views;

	require_once(__DIR__ . "/../app/pages/GeoCatPage.php");

	class Page_Map extends \GeoCatPage {

		public function printHead($config, $locale, $session, $pathToRoot){
?>
	<script type="text/javascript">
		MapController.init("#Map");
	</script>
<?php
		}

		public function printContent($config, $locale, $session, $pathToRoot){
?>
	<div data-role="page" id="Map" data-theme="a">
<?php self::printHeader($locale->get("places.title"), "#Home", $locale, $config, $session); ?>
		<div role="main" class="ui-content" style="padding: 0">
			<div id="openlayers-map" class="map"></div>
		</div>
	</div>
<?php
		}
	}
?>
