<?php

class SelectriTableTreeDataNode implements SelectriNode {

	protected $data;
	protected $tree;
	protected $key;
	protected $node;

	public function __construct(SelectriTableTreeData $data, stdClass $tree, $key) {
		$this->data = $data;
		$this->tree = $tree;
		$this->key = $key;
		$this->node = &$this->tree->nodes[$this->key];
	}

	public function getKey() {
		return $this->key;
	}

	public function getLabel() {
		return $this->data->generateTreeLabel($this->node);
	}

	public function getContent() {
		return $this->data->generateTreeContent($this->node);
	}

	public function getAdditionalInputName($key) {
		$name = $this->data->getWidget()->getAdditionalInputBaseName();
		$name .= '[' . $this->getKey() . ']';
		$name .= '[' . $key . ']';
		return $name;
	}

	public function getIcon() {
		return $this->data->generateTreeIcon($this->node);
	}

	public function isSelectable() {
		if(!$this->node['_isSelectable']) {
			return false;
		}
		switch($this->data->getConfig()->getTreeMode()) {
			case 'leaf': return !$this->node['_hasChildren']; break;
			case 'inner': return !!$this->node['_hasChildren']; break;
			default: return true; break;
		}
	}

	public function hasSelectableDescendants() {
		if($this->data->getConfig()->getTreeMode() == 'inner') {
			return $this->node['_hasGrandChildren'] == 1;
		} else {
			return $this->node['_hasChildren'] == 1;
		}
	}

	public function isOpen() {
		$children = $this->tree->children[$this->key];
		if(!$children) {
			return false;
		}
		reset($children);
		return isset($this->tree->nodes[key($children)]);
	}

	public function getChildrenIterator() {
		if(!$this->isOpen()) {
			return new EmptyIterator();
		}
		$children = array();
		foreach($this->tree->children[$this->key] as $key => $_) {
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
