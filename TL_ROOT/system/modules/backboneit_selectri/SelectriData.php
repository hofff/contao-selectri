<?php

interface SelectriData {
	
	public function validate();

	public function setSelection(array $selection);
	
	public function getSelectionIterator();
	
	public function getTreeIterator();
	
	public function hasSelectableNodes();
	
}
