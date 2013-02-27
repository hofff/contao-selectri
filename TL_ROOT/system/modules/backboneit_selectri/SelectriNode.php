<?php

interface SelectriNode {
	
	public function getKey();
	
	public function getLabel();
	
	public function getIcon();
	
	public function isSelectable();
	
	public function hasSelectableChildren();
	
	public function hasPath();
	
	public function getPathIterator();
	
	public function hasItems();
	
	public function getItemIterator();
	
	public function hasChildren();
	
	public function getChildrenIterator();
	
}
