<?php
	class InputTemplates {

		public static function printTextField($nameAndId, $isPasswordField, $labelText, $isRequiredField, $maxCharacters){
			print("<div class=\"ui-field-contain\">\n" .
					self::getDefaultLabel($nameAndId, $labelText, $isRequiredField) .
					"<input id=\"" . $nameAndId . "\" name=\"" . $nameAndId . "\" type=\"" . ($isPasswordField ? "password" : "text") . "\" value=\"\" placeholder=\"" . $labelText . "\" maxlength=" . $maxCharacters . ">\n" .
					"</div>\n");
		}

		public static function printTextArea($nameAndId, $isPasswordField, $labelText, $isRequiredField, $maxCharacters){
			print("<div class=\"ui-field-contain\">\n" .
					self::getDefaultLabel($nameAndId, $labelText, $isRequiredField) .
					"<textarea id=\"" . $nameAndId . "\" name=\"" . $nameAndId . "\" placeholder=\"" . $labelText . "\" maxlength=" . $maxCharacters . ($isRequiredField ? " required" : "") . "></textarea>" .
				  "</div>\n");
		}

		public static function printDropDown($nameAndId, $labelText, $isRequiredField, $items, $defaultValue){
			print("<div class=\"ui-field-contain\">\n" .
					self::getDefaultLabel($nameAndId, $labelText, $isRequiredField) .
					"<select id=\"" . $nameAndId . "\" name=\"" . $nameAndId . "\" data-mini=\"true\">\n");

			foreach($items as $key => $value){
				print("\t<option value=\"" . $value . "\"" . ($value == $defaultValue ? " selected" : "") . ">" . $key . "</option>\n");
			}

			print("</select>\n</div>\n");
		}

		public static function printFlipswitch($nameAndId, $labelText, $isRequiredField, $isChecked){

			print("<div class=\"ui-field-contain\">\n" .
					self::getDefaultLabel($nameAndId, $labelText, $isRequiredField) .
					"<input id=\"" . $nameAndId . "\" name=\"" . $nameAndId . "\" data-role=\"flipswitch\" type=\"checkbox\"" . ($isChecked ? " checked" : "") . ">" .
				  "</div>\n");
		}

		private static function getDefaultLabel($nameAndId, $labelText, $isRequiredField){
			return "<label for=\"" . $nameAndId . "\">" . $labelText . ":" . ($isRequiredField ? " <span class=\"required\">*</span>" : "") . "</label>\n";
		}
	}
?>
