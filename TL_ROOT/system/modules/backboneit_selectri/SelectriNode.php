<?php

interface SelectriNode {
	
// 	public function getCSSClasses() {
// 		if(!$this->hasChildren()) {
// 			return '';
// 		}
// 		if(!$this->hasVisibleChildren()) {
// 			return 'striParent striClosed';
// 		}
// 		return 'striParent';
// 	}
	
	public function getKey();
	
	public function getLabel();
	
	public function getIcon();
	
	public function hasPath();
	
	public function getPathIterator();
	
	public function isSelectable();
	
	public function hasItems();
	
	public function getItemIterator();
	
	public function hasChildren();
	
	public function getChildrenIterator();
	
}
