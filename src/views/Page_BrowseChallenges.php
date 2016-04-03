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
				<a href="#create-challenge-popup" data-rel="popup" class="substance-button substance-button-grow substance-animated substance-lime img-plus"
				   title="<?php $locale->write("challenge.browse.create_challenge"); ?>"></a>
				<a href="#JoinChallengePopup" data-rel="popup" class="substance-button substance-button-grow substance-animated substance-blue img-key"
				   title="<?php $locale->write("challenge.browse.join_challenge"); ?>"></a>
			</div>
		</div>

		<div id="create-challenge-popup" data-role="popup" data-theme="a" data-position-to="window" class="ui-corner-all">
			<div data-role="header" data-theme="b">
				<h3><?php $locale->write("challenge.create.title"); ?></h3>
				<a href="#" data-role="button" data-rel="back" class="ui-btn-right ui-btn ui-btn-inline ui-mini ui-corner-all ui-btn-icon-right ui-icon-delete ui-btn-icon-notext">Close Window</a>
			</div>

			<div role="main" class="ui-content">
				<label for="create-challenge-input"><?php $locale->write("challenge.create.label"); ?></label>
				<input id="create-challenge-input" placeholder="<?php $locale->write("challenge.create.name"); ?>" data-theme="a" type="text">
				<p id="create-challenge-errorinfo" style="color: red;"></p>
				<button id="create-challenge-confirm" class="ui-btn ui-corner-all ui-shadow"><?php $locale->write("okay"); ?></button>
			</div>
		</div>

		<div id="JoinChallengePopup" data-role="popup" data-theme="a" data-position-to="window" class="ui-corner-all">
			<div data-role="header" data-theme="b">
				<h3><?php $locale->write("challenge.browse.joinpopup.title"); ?></h3>
				<a href="#" data-role="button" data-rel="back" class="ui-btn-right ui-btn ui-btn-inline ui-mini ui-corner-all ui-btn-icon-right ui-icon-delete ui-btn-icon-notext">Close Window</a>
			</div>

			<div role="main" class="ui-content">
				<label for="ChallengeKeyInput"><?php $locale->write("challenge.browse.joinpopup.label"); ?></label>
				<input id="ChallengeKeyInput" name="ChallengeKeyInput" placeholder="<?php $locale->write("challenge.browse.joinpopup.placeholder"); ?>" data-theme="a" type="text">
				<button id="ChallengeKeyInput-OK" class="ui-btn ui-corner-all ui-shadow"><?php $locale->write("challenge.browse.joinpopup.ok"); ?></button>
			</div>
		</div>
	</div>
<?php
		}
	}
?>
