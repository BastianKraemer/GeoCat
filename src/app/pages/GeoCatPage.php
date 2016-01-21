<?php
	abstract class GeoCatPage {

		/* Functions that has to be implemented */
		public abstract function getPageId();

		protected abstract function printContent($config, $locale, $session);

		/* Function that can be implemented (override) */
		public function getPageTheme(){return "a";}
		protected function getPageHeaderAttributes(){return null;}

		/* Functions for all  Pages */
		public function printPage($config, $locale, $session){
			$attribs = $this->getPageHeaderAttributes();
			print("<div data-role=\"page\" id=\"" . $this->getPageId() . "\" data-theme=\"" . $this->getPageTheme() . "\"" . ($attribs == null ? "" : (" " . $attribs)) . ">\n");
			$this->printContent($config, $locale, $session);
			print("</div>\n");
		}

		public static function printHeader($title, $homeButton, $externalLink, $config, $session){ ?>
		<div data-role="header" data-id="page_header" data-theme="b">
			<h1><?php echo $title ?></h1>
			<?php if($homeButton):
				$href = $externalLink ? $config["app.contextroot"] . "/" : "#home";
				$dataAttribute = $externalLink ? "rel=\"external\" " : ""; ?>
				<a href="<?php echo $href ?>" <?php echo $dataAttribute ?> class="ui-btn-left ui-btn ui-btn-inline ui-mini ui-corner-all ui-btn-icon-left ui-icon-home">Home</a>
			<?php endif;
			if($session->isSignedIn()): ?>
				<div data-role="controlgroup" data-type="horizontal" class="ui-btn-right">
					<!-- link zu "Account" -->
					<a href="#" data-transition="fade" class="loginbutton ui-btn ui-btn-inline ui-mini ui-corner-all ui-btn-icon-right ui-icon-user"><?php echo $session->getUsername() ?></a>
					<a onclick="Logout.send('<?php print($config["app.contextroot"] . "/"); ?>');" class="loginbutton ui-btn ui-btn-inline ui-mini ui-corner-all">Logout</a>
				</div>
			<?php else: ?>
				<a href="#login" data-transition="fade" class="loginbutton ui-btn-right ui-btn ui-btn-inline ui-mini ui-corner-all ui-btn-icon-right ui-icon-user">Login</a>
			<?php endif; ?>
		</div>
<?php
		}
	}
?>
