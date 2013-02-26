<?php

class SelectriTableDataFactory extends SelectriAbstractDataFactory {
	
	private $cfg;
	
	public function __construct() {
		parent::__construct();
		$this->cfg = new SelectriTableDataConfig();
	}
	
	public function __clone() {
		parent::__clone();
		$this->cfg = clone $this->cfg;
	}
	
	public function getConfig() {
		return $this->cfg;
	}
	
	public function setConfig(SelectriTableDataConfig $cfg) {
		$this->cfg = $cfg;
		return $this;
	}
	
	public function getData() {
		return new SelectriTableData($this->getWidget(), $this->getConfig());
	}
	
}
