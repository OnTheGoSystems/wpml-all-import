<?php

class PMLI_Import_List extends PMLI_Model_List {
	public function __construct() {
		parent::__construct();
		$this->setTable(PMLI_Plugin::getInstance()->getTablePrefix() . 'imports');
	}
}