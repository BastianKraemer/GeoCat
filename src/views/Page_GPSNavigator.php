<?php
/**
 * File for the GeoCat 'GPS Navigator' page
 * @package views
 */
	namespace views;

	require_once(__DIR__ . "/../app/pages/GeoCatPage.php");

	/**
	 * GeoCat GPSNavigator page
	 */
	class Page_GPSNavigator extends \GeoCatPage {

		/**
		 * {@inheritDoc}
		 * @param array $config GeoCat configuration
		 * @param JSONLocale $locale
		 * @param SessionManager $session
		 * @param string $pathToRoot
		 * @see GeoCatPage::printHead()
		 */
		public function printHead($config, $locale, $session, $pathToRoot){
?>
	<script type="text/javascript">
		GPSNavigationController.init("#GPSNavigator");
	</script>
<?php
		}

		/**
		 * {@inheritDoc}
		 * @param array $config GeoCat configuration
		 * @param JSONLocale $locale
		 * @param SessionManager $session
		 * @param string $pathToRoot
		 * @see GeoCatPage::printContent()
		 */
		public function printContent($config, $locale, $session, $pathToRoot){
?>
	<div data-role="page" id="GPSNavigator" data-theme="a">
<?php self::printHeader($locale->get("gpsnav.title"), "#Home", $locale, $config, $session); ?>
		<div id="gpsnav-content" role="main" class="ui-content my-page">
			<div class="gpsradar-container">
				<canvas id="gpsnav-canvas" class="gpsradar"></canvas>
			</div>
		</div>

		<div data-role="footer" data-id="navbar" data-position="fixed" data-tap-toggle="false" data-theme="b" style="overflow:hidden;">
			<div class="substance-footer">
				<span id="gpsnavigagtor-add-place" class="substance-button substance-button-grow substance-animated substance-lime img-plus"
				   title="<?php $locale->write("gpsnav.add_coord"); ?>"></span>
				<span id="gpsnavigagtor-show-map" class="substance-button substance-button-grow substance-animated substance-blue img-pin"
				   title="<?php $locale->write("gpsnav.show_map"); ?>"></span>
				<a href="#gpsnav-destination-list-panel" data-rel="popup" class="substance-button substance-button-grow substance-animated substance-orange img-list"
				   title="<?php $locale->write("gpsnav.show_dest_list"); ?>"></a>
			</div>
		</div>

		<!-- Side panel to show destination list -->
		<div data-role="panel" id="gpsnav-destination-list-panel" data-position="right" data-display="overlay" class="substance-foreground">
			<ul id="gpsnav-destination-list" data-role="listview">
			</ul>
		</div>
	</div>
<?php
		}
	}
?>
