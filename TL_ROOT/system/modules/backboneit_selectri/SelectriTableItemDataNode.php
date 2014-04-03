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

	public function getLabel() {
		return $this->data->generateLabel($this->node);
	}

	public function getContent() {
		return $this->data->generateItemContent($this->node);
	}

	public function getAdditionalInputName($key) {
		$name = $this->data->getWidget()->getAdditionalInputBaseName();
		$name .= '[' . $this->getKey() . ']';
		$name .= '[' . $key . ']';
		return $name;
	}

	public function getIcon() {
		return $this->data->generateIcon($this->node);
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
