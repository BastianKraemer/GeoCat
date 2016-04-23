<?php
load("app/JSONLocale.php");

class JSONLocaleTest  extends PHPUnit_Framework_TestCase {

	private $jsonLocale;

	/**
	 * @before
	 */
	public function prepare(){
		$this->jsonLocale = new JSONLocale("de");
	}

	public function testGet(){
		$this->assertEquals("OK", $this->jsonLocale->get("okay"));
	}

	/**
	 * @expectedException InvalidArgumentException
	 */
	public function testIllegalLang(){
		new JSONLocale("xy");
	}
}
