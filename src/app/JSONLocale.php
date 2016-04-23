<?php
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
			if(strtolower($locale) == "de"){
				$this->translations = json_decode(file_get_contents(__DIR__ . "/../locale/" . $locale . ".json"), true);
			}
			else{
				throw new InvalidArgumentException("Unsupported locale: " . $locale);
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
			return "de"; // later this should be "en"
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
