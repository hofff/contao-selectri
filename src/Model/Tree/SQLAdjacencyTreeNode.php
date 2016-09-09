<?php

namespace Hofff\Contao\Selectri\Model\Tree;

use Hofff\Contao\Selectri\Model\Node;

class SQLAdjacencyTreeNode implements Node {

	/**
	 * @var SQLAdjacencyTreeData
	 */
	protected $data;

	/**
	 * @var \stdClass
	 */
	protected $tree;

	/**
	 * @var string
	 */
	protected $key;

	/**
	 * @var array
	 */
	protected $node;

	/**
	 * @param SQLAdjacencyTreeData $data
	 * @param \stdClass $tree
	 * @param string $key
	 */
	public function __construct(SQLAdjacencyTreeData $data, Tree $tree, $key) {
		$this->data = $data;
		$this->tree = $tree;
		$this->key = $key;
		$this->node = &$this->tree->nodes[$this->key];
	}

	public function getKey() {
		return $this->key;
	}

	public function getData() {
		return $this->node;
	}

	public function getLabel() {
		$data = $this->data;
		$config = $data->getConfig();
		$callback = $config->getLabelCallback();
		return call_user_func($callback, $this, $data, $config);
	}

	public function getContent() {
		$data = $this->data;
		$config = $data->getConfig();
		$callback = $config->getContentCallback();
		return $callback ? call_user_func($callback, $this, $data, $config) : '';
	}

	public function getAdditionalInputName($key) {
		$name = $this->data->getWidget()->getAdditionalInputBaseName();
		$name .= '[' . $this->getKey() . ']';
		$name .= '[' . $key . ']';
		return $name;
	}

	public function getIcon() {
		$data = $this->data;
		$config = $data->getConfig();
		$callback = $config->getIconCallback();
		return call_user_func($callback, $this, $data, $config);
	}

	public function isSelectable() {
		if(!$this->node['_isSelectable']) {
			return false;
		}
		switch($this->data->getConfig()->getSelectionMode()) {
			case SQLAdjacencyTreeDataConfig::SELECTION_MODE_LEAF:
				return !$this->node['_hasChildren'];
				break;

			case SQLAdjacencyTreeDataConfig::SELECTION_MODE_INNER:
				return !!$this->node['_hasChildren'];
				break;

			default:
				return true;
				break;
		}
	}

	public function hasSelectableDescendants() {
		if($this->data->getConfig()->getSelectionMode() == SQLAdjacencyTreeDataConfig::SELECTION_MODE_INNER) {
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
			return new \EmptyIterator;
		}
		$children = array();
		foreach(array_keys($this->tree->children[$this->key]) as $key) {
			$children[] = new self($this->data, $this->tree, $key);
		}
		return new \ArrayIterator($children);
	}

	public function hasItems() {
		return false;
	}

	public function getItemIterator() {
		return new \EmptyIterator;
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
		return new \ArrayIterator(array_reverse($path));
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
