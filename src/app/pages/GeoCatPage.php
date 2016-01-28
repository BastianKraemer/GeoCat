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
			if($session->isSignedIn()): ?>
				<div data-role="controlgroup" data-type="horizontal" class="ui-btn-right">
					<!-- link zu "Account" -->
					<a href="#" data-transition="fade" class="loginbutton ui-btn ui-btn-inline ui-mini ui-corner-all ui-btn-icon-right ui-icon-user"><?php echo $session->getUsername() ?></a>
					<a onclick="Logout.send('<?php print($config["app.contextroot"] . "/"); ?>');" class="loginbutton ui-btn ui-btn-inline ui-mini ui-corner-all">Logout</a>
				</div>
			<?php else: ?>
				<?php if(!$externalLink):?>
					<a href="#login" data-transition="fade" class="loginbutton ui-btn-right ui-btn ui-btn-inline ui-mini ui-corner-all ui-btn-icon-right ui-icon-user">Login</a>
				<?php else: ?>
					<a href="<?php print($config["app.contextroot"] . "/index.php#login"); ?>" data-rel="external" data-ajax="false" data-transition="fade" class="loginbutton ui-btn-right ui-btn ui-btn-inline ui-mini ui-corner-all ui-btn-icon-right ui-icon-user">Login</a>
				<?php endif; ?>
			<?php endif; ?>
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
