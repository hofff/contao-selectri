<?php

interface SelectriData {
	
	public function validate();
	
	public function filter(array $selection);
	
	public function getSelectionIterator(array $selection);
	
	public function getTreeIterator($start = null);
	
	public function getPathIterator($key);
	
	public function getSearchIterator($query);
	
}
