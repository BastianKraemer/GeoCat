<?php
/* GeoCat - Geocaching and -tracking application
 * Copyright (C) 2016 Bastian Kraemer, Raphael Harzer
 *
 * JSONLocale.php
 *
 * This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * File JSONLocale.php
 * @package app
 */

	/**
	 * This class can be used to localize the application. The list of all translations is stored in a JSON file.
	 */
	class JSONLocale {

		/**
		 * This array contains all translations as key value pairs
		 * @var string[]
		 */
		private $translations;

		/**
		 * Create a JSONLocale object to get the translations
		 * @param string $locale The language. For example "en" or "de".
		 * @throws InvalidArgumentException If the selected language is not available
		 */
		function __construct($locale) {
			if(strtolower($locale) == "en"){
				$this->loadLocale("en");
			}
			else if(strtolower($locale) == "de"){
				$this->loadLocale("de");
			}
			else{
				$this->translations = array();
				throw new InvalidArgumentException("Unsupported locale: " . $locale);
			}
		}

		/**
		 * Loads the translations from a JSON file
		 * @param string $locale
		 */
		function loadLocale($locale){
			$file = __DIR__ . "/../locale/" . $locale . ".json";
			if(file_exists($file)){
				$this->translations = json_decode(file_get_contents($file), true);
			}
			else{
				$this->translations = array();
			}
		}

		/**
		 * Get a translated string, identified by its key
		 * @param string $key The key that idetifies the translated string
		 * @param string $default (optional) The text that will be returned if there is no translation for this key
		 * @return string The translation for this identifier or $default.
		 */
		public function get($key, $default = null){
			return array_key_exists($key, $this->translations) ? $this->translations[$key] : ($default == null ? "Undefined key: " . $key : $default);
		}

		/**
		 * Print a translated string, identified by its key
		 * @param string $key The key that idetifies the translated string
		 * @param string $default (optional) The text that will be printed if there is no translation for this key
		 */
		public function write($key, $default = null){
			print(array_key_exists($key, $this->translations) ? $this->translations[$key] : ($default == null ? "Undefined key: " . $key : $default));
		}

		/**
		 * Print a translated string qutoed by '"'
		 * @param string $key The key that idetifies the translated string
		 * @param string $quote (optional) The character that should be used as quote
		 * @param string $default (optional) The text that will be printed if there is no translation for this key
		 */
		public function writeQuoted($key, $quote = "\"", $default = null){
			print($quote . (array_key_exists($key, $this->translations) ? $this->translations[$key] : ($default == null ? "Undefined key: " . $key : $default)) . $quote);
		}

		/**
		 * Returns the current browser language. For example "en" or "de".
		 * Notice: If there are no translations for the browser language, the default language will be used.
		 *
		 * At the moment German ("de") is the only supported language.
		 */
		public static function getBrowserLanguage(){
			$lang = array_key_exists("HTTP_ACCEPT_LANGUAGE", $_SERVER) ? substr($_SERVER["HTTP_ACCEPT_LANGUAGE"], 0, 2) : "";

			if($lang == "de"){return "de";}
			return "en";
		}

		/**
		 * Creates a JSONLocale object by using the browser language
		 * @return JSONLocale
		 */
		public static function withBrowserLanguage(){
			return new JSONLocale(self::getBrowserLanguage());
		}
	}
?>
