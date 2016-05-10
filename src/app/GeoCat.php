<?php
/* GeoCat - Geocaching and -tracking application
 * Copyright (C) 2016 Bastian Kraemer, Raphael Harzer
 *
 * GeoCat.php
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
 * GeoCat core file
 * @package app
 */

/**
 * This class contains GeoCat version information and provides access to the GeoCat configuration
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
	 * GeoCat configuration
	 * @var array
	 */
	private static $config = null;

	/**
	 * Path to GeoCat configruation file
	 * @var string $configPath
	 */
	private static $configPath = "/../config/config.php";

	/**
	 * Path to GeoCat app directory
	 * @var string $appDir
	 */
	private static $appDir = __DIR__;

	/**
	 * Loads the GeoCat configuration from the config file ('/config/config.php')
	 */
	private static function loadConfig(){
		if(self::$config == null){
			self::$config = require self::$appDir . self::$configPath;
		}
	}

	/**
	 * Sets the path to the GeoCat configuration file relative to the 'app' directory and reloads the configuration
	 * @param string $path Relative path to GeoCat configuration file
	 */
	public static function setConfigPathRelativeToAppDirectory($path){
		self::setConfigPath(self::$appDir . "/" . self::$configPath);
	}

	/**
	 * Sets the path to the GeoCat configuration file and reloads the configuration
	 * @param string $path Path to GeoCat configuration file
	 */
	public static function setConfigPath($path){
		self::$configPath = $path;
		self::$config = require self::$configPath;
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
