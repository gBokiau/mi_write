<?php
/* Page Test cases generated on: 2010-03-23 00:03:27 : 1269300927*/
App::import('model', 'MiWrite.Page');

class PageTestCase extends CakeTestCase {
	public $fixtures = array('mi_write.page', 'app.user', 'app.profile');

	function startTest() {
		$this->Page = ClassRegistry::init('MiWrite.Page');
	}

	function endTest() {
		unset($this->Page);
		ClassRegistry::flush();
	}

	function testPageList() {

	}

	function testRemoveStopWord() {

	}

	function testResetTag() {

	}

}