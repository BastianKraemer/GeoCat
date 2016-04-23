<?php
/**
 * PHP file for the GeoCat challenge information page
 * @package views
 */
	namespace views;

	use InputTemplates;

	require_once(__DIR__ . "/../app/pages/GeoCatPage.php");

	/**
	 * GeoCat challenge information page
	 */
	class Page_ChallengeInfo extends \GeoCatPage {

		/**
		 * {@inheritDoc}
		 * @param JSONLocale $locale
		 * @param SessionManager $session
		 * @see GeoCatPage::printHead()
		 */
		public function printHead($locale, $session){
?>
	<script type="text/javascript">
		ChallengeInfoController.init("#ChallengeInfo");
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

			require_once(__DIR__ . "/../app/pages/InputTemplates.php");
?>
	<div data-role="page" id="ChallengeInfo" data-theme="a">
<?php self::printHeader("Wettbewerb", "#ChallengeBrowser", $locale, $session); ?>
		<div role="main" class="ui-content">

			<p id="challengeinfo-title" class="title">...</p>
			<p id="challengeinfo-description" class="description">...</p>

			<div class="substance-horizontal-flexcontainer">
				<p class="substance-flexitem1 small center"><?php $locale->write("challenge.info.owner"); ?>:<br><span id="challengeinfo-owner">-</span></p>
				<p class="substance-flexitem1 small center"><?php $locale->write("challenge.info.type"); ?>:<br><span id="challengeinfo-type">-</span>
				<p class="substance-flexitem1 small center"><?php $locale->write("challenge.info.starttime"); ?>:<br><span id="challengeinfo-start-time">-</span></p>
				<p class="substance-flexitem1 small center"><?php $locale->write("challenge.info.endtime"); ?>:<br><span id="challengeinfo-end-time">-</span></p>
			</div>

			<div class="substance-horizontal-flexcontainer">
				<div class="substance-container substance-flexitem2" style="min-width: 300px; min-height: 200px; max-height: 400px; overflow-y: auto;">
					<table class="styled-table">
						<caption id="challengeinfo-cache-list-caption" class="clickable"><?php $locale->write("challenge.info.cachelist"); ?></caption>
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
								<th colspan=2><?php $locale->write("challenge.info.members"); ?></th>
							</tr>
						</thead>
						<tbody id="challengeinfo-team-list">
							<tr><td colspan=3><?php $locale->write("challenge.info.loading"); ?></td></tr>
						</tbody>
					</table>
				</div>

				<div id="challengeinfo-stats-container" class="substance-container substance-flexitem3" style="margin-top: 40px; min-width: 100%; min-height: 200px; max-height: 400px; overflow-y: auto;">
					<table class="styled-table">
						<caption><?php $locale->write("challenge.info.stats"); ?></caption>
						<thead>
							<tr>
								<th><?php $locale->write("challenge.info.position"); ?></th>
								<th><?php $locale->write("challenge.info.team"); ?></th>
								<th><?php $locale->write("challenge.info.caches_or_time"); ?></th>
							</tr>
						</thead>
						<tbody id="challengeinfo-stats-table">
							<tr><td colspan=3><?php $locale->write("challenge.info.loading"); ?></td></tr>
						</tbody>
					</table>
				</div>
			</div>

			<div id="challengeinfo-help-section"></div>
			<p class="substance-footer-offset"></p>

			<div class="substance-footer" id="challengeinfo-footer">
				<span id="challengeinfo-add-cache" class="substance-button substance-animated substance-lime img-plus"
					  title="<?php $locale->write("challenge.info.add_cache"); ?>" style="display: none;"></span>
				<span id="challengeinfo-create-team" class="substance-button substance-animated substance-green img-plus"
					  title="<?php $locale->write("challenge.info.create_team"); ?>" style="display: none;"></span>
				<span id="challengeinfo-start" class="substance-button substance-animated substance-blue img-pin"
					  title="<?php $locale->write("challenge.info.start_challenge"); ?>" style="display: none;"></span>
				<span id="challengeinfo-leave" class="substance-button substance-animated substance-orange img-leave"
					  title="<?php $locale->write("challenge.info.leave"); ?>" style="display: none;"></span>
				<span id="challengeinfo-reset" class="substance-button substance-animated substance-purple img-reset"
					  title="<?php $locale->write("challenge.info.reset"); ?>" style="display: none;"></span>
				<span id="challengeinfo-enable" class="substance-button substance-animated substance-blue img-flag"
					  title="<?php $locale->write("challenge.info.enable"); ?>" style="display: none;"></span>
				<span id="challengeinfo-delete" class="substance-button substance-animated substance-red img-delete"
					  title="<?php $locale->write("challenge.info.delete"); ?>" style="display: none;"></span>
			</div>

		</div>

		<div data-role="popup" id="challengeinfo-cache-popup" data-theme="a">
		        <ul data-role="listview" data-inset="true">
		            <li><a id="challengeinfo-editcache" class="ui-mini ui-icon-edit"><?php $locale->write("challenge.info.edit_cache"); ?></a></li>
		            <li><a id="challengeinfo-deletecache" class="ui-mini ui-icon-delete"><?php $locale->write("challenge.info.delete_cache"); ?></a></li>
		        </ul>
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

				<label><input id="challengeinfo-edit-ispublic" type="checkbox"><?php $locale->write("challenge.create.public"); ?></label>
				<hr>
				<button id="challengeinfo-editdesc-ok" class="ui-btn ui-corner-all ui-shadow"><?php $locale->write("save"); ?></button>
			</div>
		</div>

		<!-- popup create team -->
		<div id="create-team" data-role="popup" data-theme="a" data-position-to="window" class="ui-corner-all" style="width: 75vw;">
			<div data-role="header" data-theme="b">
				<h1><?php $locale->write("challenge.info.create_team"); ?></h1>
			</div>

			<div data-role="main" class="ui-content">
				<?php
				InputTemplates::printTextField("team-name", false, $locale->get("challenge.info.teamname"), false, 30);
				?>
				<div class="ui-field-contain">
					<label for="team-color"><?php $locale->write("challenge.info.teamcolor"); ?>:</label>
					<input id="team-color" name="team-color" type="text" value="#ff0000" maxlength="30" />
				</div>
				<div class="ui-checkbox">
					<label for="team-access-checkbox" class="ui-btn ui-corner-all ui-btn-inherit ui-btn-icon-left"><?php $locale->write("challenge.info.teamaccess"); ?></label>
					<input id="team-access-checkbox" type="checkbox" name="team-access-checkbox" data-enhanced="true">
				</div>
				<div id="team-access-password-wrap">
					<input id="team-access-password" type="text" placeholder="<?php $locale->write('challenge.info.teamaccess'); ?>" maxlength="25" />
				</div>
				<?php
				InputTemplates::printFlipswitch("team-ispredefined", $locale->get("challenge.info.ispredefined"), false, false, "team-ispredefined-container");
				?>
				<input id="team-button-create" type="button" class="ui-btn ui-corner-all ui-shadow" value="<?php $locale->write("challenge.info.team_create"); ?>" />
			</div>
		</div>

		<!-- popup join team -->
		<div id="join-team" data-role="popup" data-theme="a" data-position-to="window" data-transition="pop" class="ui-corner-all" style="width: 75vw;">
			<div data-role="header" data-theme="b">
				<h1><?php $locale->write("challenge.info.join_team"); ?></h1>
			</div>

			<div data-role="main" class="ui-content" style="text-align: center;">
				<h2><?php $locale->write("challenge.info.q.join_this_team"); ?></h2>
				<div>
					<table class="styled-table">
						<caption id="join-team-name"></caption>
						<thead>
							<tr>
								<th style="text-align: center;"><?php $locale->write("challenge.info.members"); ?>:</th>
							</tr>
						</thead>
						<tbody id="join-team-members">
							<tr><td><?php $locale->write("challenge.info.loading"); ?></td></tr>
						</tbody>
					</table>
				</div>
				<div id="join-team-wrap-password">
					<?php
					InputTemplates::printTextField("join-team-field-password", true, $locale->get("challenge.info.enterpassword"), false, 20);
					?>
				</div>
				<div class="ui-grid-a">
					<div class="ui-block-a">
						<input id="join-team-no" type="button" class="ui-btn ui-corner-all ui-shadow" value="<?php $locale->write("no"); ?>" />
					</div>
					<div class="ui-block-b">
						<input id="join-team-yes" type="button" class="ui-btn ui-corner-all ui-shadow" value="<?php $locale->write("yes"); ?>" />
					</div>
				</div>
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

				<label><input id="challengeinfo-edit-predefteams" type="checkbox"><?php $locale->write("challenge.create.predef_teams"); ?></label>

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
