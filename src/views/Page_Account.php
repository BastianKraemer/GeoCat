<?php
/**
 * File for the GeoCat account page
 * @package views
 */
	namespace views;

	require_once(__DIR__ . "/../app/pages/GeoCatPage.php");

	/**
	 * GeoCat challenge browser page
	 */
	class Page_Account extends \GeoCatPage {

		/**
		 * {@inheritDoc}
		 * @param JSONLocale $locale
		 * @param SessionManager $session
		 * @see GeoCatPage::printHead()
		 */
		public function printHead($locale, $session){
?>
	<script type="text/javascript">
		Account.init();
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
?>
	<div data-role="page" id="Account" data-theme="a">
<?php self::printHeader($locale->get("account.title"), "#Home", $locale, $session); ?>
		<div role="main" class="ui-content">
			<a href="#" onclick="$.mobile.changePage('#Home'); GeoCat.logout(null, './' );" id="logout" class="ui-btn"><?php $locale->write("logout") ?></a>
			<form>
				<ul data-role="listview" data-inset="true" data-divider-theme="a">
					<li data-role="list-divider"><h2><?php $locale->write("account.email"); ?></h2></li>
					<li data-icon="gear"><a href="#popup-edit" id="acc-email" data-rel="popup" data-position-to="window" data-transition="pop"><?php $locale->write("account.loading"); ?></a></li>
					<li data-role="list-divider"><h2><?php $locale->write("account.username"); ?></h2></li>
					<li data-icon="gear"><a href="#popup-edit" id="acc-username" data-rel="popup" data-position-to="window" data-transition="pop"><?php $locale->write("account.loading"); ?></a></li>
					<li data-role="list-divider"><h2><?php $locale->write("account.firstname"); ?></h2></li>
					<li data-icon="gear"><a href="#popup-edit" id="acc-firstname" data-rel="popup" data-position-to="window" data-transition="pop"><?php $locale->write("account.loading"); ?></a></li>
					<li data-role="list-divider"><h2><?php $locale->write("account.lastname"); ?></h2></li>
					<li data-icon="gear"><a href="#popup-edit" id="acc-lastname" data-rel="popup" data-position-to="window" data-transition="pop"><?php $locale->write("account.loading"); ?></a></li>
				</ul>
			</form>
			<a href="#popup-pw" id="acc-password" class="ui-btn" data-rel="popup" data-position-to="window" data-transition="pop"><?php $locale->write("account.password"); ?></a>
		</div>

		<!-- POPUP USER DATA -->
		<div id="popup-edit" data-role="popup" data-theme="a" class="ui-corner-all" style="width: 85vw;">
			<div data-role="header" data-theme="b">
				<h1><?php $locale->write("account.edit"); ?></h1>
			</div>

			<div data-role="main" class="ui-content">
				<input id="edit-field" type="text" value="" />
				<input id="edit-submit" type="button" value="<?php $locale->write("account.send"); ?>" />
			</div>
		</div>

		<!-- POPUP USER PASSWORD -->
		<div id="popup-pw" data-role="popup" data-theme="a" class="ui-corner-all" style="width: 85vw;">
			<div data-role="header" data-theme="b">
				<h1><?php $locale->write("account.edit"); ?></h1>
			</div>

			<div data-role="main" class="ui-content">
				<form>
					<input id="pwold" type="password" placeholder="<?php $locale->write("account.oldpassword"); ?>" value="" />
					<input id="pwnew1" type="password" placeholder="<?php $locale->write("account.newpassword"); ?>" value="" />
					<input id="pwnew2" type="password" placeholder="<?php $locale->write("account.newpassword"); ?>" value="" />
					<input id="pw-submit" type="button" value="<?php $locale->write("account.send"); ?>" />
				</form>
			</div>
		</div>

	</div>
<?php
		}
	}
?>
