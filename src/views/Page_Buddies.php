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
		 * @param array $config GeoCat configuration
		 * @param JSONLocale $locale
		 * @param SessionManager $session
		 * @param string $pathToRoot
		 * @see GeoCatPage::printHead()
		 */
		public function printHead($config, $locale, $session, $pathToRoot){
?>
	<script type="text/javascript">
		BuddyController.init("#Buddies");
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
	<div data-role="page" id="Buddies" data-theme="a">
<?php self::printHeader($locale->get("buddies.title"), "#Home", $locale, $config, $session); ?>
		<div role="main" class="ui-content">

			<div data-role="tabs">
				<div data-role="navbar">
					<ul>
						<li><a id="buddies-show"><?php $locale->write("buddies.show_list"); ?></a></li>
						<li><a id="buddies-find"><?php $locale->write("buddies.search"); ?></a></li>
					</ul>
				</div>
			</div>

			<ul id="buddy-list" data-role="listview" data-inset="true">
			</ul>

			<!-- <p class="substance-footer-offset"></p>

			<div class="substance-footer">
				<span id="buddies-add" class="substance-button substance-button-grow substance-animated substance-lime img-plus"
				   title="<?php $locale->write("challenge.browse.create_challenge"); ?>"></span>
				<span id="challenge-join-by-key" class="substance-button substance-button-grow substance-animated substance-blue img-key"
				   title="<?php $locale->write("challenge.browse.join_challenge"); ?>"></span>
			</div> -->
		</div>
	</div>
<?php
		}
	}
?>
