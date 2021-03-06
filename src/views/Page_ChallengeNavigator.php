<?php
/**
 * File for the GeoCat challenge navigator
 * @package views
 */
	namespace views;

	require_once(__DIR__ . "/../app/pages/GeoCatPage.php");

	/**
	 * GeoCat challenge navigator page
	 */
	class Page_ChallengeNavigator extends \GeoCatPage {

		/**
		 * {@inheritDoc}
		 * @param JSONLocale $locale
		 * @param SessionManager $session
		 * @see GeoCatPage::printHead()
		 */
		public function printHead($locale, $session){
?>
	<script type="text/javascript">
		ChallengeNavigatorController.init("#ChallengeNavigator");
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
	<div data-role="page" id="ChallengeNavigator" data-theme="a">
<?php self::printHeader("Challenge Navigator", "#ChallengeInfo", $locale, $session); ?>
		<div id="challenge-nav-waiting"><span class="no-shadow"><?php $locale->write("challenge.navigator.waiting"); ?></span></div>
		<div id="challenge-navigator-content" role="main" class="ui-content my-page">
			<div class="gpsradar-container">
				<canvas id="challenge-navigator-canvas" class="gpsradar"></canvas>
			</div>
		</div>

		<div class="substance-footer">
			<a href="#challenge-navigator-stats-panel" data-rel="panel" class="substance-button substance-animated substance-purple img-stats"></a>
			<span id="challenge-navigator-update-button" class="substance-button substance-animated substance-lime img-update"></span>
			<span id="checkpoint-reached-button" class="substance-button substance-animated substance-blue img-flag"></span>
			<a href="#challenge-navigator-coord-panel" data-rel="panel" class="substance-button substance-animated substance-orange img-list"></a>
		</div>

		<!-- Side panel to show destination list -->
		<div data-role="panel" id="challenge-navigator-coord-panel" data-position="right" data-display="overlay" class="substance-foreground">
			<ul id="challenge-navigator-coord-list" data-role="listview">
			</ul>
		</div>

		<!-- Side panel to show stats -->
		<div data-role="panel" id="challenge-navigator-stats-panel" data-position="left" data-display="overlay" class="substance-foreground">
			<ul id="challenge-navigator-stats" data-role="listview">
			</ul>
			<p style="font-size: 14px; font-weight: 700; margin-left: -1.143em; margin-right: -1.143em; padding: 0.5em 1.1em; border-radius: 0; background-color: #e9e9e9; cursor: default;">
				<?php $locale->write("challenge.navigator.preferences"); ?>
			</p>
			<label style="font-weight: initial; margin-top: -1em; margin-left: -1em; margin-right: -1em; border-radius: 0; background-color: #ffffff; border-right-style: none;">
				<input id="challenge-navigator-autohide" type="checkbox" checked><?php $locale->write("challenge.navigator.autohide"); ?></label>
		</div>

		<div id="code-input-popup" data-role="popup" data-theme="a" data-position-to="window" class="ui-corner-all">
			<div data-role="header" data-theme="b">
				<h3><?php $locale->write("challenge.navigator.codeinput.title"); ?></h3>
				<a href="#" data-role="button" data-rel="back" class="ui-btn-right ui-btn ui-btn-inline ui-mini ui-corner-all ui-btn-icon-right ui-icon-delete ui-btn-icon-notext"><?php $locale->write("challenge.navigator.codeinput.close"); ?></a>
			</div>

			<div role="main" class="ui-content">
				<label for="checkpoint-code-input"><?php $locale->write("challenge.navigator.codeinput.label"); ?>:</label>
				<p id="checkpoint-code-input-hint" class="hint"></p>
				<input id="checkpoint-code-input" name="checkpoint-code-input" placeholder="<?php $locale->write("challenge.navigator.codeinput.placeholder"); ?>" data-theme="a" type="text">
				<button id="checkpoint-code-input-ok" class="ui-btn ui-corner-all ui-shadow"><?php $locale->write("okay"); ?></button>
			</div>
		</div>
	</div>
<?php
		}
	}
?>
