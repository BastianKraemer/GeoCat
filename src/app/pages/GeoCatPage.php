<?php
	abstract class GeoCatPage {

		/* Functions that has to be implemented */
		public abstract function printHead($config, $locale, $session, $pathToRoot);
		public abstract function printContent($config, $locale, $session, $pathToRoot);

		/* Default header for all  pages */

		public static function printHeader($title, $homeButton, $externalLink, $config, $session){ ?>
		<div data-role="header" data-id="page_header" data-theme="b">
			<h1><?php echo $title ?></h1>
			<?php if($homeButton):
				$href = $externalLink ? $config["app.contextroot"] . "/" : "#Home";
				$dataAttribute = $externalLink ? "data-rel=\"external\" data-ajax=\"false\"" : ""; ?>
				<a href="<?php echo $href ?>" <?php echo $dataAttribute ?> class="ui-btn-left ui-btn ui-btn-inline ui-mini ui-corner-all ui-btn-icon-left ui-icon-home">Home</a>
			<?php endif;
			$buttonText = $session->isSignedIn() ? $session->getUsername() : "Login";
			$pathToRoot = $externalLink ? $config["app.contextroot"] : ".";
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
