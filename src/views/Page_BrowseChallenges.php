<?php
	require_once(__DIR__ . "/../app/pages/GeoCatPage.php");

	class Page_BrowseChallenges extends GeoCatPage {

		public function printHead($config, $locale, $session, $pathToRoot){
?>
	<script type="text/javascript">
		BrowseChallengesController.init();
	</script>
<?php
		}

		public function printContent($config, $locale, $session, $pathToRoot){
?>
	<div data-role="page" id="ChallengeBrowser" data-theme="a">
<?php self::printHeader($locale->get("challenge.browse.title"), true, false, $config, $session); ?>
		<div role="main" class="ui-content">

			<a id="my-challenges" href="#popup-my-challenges" data-rel="popup" data-position-to="window" class="ui-btn ui-corner-all ui-shadow ui-btn-inline ui-btn-a ui-state-disabled" data-transition="pop" style="float: right;"><?php $locale->write("challenge.browse.my_challenges"); ?></a>

			<div class="ui-field-contain listview-header">
				<p id="ChallengePageInformation" class="page-number-info"></p>
				<button id="Browse_Prev" class="ui-btn ui-btn-inline ui-icon-arrow-l ui-btn-icon-left ui-btn-icon-notext" title="<?php $locale->write("prev_page") ?>"><?php $locale->write("prev_page") ?></button >
				<button id="Browse_Next" class="ui-btn ui-btn-inline ui-icon-arrow-r ui-btn-icon-right ui-btn-icon-notext" style="float:right" title="<?php $locale->write("next_page") ?>"><?php $locale->write("next_page") ?></button >
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

		<!-- POPUP MY CHALLENGES -->
		<div id="popup-my-challenges" data-role="popup" data-theme="a" class="ui-corner-all" style="width: 85vw;">
			<div data-role="header" data-theme="b">
				<h1><?php $locale->write("challenge.browse.my_challenges"); ?></h1>
			</div>

			<div role="main" class="ui-content">
				<div data-role="tabs">
					<div id="my-challenges-nav" data-role="navbar">
						<ul>
							<li><a href="#enabled"><?php $locale->write("challenge.browse.enabled"); ?></a></li>
							<li><a href="#notenabled"><?php $locale->write("challenge.browse.notenabled"); ?></a></li>
							<li><a href="#joined"><?php $locale->write("challenge.browse.joined"); ?></a></li>
						</ul>
					</div>
					<div id="enabled">
						<ul id="MyChallengesEnabled" data-role="listview" data-inset="true">
							<li><span><?php $locale->write("challenge.browse.loading"); ?></span></li>
						</ul>
					</div>
					<div id="notenabled">
						<ul id="MyChallengesNotEnabled" data-role="listview" data-inset="true">
							<li><span><?php $locale->write("challenge.browse.loading"); ?></span></li>
						</ul>
					</div>
					<div id="joined">
						<ul id="MyChallengesJoined" data-role="listview" data-inset="true">
							<li><span><?php $locale->write("challenge.browse.loading"); ?></span></li>
						</ul>
					</div>
				</div>
			</div>
		</div>

	</div>
<?php
		}
	}
?>
