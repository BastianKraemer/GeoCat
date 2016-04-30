<?php
/**
 * Sample configuration file for GeoCat
 */

	return [
			"app.name" => "GeoCat",

			"database.host" => "localhost",
			"database.port" => "3306",
			"database.type" => "mysql", 	/* For all supported databases see https://secure.php.net/manual/en/pdo.drivers.php */
			"database.username" => "root",
			"database.password" => "",
			"database.name" => "geocat",

			/* Path to the imprint and the data privacy statement of your website.
			 * GeoCat will set a href to this file. The path will be used relative to 'app.contextroot' */
			"policy.imprint" => null,
			"policy.data_privacy_statement" => null
	];
?>
