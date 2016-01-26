<?php
	require_once(__DIR__ . "/../app/pages/GeoCatPage.php");

	class Page_ChallengeNavigator extends GeoCatPage {

		public function getPageId(){return "ChallengeNavigator";}

		protected function printContent($config, $locale, $session){

		self::printHeader("Challenge Navigator", true, false, $config, $session);
?>
		<div id="challenge-navigator-content" role="main" class="ui-content my-page">
			<div class="gpsradar-container">
				<canvas id="challenge-navigator-canvas" class="gpsradar"></canvas>
			</div>
		</div>

		<div class="substance-footer">
			<a href="#challenge-navigator-coord-panel" data-rel="panel" class="substance-button substance-button-animated substance-orange" style="background-image: url('./img/list.png')"></a>
			<span id="challenge-navigator-update-button" class="substance-button substance-button-animated substance-lime" style="background-image: url('./img/update.png'"></span>
			<span id="checkpoint-reached-button" class="substance-button substance-button-animated substance-blue" style="background-image: url('./img/flag.png'"></span>
		</div>

		<!-- Side panel to show destination list -->
		<div data-role="panel" id="challenge-navigator-coord-panel" data-position="right" data-display="overlay">
			<ul id="challenge-navigator-coord-list" data-role="listview">
			</ul>
		</div>

	<div id="code-input-popup" data-role="popup" data-theme="a" data-position-to="window" class="ui-corner-all">
		<div data-role="header" data-theme="b">
			<h3><?php $locale->write("challenge.navigator.codeinput.title"); ?></h3>
			<a href="#" data-role="button" data-rel="back" class="ui-btn-right ui-btn ui-btn-inline ui-mini ui-corner-all ui-btn-icon-right ui-icon-delete ui-btn-icon-notext">Close Window</a>
		</div>

		<div role="main" class="ui-content">
			<label for="checkpoint-code-input"><?php $locale->write("challenge.navigator.codeinput.label"); ?>:</label>
			<input id="checkpoint-code-input" name="checkpoint-code-input" placeholder="<?php $locale->write("challenge.navigator.codeinput.placeholder"); ?>" data-theme="a" type="text">
			<button id="checkpoint-code-input-ok" class="ui-btn ui-corner-all ui-shadow"><?php $locale->write("okay"); ?></button>
		</div>
	</div>
<?php
		}
	}
?>
