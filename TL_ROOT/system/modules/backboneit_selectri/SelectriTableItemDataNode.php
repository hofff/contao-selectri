<?php

class SelectriTableItemDataNode implements SelectriNode {

	protected $data;
	protected $key;
	protected $node;

	public function __construct(SelectriTableItemData $data, $node) {
		$this->data = $data;
		$this->key  = $node['_key'];
		$this->node = $node;
	}

	public function getKey() {
		return $this->key;
	}

	public function getData() {
		return $this->node;
	}

	public function getLabel() {
		return $this->data->generateLabel($this);
	}

	public function getContent() {
		return $this->data->generateContent($this);
	}

	public function getAdditionalInputName($key) {
		$name = $this->data->getWidget()->getAdditionalInputBaseName();
		$name .= '[' . $this->getKey() . ']';
		$name .= '[' . $key . ']';
		return $name;
	}

	public function getIcon() {
		return $this->data->generateIcon($this);
	}

	public function isSelectable() {
		return $this->node['_isSelectable'];
	}

	public function hasSelectableDescendants() {
		return false;
	}

	public function isOpen() {
		return false;
	}

	public function getChildrenIterator() {
		return new EmptyIterator();
	}

	public function hasItems() {
		return false;
	}

	public function getItemIterator() {
		return new EmptyIterator();
	}

	public function hasPath() {
		return false;
	}

	public function getPathIterator() {
		return new EmptyIterator();
	}

	public function getPathKeys() {
		return array();
	}

}
