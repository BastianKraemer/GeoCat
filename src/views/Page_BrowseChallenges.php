<?php
/**
 * File for the GeoCat challenge browser page
 * @package views
 */
	namespace views;

	require_once(__DIR__ . "/../app/pages/GeoCatPage.php");

	/**
	 * GeoCat challenge browser page
	 */
	class Page_BrowseChallenges extends \GeoCatPage {

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
		BrowseChallengesController.init("#ChallengeBrowser");
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
	<div data-role="page" id="ChallengeBrowser" data-theme="a">
<?php self::printHeader($locale->get("challenge.browse.title"), "#Home", $locale, $config, $session); ?>
		<div role="main" class="ui-content">

			<div id="challenge-list-tabs" data-role="tabs">
				<div data-role="navbar">
					<ul>
						<li><a id="challenge-list-public"><?php $locale->write("challenge.browse.public"); ?></a></li>
						<li><a id="challenge-list-joined"><?php $locale->write("challenge.browse.joined"); ?></a></li>
						<li><a id="challenge-list-owner"><?php $locale->write("challenge.browse.owner"); ?></a></li>
					</ul>
				</div>
			</div>

			<ul id="ChallengeListView" data-role="listview" data-inset="true">
				<li><span><?php $locale->write("challenge.browse.loading"); ?></span></li>
			</ul>

			<p class="substance-footer-offset"></p>

			<div class="substance-footer">
				<span id="challenge-create" class="substance-button substance-button-grow substance-animated substance-lime img-plus"
				   title="<?php $locale->write("challenge.browse.create_challenge"); ?>"></span>
				<span id="challenge-join-by-key" class="substance-button substance-button-grow substance-animated substance-blue img-key"
				   title="<?php $locale->write("challenge.browse.join_challenge"); ?>"></span>
			</div>
		</div>
	</div>
<?php
		}
	}
?>
