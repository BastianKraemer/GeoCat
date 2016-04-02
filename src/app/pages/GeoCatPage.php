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
		 * @param array $config GeoCat configuration
		 * @param JSONLocale $locale
		 * @param SessionManager $session
		 * @param string $pathToRoot
		 */
		public abstract function printHead($config, $locale, $session, $pathToRoot);

		/**
		 * Prints out the content of a GeoCat page
		 * @param array $config GeoCat configuration
		 * @param JSONLocale $locale
		 * @param SessionManager $session
		 * @param string $pathToRoot
		 */
		public abstract function printContent($config, $locale, $session, $pathToRoot);

		/**
		 * Print the default header for a GeoCat page
		 * @param string $title page title
		 * @param string $backButtonTarget Id of the previous GeoCat page (use <code>null</code> to hide the button)
		 * @param JSONLocale $locale
		 * @param array $config
		 * @param SessionManager $session
		 */
		public static function printHeader($title, $backButtonTarget, $locale, $config, $session){ ?>
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
			$pathToRoot = ".";
			//$targetJavaScriptFunction = $session->isSignedIn() ? "GeoCat.logout(null, '" . $pathToRoot . "');" : "Dialogs.showLoginDialog('" . $pathToRoot . "');";
			$targetJavaScriptFunction = $session->isSignedIn() ? "$.mobile.changePage('#Account');" : "Dialogs.showLoginDialog('" . $pathToRoot . "');";
?>
			<button id="login-btn" onclick="<?php echo $targetJavaScriptFunction; ?>" class="login-button ui-btn-right ui-btn ui-btn-inline ui-mini ui-corner-all ui-btn-icon-right ui-icon-user"><?php echo $buttonText ?></button>
		</div>
<?php
		}

		/**
		 * Prints out all page headers to genereate the whole HTML file
		 * @param GeoCatPage[] $pages Array of all pages
		 * @param array $config
		 * @param JSONLocale $locale
		 * @param SessionManager $session
		 * @param string $pathToRoot
		 */
		public static function printAllHeaders($pages, $config, $locale, $session, $pathToRoot){
			foreach($pages as $page){
				$page->printHead($config, $locale, $session, $pathToRoot);
			}
		}

		/**
		 * Prints out all page contents to genereate the whole HTML file
		 * @param GeoCatPage[] $pages Array of all pages
		 * @param array $config
		 * @param JSONLocale $locale
		 * @param SessionManager $session
		 * @param string $pathToRoot
		 */
		public static function printAllPages($pages, $config, $locale, $session, $pathToRoot){
			foreach($pages as $page){
				$page->printContent($config, $locale, $session, $pathToRoot);
			}
		}
	}
?>
