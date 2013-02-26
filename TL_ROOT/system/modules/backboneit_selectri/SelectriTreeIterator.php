<?php

class SelectriTreeIterator extends RecursiveIteratorIterator {
	
	private $prevDepth;
	
	public function callHasChildren() {
		return $this->current()->hasChildren(); // calls SelectriNode::hasChildren
	}
	
	public function callGetChildren() {
		return $this->current()->getChildrenIterator(); // calls SelectriNode::getChildrenIterator
	}
	
	public function rewind() {
		$this->prevDepth = 0;
		return parent::rewind();
	}
	
	public function next() {
		$this->prevDepth = $this->getDepth();
		return parent::next();
	}
	
	public function key() {
		return $this;
	}
	
	public function getDown() {
		return max($this->prevDepth - $this->getDepth(), 0);
	}
	
	public function getUp() {
		return abs(min($this->prevDepth - $this->getDepth(), 0));
	}
	
}
