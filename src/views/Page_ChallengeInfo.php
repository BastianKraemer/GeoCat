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

			require_once(__DIR__ . "/../app/pages/InputTemplates.php");
?>
	<div data-role="page" id="ChallengeInfo" data-theme="a">
<?php self::printHeader("Wettbewerb", true, false, $config, $session); ?>
		<div role="main" class="ui-content">

			<p id="challengeinfo-title" class="title">...</p>
			<p id="challengeinfo-description" class="description">...</p>

			<div class="substance-vflexcontainer">
				<p class="substance-flexitem1 small center"><?php $locale->write("challenge.info.owner"); ?>:<br><span id="challengeinfo-owner">-</span></p>
				<p class="substance-flexitem1 small center"><?php $locale->write("challenge.info.type"); ?>:<br><span id="challengeinfo-type">-</span>
				<p class="substance-flexitem1 small center"><?php $locale->write("challenge.info.start"); ?>:<br><span id="challengeinfo-start-time">-</span></p>
				<p class="substance-flexitem1 small center"><?php $locale->write("challenge.info.end"); ?>:<br><span id="challengeinfo-end-time">-</span></p>
			</div>

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

				<div class="substance-footer" id="challengeinfo-footer">
					<span id="challengeinfo-add-cache" class="substance-button substance-button-animated substance-lime" style="display: none; background-image: url('./img/plus.png'"></span>
					<span id="challengeinfo-create-team" class="substance-button substance-button-animated substance-green" style="display: none; background-image: url('./img/plus.png'"></span>
					<span id="challengeinfo-start" class="substance-button substance-button-animated substance-blue" style="display: none; background-image: url('./img/pin.png'"></span>
					<span id="challengeinfo-leave" class="substance-button substance-button-animated substance-orange" style="display: none; background-image: url('./img/leave.png'"></span>
					<span id="challengeinfo-reset" class="substance-button substance-button-animated substance-purple" style="display: none; background-image: url('./img/reset.png'"></span>
					<span id="challengeinfo-enable" class="substance-button substance-button-animated substance-blue" style="display: none; background-image: url('./img/flag.png'"></span>
					<span id="challengeinfo-delete" class="substance-button substance-button-animated substance-red" style="display: none; background-image: url('./img/delete.png'"></span>
				</div>
			</div>
		</div>

		<!-- Popup: Modify challenge name and description -->
		<div id="challengeinfo-editdesc-popup" data-role="popup" data-theme="a" data-position-to="window" class="ui-corner-all">
			<div data-role="header" data-theme="b">
				<h3><?php $locale->write("challenge.info.edit.title"); ?></h3>
			</div>

			<div role="main" class="ui-content">

<?php
					InputTemplates::printTextField("challengeinfo-edit-name", false, $locale->get("challenge.create.name"), false, 63);
					InputTemplates::printTextArea("challengeinfo-edit-desc", false, $locale->get("challenge.create.desc"), false, 512);
?>

				<label>
			        <input id="challengeinfo-edit-ispublic" type="checkbox"><?php $locale->write("challenge.create.public"); ?>
			    </label>
				<hr>
				<button id="challengeinfo-editdesc-ok" class="ui-btn ui-corner-all ui-shadow"><?php $locale->write("save"); ?></button>
			</div>
		</div>

		<!-- Popup: Modify start time, end time, type, ... -->
		<div id="challengeinfo-editetc-popup" data-role="popup" data-theme="a" data-position-to="window" class="ui-corner-all">
			<div data-role="header" data-theme="b">
				<h3><?php $locale->write("challenge.info.edit.title"); ?></h3>
			</div>

			<div role="main" class="ui-content">

<?php
				InputTemplates::printDropDown("challengeinfo-edit-type", $locale->get("challenge.create.type"), false, array(
							$locale->get("challenge.create.type.default") => "0", $locale->get("challenge.create.type.ctf") => "1"
					), "default");
?>

				<div class="ui-field-contain">
					<label for="challengeinfo-edit-starttime"><?php $locale->write("challenge.create.start_time"); ?></label>
					<input id="challengeinfo-edit-starttime" type="datetime-local" placeholder="<?php print(date("Y-m-d h:m:s"))?>">
				</div>

				<div class="ui-field-contain">
					<label for="challengeinfo-edit-endtime"><?php $locale->write("challenge.create.end_time"); ?></label>
					<input id="challengeinfo-edit-endtime" type="datetime-local" placeholder="(Optional)">
				</div>

				<label>
			        <input id="challengeinfo-edit-predefteams" type="checkbox"><?php $locale->write("challenge.create.predef_teams"); ?>
			    </label>

<?php
				$values1 = array($locale->get("challenge.info.edit.unlimited_teams") => -1);
				$values2 = array();
				for($i = 1; $i < 10; $i++){
					$values1[$i] = $i;
					$values2[$i] = $i;
				}

				InputTemplates::printDropDown("challengeinfo-edit-maxteams", $locale->get("challenge.create.max_team_cnt"), false, $values1, 4);
				InputTemplates::printDropDown("challengeinfo-edit-maxteam-members", $locale->get("challenge.create.max_team_members"), false, $values2, 4);
?>
				<hr>
				<button id="challengeinfo-editetc-ok" class="ui-btn ui-corner-all ui-shadow"><?php $locale->write("save"); ?></button>
			</div>
		</div>
	</div>
<?php
		}
	}
?>
