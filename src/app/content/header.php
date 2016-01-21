<?php
	/**
	 * @ignore
	 */
	function printHeader($title, $homeButton, $externalLink, $config, $session){ ?>
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
				<button onclick="sendRequest('app/content/logout.php', 'logout=true', redirectHome);" class="loginbutton ui-btn ui-btn-inline ui-mini ui-corner-all">Logout</a>
			</div>
		<?php else: ?>
			<a href="#login" data-transition="fade" class="loginbutton ui-btn-right ui-btn ui-btn-inline ui-mini ui-corner-all ui-btn-icon-right ui-icon-user">Login</a>
		<?php endif; ?>
		</div>	
	<?php
	} ?>