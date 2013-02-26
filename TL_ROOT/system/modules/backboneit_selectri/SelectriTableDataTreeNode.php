<?php

class SelectriTableDataTreeNode implements SelectriNode {
	
	protected $data;
	protected $tree;
	protected $key;
	protected $node;
	
	public function __construct(SelectriTableData $data, stdClass $tree, $key) {
		$this->data = $data;
		$this->tree = $tree;
		$this->key = $key;
		$this->node = &$this->tree->nodes[$this->key];
	}
	
// 	public function getCSSClasses() {
// 		if(!$this->hasChildren()) {
// 			return '';
// 		}
// 		if(!$this->hasVisibleChildren()) {
// 			return 'striParent striClosed';
// 		}
// 		return 'striParent';
// 	}
	
	public function getKey() {
		return $this->key;
	}
	
	public function getLabel() {
		return $this->data->getConfig()->resolveTreeLabelFormat($this->node, $this->data);
	}
	
	public function getIcon() {
		$icon = $this->data->getConfig()->resolveTreeIcon($this->node, $this->data);
		return strpos($icon, '/') === false && false
			? sprintf('system/themes/%s/images/%s', $this->getTheme(), $icon) // TODO shit call
			: $icon;
	}
	
	public function isSelectable() {
		if($this->data->getConfig()->hasItem()) {
			return false;
		}
		switch($this->data->getWidget()->getMode()) {
			case 'leaf': return !$this->hasChildren(); break;
			case 'inner': return $this->hasChildren(); break;
			default: return true; break;
		}
	}
	
	public function hasChildren() {
		return !!$this->tree->children[$this->key];
	}
	
	public function getChildrenIterator() {
		$children = array();
		if($this->hasChildren()) foreach($this->tree->children[$this->key] as $key => $_) {
			$children[] = new self($this->data, $this->tree, $key);
		}
		return new ArrayIterator($children);
	}
	
	public function hasItems() {
		return false;
	}
	
	public function getItemIterator() {
		return new EmptyIterator();
	}
	
	public function hasPath() {
		return isset($this->tree->nodes[$this->tree->parents[$this->key]]);
	}
	
	public function getPathIterator() {
		$key = $this->key;
		$path = array();
		while($this->tree->nodes[$key = $this->tree->parents[$key]]) {
			$path[] = new self($this->data, $this->tree, $key);
		}
		return new ArrayIterator(array_reverse($path));
	}
	
}
