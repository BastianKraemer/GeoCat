<?php

/**
 * GeoCat core file
 * @package app
 */

class GeoCat{

	/**
	 * Current version of GeoCat
	 * @var VERSION
	 */
	const VERSION = 0.1;

	/**
	 * Latest version of the GeoCat database
	 * @var DB_VERSION
	 */
	const DB_VERSION = 0.1;

	/**
	 * Latest revision of the GeoCat database
	 * @var DB_REVISION
	 */
	const DB_REVISION = 1;

	/**
	 * Class for central configuration handling
	 *
	 * This class also provides some properties for the latest versin of GeoCat and its database structure
	 *
	 * @package app
	 */
	private static $config = null;

	/**
	 * Loads the GeoCat configuration from the config file ('/config/config.php')
	 */
	private static function loadConfig(){
		if(self::$config == null){
			self::$config = require __DIR__ . "/../config/config.php";
		}
	}

	/**
	 * Returns the current GeoCat configuration
	 * @return array $config GeoCat configuration
	 */
	public static function getConfig(){
		self::loadConfig();
		return self::$config;
	}

	/**
	 * Returns a single configuration value defined by its key or <code>null</code> if the key does not exist
	 * @param string $key Configuration key
	 */
	public static function getConfigKey($key){
		self::loadConfig();
		if(array_key_exists($key, self::$config)){
			return self::$config[$key];
		}
		return null;
	}
}
