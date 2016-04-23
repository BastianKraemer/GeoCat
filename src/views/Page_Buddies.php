<?php
/**
 * File for the GeoCat buddies page
 * @package views
 */
	namespace views;

	require_once(__DIR__ . "/../app/pages/GeoCatPage.php");

	/**
	 * GeoCat buddies page
	 */
	class Page_Buddies extends \GeoCatPage {

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
		BuddyController.init("#Buddies");
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
	<div data-role="page" id="Buddies" data-theme="a">
<?php self::printHeader($locale->get("buddies.title"), "#Home", $locale, $session); ?>
		<div role="main" class="ui-content">

			<div id="buddy-search-container">
				<table>
					<tr>
						<td><input id="buddy-search-input" data-mini="true" data-corners="false" type="text" placeholder="<?php $locale->write("buddies.search_placeholder"); ?>"></td>
						<td style="width: 48px"><span id="buddy-search-confirm" class="no-shadow">Suchen</span></td>
					</tr>
				</table>
			</div>

			<ul id="buddy-list" data-role="listview" data-inset="true" data-corners="false">
			</ul>

			<div id="buddy-radar" class="gpsradar-container" style="display: none;">
				<canvas class="gpsradar"></canvas>
			</div>

			<p class="substance-footer-offset"></p>

			<div class="substance-footer">
				<span id="buddies-show-list-btn" class="substance-button substance-animated substance-lime img-public"
				   title="<?php $locale->write("buddies.show_list"); ?>"></span>
				<span id="buddies-search-mode-btn" class="substance-button substance-animated substance-blue img-find"
				   title="<?php $locale->write("buddies.search"); ?>"></span>
				<span id="start-tracking" class="substance-button substance-animated substance-blue img-pin"
				   title="<?php $locale->write("tracking.startstop"); ?>"></span>
				<span id="locate-friends" class="substance-button substance-animated substance-blue img-world"
				   title="<?php $locale->write("tracking.locate_buddies"); ?>"></span>
			</div>
		</div>
	</div>
<?php
		}
	}
?>
