<?php
	require_once(__DIR__ . "/../app/pages/GeoCatPage.php");

	class Page_ChallengeInfo extends GeoCatPage {

		public function printHead($config, $locale, $session, $pathToRoot){
?>
	<script type="text/javascript">
		ChallengeInfoController.init();
	</script>
<?php
		}

		public function printContent($config, $locale, $session, $pathToRoot){
?>
	<div data-role="page" id="ChallengeInfo" data-theme="a">
<?php self::printHeader("Wettbewerb", true, false, $config, $session); ?>
		<div role="main" class="ui-content">
			<p id="challengeinfo-title" class="title">...</p>
			<p id="challengeinfo-description" class="description">...</p>

			<div class="substance-vflexcontainer">
				<p class="substance-flexitem1 small center"><?php $locale->write("challenge.info.owner"); ?>:<br><span id="challengeinfo-owner">???</span></p>
				<p class="substance-flexitem1 small center"><?php $locale->write("challenge.info.type"); ?>:<br><span id="challengeinfo-type">Capture the Flag</span>
				<p class="substance-flexitem1 small center"><?php $locale->write("challenge.info.start"); ?>:<br><span id="challengeinfo-start-time">18.02.2016 12:00 Uhr</span></p>
				<p class="substance-flexitem1 small center"><?php $locale->write("challenge.info.end"); ?>:<br><span id="challengeinfo-end-time">18.02.2016 18:00 Uhr</span></p>
			</div>
			<!-- <table style="border-spacing: 4px 0px;" class="center">
				<tbody>
					<tr>
						<td><p><?php $locale->write("challenge.info.owner"); ?>:<br><span id="ChallengeInfo-owner">???</span></p></td>
						<td><p><?php $locale->write("challenge.info.type"); ?>:<br><span id="ChallengeInfo-type">Capture the Flag</span></p></td>
					</tr>
					<tr>
						<td><p><?php $locale->write("challenge.info.start"); ?>:<br><span id="ChallengeInfo-start-time">18.02.2016 12:00 Uhr</span></p></td>
						<td><p><?php $locale->write("challenge.info.end"); ?>:<br><span id="ChallengeInfo-end-time">18.02.2016 18:00 Uhr</span></p></td>
					</tr>
				</tbody>
			</table>-->

			<div class="substance-vflexcontainer">
				<div class="substance-container substance-flexitem2" style="min-width: 300px; min-height: 200px; max-height: 400px; overflow-y: auto;">
					<table class="styled-table">
						<caption><?php $locale->write("challenge.info.cachelist"); ?></caption>
						<thead>
							<tr>
								<th><?php $locale->write("challenge.info.cache"); ?></th>
								<th><?php $locale->write("challenge.info.hint"); ?></th>
								<th><?php $locale->write("challenge.info.code"); ?></th>
								<th><?php $locale->write("challenge.info.coords"); ?></th>
							</tr>
						</thead>
						<tbody id="challengeinfo-cache-list">
							<tr><td colspan=4><?php $locale->write("challenge.info.loading"); ?></td></tr>
						</tbody>
					</table>
				</div>

				<div class="substance-container substance-flexitem1" style="min-width: 300px; min-height: 200px; max-height: 400px; overflow-y: auto;">
					<table class="styled-table">
						<caption><?php $locale->write("challenge.info.teamlist"); ?></caption>
						<thead>
							<tr>
								<th></th>
								<th><?php $locale->write("challenge.info.team"); ?></th>
								<th><?php $locale->write("challenge.info.members"); ?></th>
							</tr>
						</thead>
						<tbody id="challengeinfo-team-list">
							<tr><td colspan=3><?php $locale->write("challenge.info.loading"); ?></td></tr>
						</tbody>
					</table>
				</div>
				<!--
					-> Team erstellen
					-> Challenge verlassen
					-> Go
					-> Challenge freigeben (nur owner)
					-> Challenge löschen (nur owner)
				 -->

				<div class="substance-footer" id="challengeinfo-footer">
				</div>
			</div>
		</div>
	</div>
<?php
		}
	}
?>
