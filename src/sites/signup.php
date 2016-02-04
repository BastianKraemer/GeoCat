<?php

	/**
	 * File signup.php
	 */

	$config = require(__DIR__ . "/../config/config.php");
	require_once(__DIR__ . "/../app/JSONLocale.php");
	require_once(__DIR__ . "/../app/pages/GeoCatPage.php");
	require_once(__DIR__ . "/../app/SessionManager.php");
	$locale = JSONLocale::withBrowserLanguage();
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
?>

<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Create Account</title>
	<!--<link rel="shortcut icon" href="../favicon.ico">-->
	<link rel="stylesheet" href="../css/jquery.mobile-1.4.5.min.css">
	<link rel="stylesheet" href="../css/style.css">

	<!-- <## ../lib/jquery_package.min.js ##> -->
	<script src="../lib/jquery.min.js"></script>
	<script src="../lib/jquery.mobile-1.4.5.min.js"></script>
	<!-- </## ../lib/jquery_package.min.js ##> -->

	<!-- <## ../js/geocat.min.js ##> -->
	<script src="../js/GeoCat.js"></script>
	<script src="../js/etc/JSONLocale.js"></script>
	<script src="../js/etc/GuiToolkit.js"></script>
	<script src="../js/etc/Uplink.js"></script>
	<script src="../js/etc/LocalCoordinateStore.js"></script>
	<script src="../js/Logout.js"></script>
	<script src="../js/Substance.js"></script>
	<!-- </## ../js/geocat.min.js ##> -->

	<script type="text/javascript">
		var ajaxSent = false;

		$(document).on("pagecreate", function(event){

			$("#CreateAccountButton").click(function(){
				var usrname = $("#Form_CreateAccount_username").val();
				var email = $("#Form_CreateAccount_email").val();
				var pw1 = $("#Form_CreateAccount_password").val();
				var pw2 = $("#Form_CreateAccount_password_confirm").val();

				if(usrname != "" && email != "" && pw1 != "" && pw2 != ""){
					if(pw1 == pw2){
						sendCreateAccountRequest("create",
									usrname,
									pw1,
									email,
									$("#Form_CreateAccount_firstname").val(),
									$("#Form_CreateAccount_lastname").val(),
									$("Form_CreateAccount_public_email").is(":checked") ? 1 : 0);
					}
					else{
						GuiToolkit.showPopup(<?php $locale->writeQuoted("notification"); ?>, <?php $locale->writeQuoted("signup.passwords_not_equal"); ?>,  <?php $locale->writeQuoted("okay"); ?>, null);
					}
				}
				else{
					GuiToolkit.showPopup(<?php $locale->writeQuoted("notification"); ?>, <?php $locale->writeQuoted("signup.fill_required_fields"); ?>, <?php $locale->writeQuoted("okay"); ?>, null);
				}
			});
		});

		function sendCreateAccountRequest(command, user, pw, emailAddr, firstName, lastName, emailIsPublic){
			if(!ajaxSent){
				ajaxSent = true;

				$.ajax({type: "POST", url: "../query/account.php",
					data: { task: command,
							username: user,
							password: pw,
							email: emailAddr,
							firstname: firstName,
							lastname: lastName,
							public_email: emailIsPublic
					},
					cache: false,
					success: function(response){
						ajaxSent = false;
						if(response.status == "ok"){
							GuiToolkit.showPopup(<?php $locale->writeQuoted("signup.account_created"); ?>, <?php $locale->writeQuoted("signup.account_created_msg"); ?>, <?php $locale->writeQuoted("okay"); ?>,
											function(){location.href="../index.php";});
						}
						else{
							GuiToolkit.showPopup("Error", response["msg"], <?php $locale->writeQuoted("okay"); ?>, null);
						}
					},
					error: function(xhr, status, error){
						ajaxSent = false;
						GuiToolkit.showPopup("Error", "Ajax request failed: " + error, <?php $locale->writeQuoted("okay"); ?>, null);
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
	<div data-role="page" id="CreateAccount" data-theme="a" >
		<?php GeoCatPage::printHeader($config["app.name"] . " - ". $locale->get("createaccount.title"), true, true, $config, $session); ?>

		<div role="main" class="ui-content my-page">
			<form id="form-signup" name="form-signup">
				<h3><?php $locale->write("createaccount.headline"); ?></h3>

				<?php
					printInputTextField("Form_CreateAccount_username", false, "createaccount.username", true, 63);
					printInputTextField("Form_CreateAccount_password", true, "createaccount.password", true, 63);
					printInputTextField("Form_CreateAccount_password_confirm", true, "createaccount.password_confirm", true, 63);
					printInputTextField("Form_CreateAccount_email", false, "createaccount.email", true, 63);
				?>

				<hr />

				<?php
					printInputTextField("Form_CreateAccount_firstname", false, "createaccount.firstname", false, 63);
					printInputTextField("Form_CreateAccount_lastname", false, "createaccount.lastname", false, 63);
				?>

				<div class="ui-field-contain">
					<label for="Form_CreateAccount_public_email"><?php $locale->write("createaccount.public_email"); ?></label>
					<input id="Form_CreateAccount_public_email" data-role="flipswitch" name="Form_CreateAccount_public_email" type="checkbox">
				</div>

				<div class="ui-grid-a ui-responsive">
					<div class="ui-block-a">
						<a id="login-back" href="./../../" role="button" class="ui-btn ui-corner-all"><?php $locale->write("back"); ?></a>
					</div>
					<div class="ui-block-b">
						<input id="CreateAccountButton" type="button" value="<?php $locale->write("createaccount.confirm"); ?>">
					</div>
				</div>
			</form>
		</div>
	</div>
</body>
</html>
