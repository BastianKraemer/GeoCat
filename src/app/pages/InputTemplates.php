<?php
/**
 * This file provides some templates for input elements
 */

	/**
	 * This class contains several methods to add input elemtns to your page
	 */
	class InputTemplates {

		/**
		 * Append a text field
		 * @param string $nameAndId
		 * @param boolean $isPasswordField
		 * @param string $labelText
		 * @param boolean $isRequiredField
		 * @param integer $maxCharacters
		 */
		public static function printTextField($nameAndId, $isPasswordField, $labelText, $isRequiredField, $maxCharacters){
			print("<div class=\"ui-field-contain\">\n" .
					self::getDefaultLabel($nameAndId, $labelText, $isRequiredField) .
					"<input id=\"" . $nameAndId . "\" name=\"" . $nameAndId . "\" type=\"" . ($isPasswordField ? "password" : "text") . "\" value=\"\" placeholder=\"" . $labelText . "\" maxlength=" . $maxCharacters . ">\n" .
					"</div>\n");
		}

		/**
		 * Append a text area
		 * @param string $nameAndId
		 * @param boolean $isPasswordField
		 * @param string $labelText
		 * @param boolean $isRequiredField
		 * @param integer $maxCharacters
		 */
		public static function printTextArea($nameAndId, $isPasswordField, $labelText, $isRequiredField, $maxCharacters){
			print("<div class=\"ui-field-contain\">\n" .
					self::getDefaultLabel($nameAndId, $labelText, $isRequiredField) .
					"<textarea id=\"" . $nameAndId . "\" name=\"" . $nameAndId . "\" placeholder=\"" . $labelText . "\" maxlength=" . $maxCharacters . ($isRequiredField ? " required" : "") . "></textarea>" .
				  "</div>\n");
		}

		/**
		 * Append a drop-down box
		 * @param string $nameAndId
		 * @param string $labelText
		 * @param boolean $isRequiredField
		 * @param array $items The array has to be like this: array("Item name" => [value], ...)
		 * @param string $defaultValue
		 */
		public static function printDropDown($nameAndId, $labelText, $isRequiredField, $items, $defaultValue){
			print("<div class=\"ui-field-contain\">\n" .
					self::getDefaultLabel($nameAndId, $labelText, $isRequiredField) .
					"<select id=\"" . $nameAndId . "\" name=\"" . $nameAndId . "\" data-mini=\"true\">\n");

			foreach($items as $key => $value){
				print("\t<option value=\"" . $value . "\"" . ($value == $defaultValue ? " selected" : "") . ">" . $key . "</option>\n");
			}

			print("</select>\n</div>\n");
		}

		/**
		 * Append a flipswitch
		 * @param string $nameAndId
		 * @param string $labelText
		 * @param boolean $isRequiredField
		 * @param boolean $isChecked
		 * @param string $containerId (optional) The id of the surrounding div
		 */
		public static function printFlipswitch($nameAndId, $labelText, $isRequiredField, $isChecked, $containerId = null){

			print("<div " . ($containerId != null ? "id=\"" . $containerId . "\" " : "") . "class=\"ui-field-contain\">\n" .
					self::getDefaultLabel($nameAndId, $labelText, $isRequiredField) .
					"<input id=\"" . $nameAndId . "\" name=\"" . $nameAndId . "\" data-role=\"flipswitch\" type=\"checkbox\"" . ($isChecked ? " checked" : "") . ">" .
				  "</div>\n");
		}

		/**
		 * Generate a label for an element
		 * @param string $nameAndId
		 * @param string $labelText
		 * @param boolean $isRequiredField
		 */
		private static function getDefaultLabel($nameAndId, $labelText, $isRequiredField){
			return "<label for=\"" . $nameAndId . "\">" . $labelText . ":" . ($isRequiredField ? " <span class=\"required\">*</span>" : "") . "</label>\n";
		}
	}
?>
