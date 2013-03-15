<?php

abstract class SelectriAbstractData implements SelectriData {
	
	private $widget;
	private $selection;
	
	protected function __construct(SelectriWidget $widget) {
		$this->setWidget($widget);
	}
	
	public function validate() {
	}
	
	public function getWidget() {
		return $this->widget;
	}
	
	public function setWidget(SelectriWidget $widget) {
		$this->widget = $widget;
		return $this;
	}
	
}
