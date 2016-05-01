<?php
/**
 * File for the GeoCat about page
 * @package views
 */
	namespace views;

	require_once(__DIR__ . "/../app/pages/GeoCatPage.php");

	/**
	 * GeoCat about page
	 */
	class Page_About extends \GeoCatPage {

		/**
		 * {@inheritDoc}
		 * @param JSONLocale $locale
		 * @param SessionManager $session
		 * @see GeoCatPage::printHead()
		 */
		public function printHead($locale, $session){
?>
	<script type="text/javascript">
		AboutController.init("#About");
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
	<div data-role="page" id="About" data-theme="a">
<?php self::printHeader($locale->get("about.title"), "#Home", $locale, $session); ?>
		<div role="main" class="ui-content" style="padding: 0">
			<div id="about-content" class="no-shadow"></div>
		</div>
	</div>
<?php
		}
	}
?>
