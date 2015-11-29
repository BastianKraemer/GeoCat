<?php
	class JSONLocale {

		private $translations;

		function __construct($locale, $config) {
			if(strtolower($locale) == "de"){
				$this->translations = json_decode(file_get_contents($_SERVER["DOCUMENT_ROOT"] . $config["app.contextroot"] . "/locale/" . $locale . ".json"), true);
			}
			else{
				throw new InvalidArgumentException("Unsupported locale: " . $locale);
			}
		}

		public function get($key, $default = null){
			return array_key_exists($key, $this->translations) ? $this->translations[$key] : ($default == null ? "Undefined key: " . $key : $default);
		}

		public function write($key, $default = null){
			print(array_key_exists($key, $this->translations) ? $this->translations[$key] : ($default == null ? "Undefined key: " . $key : $default));
		}
	}
?>
