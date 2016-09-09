<?php

namespace Hofff\Contao\Selectri\Model\Flat;

use Hofff\Contao\Selectri\Model\Node;

class SQLListNode implements Node {

	/**
	 * @var SQLListData
	 */
	protected $data;

	/**
	 * @var string
	 */
	protected $key;

	/**
	 * @var array
	 */
	protected $node;

	/**
	 * @param SQLListData $data
	 * @param array $node
	 */
	public function __construct(SQLListData $data, array $node) {
		$this->data = $data;
		$this->key  = $node['_key'];
		$this->node = $node;
	}

	/**
	 * @see \Hofff\Contao\Selectri\Model\Node::getKey()
	 */
	public function getKey() {
		return $this->key;
	}

	/**
	 * @see \Hofff\Contao\Selectri\Model\Node::getData()
	 */
	public function getData() {
		return $this->node;
	}

	/**
	 * @see \Hofff\Contao\Selectri\Model\Node::getLabel()
	 */
	public function getLabel() {
		$data = $this->data;
		$config = $data->getConfig();
		$callback = $config->getLabelCallback();
		return call_user_func($callback, $this, $data, $config);
	}

	/**
	 * @see \Hofff\Contao\Selectri\Model\Node::getContent()
	 */
	public function getContent() {
		$data = $this->data;
		$config = $data->getConfig();
		$callback = $config->getContentCallback();
		return $callback ? call_user_func($callback, $this, $data, $config) : '';
	}

	/**
	 * @see \Hofff\Contao\Selectri\Model\Node::getIcon()
	 */
	public function getIcon() {
		$data = $this->data;
		$config = $data->getConfig();
		$callback = $config->getIconCallback();
		return call_user_func($callback, $this, $data, $config);
	}

	/**
	 * @see \Hofff\Contao\Selectri\Model\Node::getAdditionalInputName()
	 */
	public function getAdditionalInputName($key) {
		$name = $this->data->getWidget()->getAdditionalInputBaseName();
		$name .= '[' . $this->getKey() . ']';
		$name .= '[' . $key . ']';
		return $name;
	}

	/**
	 * @see \Hofff\Contao\Selectri\Model\Node::isSelectable()
	 */
	public function isSelectable() {
		return $this->node['_isSelectable'];
	}

	/**
	 * @see \Hofff\Contao\Selectri\Model\Node::hasSelectableDescendants()
	 */
	public function hasSelectableDescendants() {
		return false;
	}

	/**
	 * @see \Hofff\Contao\Selectri\Model\Node::isOpen()
	 */
	public function isOpen() {
		return false;
	}

	/**
	 * @see \Hofff\Contao\Selectri\Model\Node::getChildrenIterator()
	 */
	public function getChildrenIterator() {
		return new \EmptyIterator;
	}

	/**
	 * @see \Hofff\Contao\Selectri\Model\Node::hasItems()
	 */
	public function hasItems() {
		return false;
	}

	/**
	 * @see \Hofff\Contao\Selectri\Model\Node::getItemIterator()
	 */
	public function getItemIterator() {
		return new \EmptyIterator;
	}

	/**
	 * @see \Hofff\Contao\Selectri\Model\Node::hasPath()
	 */
	public function hasPath() {
		return false;
	}

	/**
	 * @see \Hofff\Contao\Selectri\Model\Node::getPathIterator()
	 */
	public function getPathIterator() {
		return new \EmptyIterator;
	}

}
