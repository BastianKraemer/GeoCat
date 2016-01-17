<?php
	abstract class GeoCatPage {

		/* Functions that has to be implemented */
		public abstract function getPageId();

		protected abstract function printContent($config, $locale, $session);

		/* Function that can be implemented (override) */
		public function getPageTheme(){return "a";}

		/* Functions for all  Pages */
		public function printPage($config, $locale, $session){
			print("<div data-role=\"page\" id=\"" . $this->getPageId() . "\" data-theme=\"" . $this->getPageTheme() . "\">\n");
			$this->printContent($config, $locale, $session);
			print("</div>\n");
		}

		protected function printHeader($title, $homeButton, $externalLink, $config, $session){
			print("\t<div data-role=\"header\" data-id=\"page_header\" data-theme=\"b\">\n" .
					"\t\t<h1>" . $title . "</h1>\n");
			if($homeButton){
				$href = $externalLink ? $config["app.contextroot"] . "/" : "#home";
				$dataAttribute = $externalLink ? "rel=\"external\" " : "";
				print("\t\t<a href=\"" . $href . "\" " . $dataAttribute . "class=\"ui-btn-left ui-btn ui-btn-inline ui-mini ui-corner-all ui-btn-icon-left ui-icon-home\">Home</a>\n");
			}

			$loginBtnTxt = $session->isSignedIn() ? $session->getUsername() : "Login";

			print(	"\t\t<a href=\"#\" class=\"loginbutton ui-btn-right ui-btn ui-btn-inline ui-mini ui-corner-all ui-btn-icon-right ui-icon-user\">" . $loginBtnTxt . "</a>\n" .
					"\t</div>\n");
		}
	}
?>