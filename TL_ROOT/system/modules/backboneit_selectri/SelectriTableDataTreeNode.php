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
	
	public function getKey() {
		return $this->key;
	}
	
	public function getLabel() {
		return $this->data->getConfig()->resolveTreeLabelFormat($this->node, $this->data);
	}
	
	public function getIcon() {
		$icon = $this->data->getConfig()->resolveTreeIcon($this->node, $this->data);
		return strpos($icon, '/') === false
			? sprintf('system/themes/%s/images/%s', $this->data->getWidget()->getTheme(), $icon)
			: $icon;
	}
	
	public function isSelectable() {
		if($this->data->getConfig()->hasItem()) {
			return false;
		}
		switch($this->data->getWidget()->getMode()) {
			case 'leaf': return !$this->node['hasChildren']; break;
			case 'inner': return !!$this->node['hasChildren']; break;
			default: return true; break;
		}
	}
	
	public function hasSelectableChildren() {
		if($this->data->getConfig()->hasItem()) {
			return true;
		}
		if($this->data->getWidget()->getMode() == 'inner') {
			return !!$this->node['hasGrandChildren'] == 1;
		} else {
			return !!$this->node['hasChildren'] == 1;
		}
	}
	
	public function hasChildren() {
		$children = $this->tree->children[$this->key];
		if(!$children) {
			return false;
		}
		reset($children);
		return isset($this->tree->nodes[key($children)]);
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
	
	public function getPathKeys() {
		$pathKeys = array();
		$parents = &$this->tree->parents;
		$key = $this->key;
		while(isset($parents[$key])) {
			$pathKeys[] = $key = $parents[$key];
		}
		return $pathKeys;
	}
	
}
