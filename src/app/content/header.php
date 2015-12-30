<?php
	/**
	 * @ignore
	 */
	function printHeader($title, $homeButton, $externalLink, $config, $session){

		print("<div data-role=\"header\" data-id=\"page_header\" data-theme=\"b\">\n" .
			 	"		<h1>" . $title . "</h1>");
		if($homeButton){
			$href = $externalLink ? $config["app.contextroot"] . "/" : "#home";
			$dataAttribute = $externalLink ? "rel=\"external\" " : "";
			print("		<a href=\"" . $href . "\" " . $dataAttribute . "class=\"ui-btn-left ui-btn ui-btn-inline ui-mini ui-corner-all ui-btn-icon-left ui-icon-home\">Home</a>\n");
		}

		$loginBtnTxt = $session->isSignedIn() ? $session->getUsername() : "Login";

		print(	"		<a href=\"#\" class=\"loginbutton ui-btn-right ui-btn ui-btn-inline ui-mini ui-corner-all ui-btn-icon-right ui-icon-user\">" . $loginBtnTxt . "</a>\n" .
				"	</div>\n");
	}
?>