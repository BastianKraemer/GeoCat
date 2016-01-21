<?php
	require_once(__DIR__ . "/../app/pages/GeoCatPage.php");

	class Page_Login extends GeoCatPage {

		public function getPageId(){return "login";}
		public function getPageTheme(){return "a";}

		protected function getPageHeaderAttributes(){
			return "data-dialog=\"true\"";
		}

		protected function printContent($config, $locale, $session){
?>
			<div data-role="header" data-theme="b">
				<h1 class="ui-title">Login</h1>
			</div>

			<div role="main" class="ui-content">
				<form id="form-login" action="./query/login.php" method="POST">
					<label for="useremail"><?php $locale->write("createaccount.question.emailorusername"); ?>:</label>
					<input type="text" id="useremail" name="useremail" value="" placeholder="<?php $locale->write('createaccount.question.emailorusername'); ?>" maxlength="50" required="required">
					<label for="userpassword"><?php $locale->write("createaccount.password"); ?>:</label>
					<input type="password" id="userpassword" name="userpassword" value="" placeholder="<?php $locale->write('createaccount.password'); ?>" maxlength="50" autocomplete="off" required="required">
					<!--
					<p>
						<input type="checkbox" id="rememberme" name="rememberme">
						<label for="rememberme"><?php //$locale->write("createaccount.rememberme"); ?></label>
					</p>
					-->
					<div class="ui-grid-a ui-responsive">
						<div class="ui-block-a">
							<a id="login-back" href="#home" role="button" data-transition="fade" data-direction="reverse" class="ui-btn ui-corner-all"><?php $locale->write("back"); ?></a>
						</div>
						<div class="ui-block-b">
							<input type="submit" value="<?php $locale->write('send'); ?>">
						</div>
					</div>

					<h2><?php $locale->write("createaccount.question.noaccount"); ?>?</h2>
					<a href="./sites/signup.php" rel="external" data-ajax="false" role="button" class="ui-btn ui-corner-all"><?php $locale->write("createaccount.confirm"); ?></a>
				</form>
			</div>
<?php
		}
	}
?>
