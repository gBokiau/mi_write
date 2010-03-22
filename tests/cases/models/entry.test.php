<?php
/* Entry Test cases generated on: 2010-03-23 00:03:32 : 1269299492*/
App::import('model', 'MiWrite.Entry');

class EntryTestCase extends CakeTestCase {

	public $fixtures = array('mi_write.entry', 'mi_write.user', 'mi_write.page', 'mi_write.comment');

	function startTest() {
		$this->Entry = ClassRegistry::init('MiWrite.Entry');
	}

	function endTest() {
		unset($this->Entry);
		ClassRegistry::flush();
	}

	function testCategory() {

	}

}