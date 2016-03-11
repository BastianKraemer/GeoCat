<?php
	abstract class GeoCatPage {

		/* Functions that has to be implemented */
		public abstract function printHead($config, $locale, $session, $pathToRoot);
		public abstract function printContent($config, $locale, $session, $pathToRoot);

		/* Default header for all  pages */

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
			$targetJavaScriptFunction = $session->isSignedIn() ? "GeoCat.logout(null, '" . $pathToRoot . "');" : "Dialogs.showLoginDialog('" . $pathToRoot . "');";
?>
			<button onclick="<?php echo $targetJavaScriptFunction; ?>" class="login-button ui-btn-right ui-btn ui-btn-inline ui-mini ui-corner-all ui-btn-icon-right ui-icon-user"><?php echo $buttonText ?></button>
		</div>
<?php
		}

		public static function printAllHeaders($pages, $config, $locale, $session, $pathToRoot){
			foreach($pages as $page){
				$page->printHead($config, $locale, $session, $pathToRoot);
			}
		}

		public static function printAllPages($pages, $config, $locale, $session, $pathToRoot){
			foreach($pages as $page){
				$page->printContent($config, $locale, $session, $pathToRoot);
			}
		}
	}
?>
