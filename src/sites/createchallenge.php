<?php

	/**
	 * File signup.php
	 */

	$config = require(__DIR__ . "/../config/config.php");
	require_once(__DIR__ . "/../app/JSONLocale.php");
	require_once(__DIR__ . "/../app/pages/GeoCatPage.php");
	require_once(__DIR__ . "/../app/SessionManager.php");
	require_once(__DIR__ . "/../app/pages/InputTemplates.php");
	$locale = JSONLocale::withBrowserLanguage($config);
	$session = new SessionManager();

	/**
	 * @ignore
	 */
	function printInputTextField($nameAndId, $isPasswordField, $labelTranslationKey, $isRequiredField, $maxCharacters){
		global $locale;

		print("<div class=\"ui-field-contain\">\n" .
				"<label for=\"" . $nameAndId . "\">" . $locale->get($labelTranslationKey) . ":" . ($isRequiredField ? " <span class=\"required\">*</span>" : "") . "</label>\n" .
				"<input id=\"" . $nameAndId . "\" name=\"" . $nameAndId . "\" type=\"" . ($isPasswordField ? "password" : "text") . "\" value=\"\" placeholder=\"" . $locale->get($labelTranslationKey) . "\" maxlength=" . $maxCharacters . ">\n" .
			  "</div>");
	}

	/**
	 * @ignore
	 */
	function printInputTextArea($nameAndId, $isPasswordField, $labelTranslationKey, $isRequiredField, $maxCharacters){
		global $locale;

		print("<div class=\"ui-field-contain\">\n" .
				"<label for=\"" . $nameAndId . "\">" . $locale->get($labelTranslationKey) . ":" . ($isRequiredField ? " <span class=\"required\">*</span>" : "") . "</label>\n" .
				"<textarea id=\"" . $nameAndId . "\" name=\"" . $nameAndId . "\" maxlength=" . $maxCharacters . ($isRequiredField ? " required" : "") . "></textarea>" .
			  "</div>");
	}

	/**
	 * @ignore
	 */
	function printInputDropDown($nameAndId, $labelTranslationKey, $isRequiredField, $maxCharacters){
		global $locale;

		print("<div class=\"ui-field-contain\">\n" .
				"<label for=\"" . $nameAndId . "\">" . $locale->get($labelTranslationKey) . ":" . ($isRequiredField ? " <span class=\"required\">*</span>" : "") . "</label>\n" .
				"<textarea id=\"" . $nameAndId . "\" name=\"" . $nameAndId . "\" maxlength=" . $maxCharacters . ($isRequiredField ? " required" : "") . "></textarea>" .
				"</div>");
	}
?>

