<?php

interface SelectriDataFactory {
	
	public function setParameters($params);
	
	public function setWidget(SelectriWidget $widget);
	
	public function getData();
	
}
