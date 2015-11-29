<?php
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
		 * @param string[] $config Configruation from "config.php". The required parameter is "app.contextroot".
		 * @throws InvalidArgumentException If the selected language is not available
		 */
		function __construct($locale, $config) {
			if(strtolower($locale) == "de"){
				$this->translations = json_decode(file_get_contents($_SERVER["DOCUMENT_ROOT"] . $config["app.contextroot"] . "/locale/" . $locale . ".json"), true);
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
	}
?>