<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Create Account</title>
	<!--<link rel="shortcut icon" href="../favicon.ico">-->
	<link rel="stylesheet" href="../css/jquery.mobile-1.4.5.min.css">
	<link rel="stylesheet" href="../css/listview-grid.css">
	<link rel="stylesheet" href="../css/style.css">
	<link rel="stylesheet" href="../css/animations.css">

	<!-- <## ../lib/jquery_package.min.js ##> -->
	<script src="../lib/jquery.min.js"></script>
	<script src="../lib/jquery.mobile-1.4.5.min.js"></script>
	<!-- </## ../lib/jquery_package.min.js ##> -->

	<script src="../js/tools.js"></script>
	<script src="../js/Logout.js"></script>
	<script src="../js/LoginController.js"></script>

	<script type="text/javascript">
		var ajaxSent = false;

		LoginController.init("../");

		$(document).on("pagecreate", function(event){

			$("#CreateAccount").click(function(){
				var name = $("#form-cc-name").val();
				var desc = $("#form-cc-desc").val();
				var type = $("#form-cc-type").val();
				var isPublic = $("#form-cc-public").is(":checked") ? 1 : 0;
				var starttime = $("#form-cc-start").val();
				var endtime = $("#form-cc-end").val();
				var predefTeams = $("#form-cc-predef-teams").is(":checked") ? 1 : 0;
				var maxTeams = $("#form-cc-max-teams").val();
				var maxMebersPerTeam = $("#form-cc-max-team-members").val();

				//Send Request and replace the 'T's in the timestamp
				sendCreateChallengeRequest(name, desc, type, isPublic, starttime.replace("T", " "), endtime.replace("T", " "), predefTeams, maxTeams, maxMebersPerTeam);
			});
		});

		function sendCreateChallengeRequest(challengeName, description, challengeType, isPublic, starttime, endtime, predefTeams, maxTeams, maxMembersPerTeam){
			if(!ajaxSent){
				ajaxSent = true;

				var mydata = {
					task: "create_challenge",
					name: challengeName,
					desc: description,
					type: challengeType,
					is_public: isPublic,
					predefined_teams: predefTeams,
					max_teams: maxTeams,
					max_team_members: maxMembersPerTeam
				};

				if(starttime != ""){mydata["start_time"] = starttime;}
				if(endtime != ""){mydata["end_time"] = endtime;}

				$.ajax({type: "POST", url: "../query/challenge.php",
					data: mydata,
					cache: false,
					success: function(response){
						ajaxSent = false;
						result = JSON.parse(response);
						if(result.status == "ok"){
							Tools.showPopup(<?php $locale->writeQuoted("challenge.create.success.title"); ?>, <?php $locale->writeQuoted("challenge.create.success.msg"); ?>, <?php $locale->writeQuoted("okay"); ?>, null);
						}
						else{
							Tools.showPopup("Unable to create challenge", result.msg, <?php $locale->writeQuoted("okay"); ?>, null);
						}
					},
					error: function(xhr, status, error){
						ajaxSent = false;
						Tools.showPopup("Error", "Ajax request failed: " + error, <?php $locale->writeQuoted("okay"); ?>, null);
				}});
			}
		}
	</script>
</head>
<body>

	<!--
	================================================================================
	Page "create account"
	================================================================================
	-->
	<div data-role="page" id="create_challenge" data-theme="a" >
		<?php GeoCatPage::printHeader($locale->get("challenge.create.title"), true, true, $config, $session); ?>

		<div role="main" class="ui-content my-page">
			<form id="form-create-challenge" name="form-create-challenge">
				<h3><?php $locale->write("challenge.create.headline"); ?></h3>

				<?php
					InputTemplates::printTextField("form-cc-name", false, $locale->get("challenge.create.name"), true, 63);
					InputTemplates::printTextArea("form-cc-desc", false, $locale->get("challenge.create.desc"), true, 512);
					InputTemplates::printDropDown("form-cc-type", $locale->get("challenge.create.type"), true, array(
							$locale->get("challenge.create.type.default") => "default", $locale->get("challenge.create.type.ctf") => "ctf"
					), "default");


					print("<hr />\n");
					InputTemplates::printFlipswitch("form-cc-public", $locale->get("challenge.create.public"), false, false);
				?>

				<div class="ui-field-contain">
					<label for="form-cc-start"><?php $locale->write("challenge.create.start_time"); ?></label>
					<input id="form-cc-start" type="datetime-local" placeholder="<?php print(date("Y-m-d h:m:s"))?>">
				</div>

				<div class="ui-field-contain">
					<label for="form-cc-end"><?php $locale->write("challenge.create.end_time"); ?></label>
					<input id="form-cc-end" type="datetime-local" placeholder="(Optional)">
				</div>

				<hr />

				<?php
					InputTemplates::printFlipswitch("form-cc-predef-teams", $locale->get("challenge.create.predef_teams"), false, false);

					$values = array();
					for($i = 1; $i < 10; $i++){
						$values[$i] = $i;
					}

					InputTemplates::printDropDown("form-cc-max-teams", $locale->get("challenge.create.max_team_cnt"), false, $values, 4);
					InputTemplates::printDropDown("form-cc-max-team-members", $locale->get("challenge.create.max_team_members"), false, $values, 4);


				?>

				<div class="ui-grid-a ui-responsive">
						<input id="CreateAccount" type="button" value="<?php $locale->write("challenge.create.confirm"); ?>">
				</div>
			</form>
		</div>
	</div>
</body>
</html>
