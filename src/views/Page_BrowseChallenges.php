<?php
	require_once(__DIR__ . "/../app/pages/GeoCatPage.php");

	class Page_BrowseChallenges extends GeoCatPage {

		public function getPageId(){return "challenge_browser";}

		protected function printContent($config, $locale, $session){

			self::printHeader("Challenges", true, false, $config, $session);
?>

	<div role="main" class="ui-content">
		<div class="ui-field-contain listview-header">
			<p id="ChallengePageInformation" class="page-number-info"></p>
			<button id="Browse_Prev" class="ui-btn ui-btn-inline ui-icon-arrow-l ui-btn-icon-left ui-btn-icon-notext"><?php $locale->write("places.prev_page") ?></button >
			<button id="Browse_Next" class="ui-btn ui-btn-inline ui-icon-arrow-r ui-btn-icon-right ui-btn-icon-notext" style="float:right"><?php $locale->write("places.next_page") ?></button >
		</div>

		<ul id="ChallengeListView" data-role="listview" data-inset="true">
			<li><span>No challenges found.</span></li>
		</ul>
	</div>
<?php
		}
	}
?>
