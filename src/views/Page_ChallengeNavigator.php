<?php
	require_once(__DIR__ . "/../app/pages/GeoCatPage.php");

	class Page_ChallengeNavigator extends GeoCatPage {

		public function printHead($config, $locale, $session, $pathToRoot){
?>
	<script type="text/javascript">
		ChallengeNavigatorController.init();
	</script>
<?php
		}

		public function printContent($config, $locale, $session, $pathToRoot){
?>
	<div data-role="page" id="ChallengeNavigator" data-theme="a">
		<?php self::printHeader("Challenge Navigator", true, false, $config, $session); ?>
		<div id="challenge-navigator-content" role="main" class="ui-content my-page">
			<div class="gpsradar-container">
				<canvas id="challenge-navigator-canvas" class="gpsradar"></canvas>
			</div>
		</div>

		<div class="substance-footer">
			<a href="#challenge-navigator-stats-panel" data-rel="panel" class="substance-button substance-button-animated substance-purple" style="background-image: url('./img/stats.png')"></a>
			<span id="challenge-navigator-update-button" class="substance-button substance-button-animated substance-lime" style="background-image: url('./img/update.png'"></span>
			<span id="checkpoint-reached-button" class="substance-button substance-button-animated substance-blue" style="background-image: url('./img/flag.png'"></span>
			<a href="#challenge-navigator-coord-panel" data-rel="panel" class="substance-button substance-button-animated substance-orange" style="background-image: url('./img/list.png')"></a>
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
				<input id="challenge-navigator-autohide" type="checkbox" checked><?php $locale->write("challenge.navigator.autohide"); ?>
			</label>
		</div>

		<div id="code-input-popup" data-role="popup" data-theme="a" data-position-to="window" class="ui-corner-all">
			<div data-role="header" data-theme="b">
				<h3><?php $locale->write("challenge.navigator.codeinput.title"); ?></h3>
				<a href="#" data-role="button" data-rel="back" class="ui-btn-right ui-btn ui-btn-inline ui-mini ui-corner-all ui-btn-icon-right ui-icon-delete ui-btn-icon-notext">Close Window</a>
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
