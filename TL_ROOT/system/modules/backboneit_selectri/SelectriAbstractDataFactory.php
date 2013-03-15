<?php

abstract class SelectriAbstractDataFactory implements SelectriDataFactory {

	public static function create() {
		$clazz = get_called_class();
		return new $clazz();
	}
	
	private $widget;
	
	public function __construct() {
	}
	
	public function __clone() {
	}
	
	public function setParameters($params) {
	}
	
	public function setWidget(SelectriWidget $widget) {
		$this->widget = $widget;
		return $this;
	}
	
	public function getWidget() {
		return $this->widget;
	}
	
}
