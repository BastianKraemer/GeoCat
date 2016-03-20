<?php
/**
 * File for the GeoCat places page
 * @package views
 */
	namespace views;

	require_once(__DIR__ . "/../app/pages/GeoCatPage.php");

	/**
	 * GeoCat Places page
	 */
	class Page_Places extends \GeoCatPage {

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
		PlacesController.init("#Places");
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
	<div data-role="page" id="Places" data-theme="a">
<?php self::printHeader($locale->get("places.title"), "#Home", $locale, $config, $session); ?>
		<div role="main" class="ui-content">
			<div id="places-search-container">
				<table>
					<tr>
						<td><input id="places-search-input" data-mini="true" data-corners="false" type="text"></td>
						<td style="width: 32px"><span id="places-search-confirm" class="no-shadow">Suchen</span></td>
					</tr>
				</table>
			</div>
			<ul id="places-list" data-role="listview" data-inset="true">
				<li><span><?php $locale->write("places.empty_list") ?></span></li>
			</ul>
			<p class="substance-footer-offset"></p>
		</div>

		<div class="substance-footer">
			<span id="places-add" class="substance-button substance-button-grow substance-animated substance-blue img-plus"
				  title="<?php $locale->write("places.new_place"); ?>"></span>
			<span id="places-show-private" data-rel="popup" class="substance-button substance-button-grow substance-animated substance-blue img-private"
				  title="<?php $locale->write("places.private_places"); ?>"></span>
			<span id="places-show-public" data-rel="popup" class="substance-button substance-button-grow substance-animated substance-blue img-public"
				  title="<?php $locale->write("places.public_places"); ?>"></span>
			<span id="places-find" data-rel="popup" class="substance-button substance-button-grow substance-animated substance-blue img-find"
				  title="<?php $locale->write("places.find"); ?>"></span>
		</div>
	</div>
<?php
		}
	}
?>
