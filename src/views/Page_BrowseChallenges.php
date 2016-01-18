<?php
	require_once(__DIR__ . "/../app/pages/GeoCatPage.php");

	class Page_BrowseChallenges extends GeoCatPage {

		public function getPageId(){return "challenge_browser";}

		protected function printContent($config, $locale, $session){

			self::printHeader($locale->get("challenge.browse.title"), true, false, $config, $session);
?>

	<div role="main" class="ui-content">
		<div class="ui-field-contain listview-header">
			<p id="ChallengePageInformation" class="page-number-info"></p>
			<button id="Browse_Prev" class="ui-btn ui-btn-inline ui-icon-arrow-l ui-btn-icon-left ui-btn-icon-notext" title="<?php $locale->write("prev_page") ?>"><?php $locale->write("prev_page") ?></button >
			<button id="Browse_Next" class="ui-btn ui-btn-inline ui-icon-arrow-r ui-btn-icon-right ui-btn-icon-notext" style="float:right" title="<?php $locale->write("next_page") ?>"><?php $locale->write("next_page") ?></button >
		</div>

		<ul id="ChallengeListView" data-role="listview" data-inset="true">
			<li><span><?php $locale->write("challenge.browse.loading"); ?>.</span></li>
		</ul>

		<div class="substance-footer">
			<a href="./sites/createchallenge.php" data-rel="external" class="substance-button substance-button-animated substance-lime" style="background-image: url('./img/plus.png')"></a>
			<a href="#JoinChallengePopup" data-rel="popup" class="substance-button substance-button-animated substance-blue" style="background-image: url('./img/key.png'"></a>
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
<?php
		}
	}
?>
