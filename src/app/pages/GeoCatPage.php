<?php
/**
 * (Abstract) class 'GeoCatPage'
 */

	/**
	 * Base class for all GeoCat pages
	 *
	 * Therefore each page has to implement the abstract functions 'printHead()' and 'printContent()'.
	 */
	abstract class GeoCatPage {

		/**
		 * Prints the head section of a GeoCat page
		 * @param JSONLocale $locale
		 * @param SessionManager $session
		 */
		public abstract function printHead($locale, $session);

		/**
		 * Prints out the content of a GeoCat page
		 * @param JSONLocale $locale
		 * @param SessionManager $session
		 */
		public abstract function printContent($locale, $session);

		/**
		 * Print the default header for a GeoCat page
		 * @param string $title page title
		 * @param string $backButtonTarget Id of the previous GeoCat page (use <code>null</code> to hide the button)
		 * @param JSONLocale $locale
		 * @param SessionManager $session
		 */
		public static function printHeader($title, $backButtonTarget, $locale, $session){ ?>
		<div data-role="header" data-id="page_header" data-theme="b">
			<h1><?php echo $title ?></h1>
<?php		if($backButtonTarget != null){
				$icon; $txt;
				if(strcasecmp($backButtonTarget, "#Home") == 0){
					$icon = "ui-icon-home";
					$txt = $locale->get("home");
				}
				else{
					$icon = "ui-icon-arrow-l";
					$txt = $locale->get("back");
				}
?>
				<a href="<?php echo $backButtonTarget ?>" class="ui-btn-left ui-btn ui-btn-inline ui-mini ui-corner-all ui-btn-icon-left <?php echo $icon ?>"><?php echo $txt ?></a>
<?php 		}
			$buttonText = $session->isSignedIn() ? $session->getUsername() : "Login";
			$targetJavaScriptFunction = !$session->isSignedIn() ? "Dialogs.showLoginDialog();" : "";
?>
			<button id="login-btn" onclick="<?php echo $targetJavaScriptFunction; ?>" class="login-button ui-btn-right ui-btn ui-btn-inline ui-mini ui-corner-all ui-btn-icon-right ui-icon-user"><?php echo $buttonText ?></button>
		</div>

		<div class="popup-login" data-role="popup" data-arrow="true" data-theme="a" class="ui-corner-all">
		    <div data-role="main" class="ui-content">
          <button onclick="$.mobile.changePage('#Account')" class="ui-btn ui-shadow ui-btn-inline ui-btn-icon-left ui-icon-gear ui-corner-all ui-btn-icon-notext"></button>
          <button onclick="GeoCat.logout(null); $('#popup-login-' + $.mobile.activePage.attr('id')).popup('close')" class="ui-btn ui-shadow ui-btn-inline ui-mini ui-corner-all"><?php $locale->write("logout"); ?></button>
        </div>
		</div>

<?php
		}

		/**
		 * Prints out all page headers to genereate the whole HTML file
		 * @param GeoCatPage[] $pages Array of all pages
		 * @param JSONLocale $locale
		 * @param SessionManager $session
		 */
		public static function printAllHeaders($pages, $locale, $session){
			foreach($pages as $page){
				$page->printHead($locale, $session);
			}
		}

		/**
		 * Prints out all page contents to genereate the whole HTML file
		 * @param GeoCatPage[] $pages Array of all pages
		 * @param JSONLocale $locale
		 * @param SessionManager $session
		 */
		public static function printAllPages($pages, $locale, $session){
			foreach($pages as $page){
				$page->printContent($locale, $session);
			}
		}
	}
?>
