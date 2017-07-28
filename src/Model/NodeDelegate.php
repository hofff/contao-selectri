<?php

namespace Hofff\Contao\Selectri\Model;

abstract class NodeDelegate implements Node {

	/**
	 * @var Node
	 */
	private $delegate;

	/**
	 * @param Node $delegate
	 */
	protected function __construct(Node $delegate = null) {
		$this->delegate = $delegate;
	}

	/**
	 * @return Node
	 */
	public function getDelegate() {
		return $this->delegate;
	}

	/**
	 * @param Node $delegate
	 * @return void
	 */
	protected function setDelegate(Node $delegate) {
		$this->delegate = $delegate;
	}

	/**
	 * @see \Hofff\Contao\Selectri\Model\Node::getAdditionalInputName()
	 */
	public function getAdditionalInputName($key) {
		return $this->delegate->getAdditionalInputName($key);
	}

	/**
	 * @see \Hofff\Contao\Selectri\Model\Node::getChildrenIterator()
	 */
	public function getChildrenIterator() {
		return $this->delegate->getChildrenIterator();
	}

	/**
	 * @see \Hofff\Contao\Selectri\Model\Node::getContent()
	 */
	public function getContent() {
		return $this->delegate->getContent();
	}

	/**
	 * @see \Hofff\Contao\Selectri\Model\Node::getData()
	 */
	public function getData() {
		return $this->delegate->getData();
	}

	/**
	 * @see \Hofff\Contao\Selectri\Model\Node::getIcon()
	 */
	public function getIcon() {
		return $this->delegate->getIcon();
	}

	/**
	 * @see \Hofff\Contao\Selectri\Model\Node::getItemIterator()
	 */
	public function getItemIterator() {
		return $this->delegate->getItemIterator();
	}

	/**
	 * @see \Hofff\Contao\Selectri\Model\Node::getKey()
	 */
	public function getKey() {
		return $this->delegate->getKey();
	}

	/**
	 * @see \Hofff\Contao\Selectri\Model\Node::getLabel()
	 */
	public function getLabel() {
		return $this->delegate->getLabel();
	}

	/**
	 * @see \Hofff\Contao\Selectri\Model\Node::getPathIterator()
	 */
	public function getPathIterator() {
		return $this->delegate->getPathIterator();
	}

	/**
	 * @see \Hofff\Contao\Selectri\Model\Node::hasItems()
	 */
	public function hasItems() {
		return $this->delegate->hasItems();
	}

	/**
	 * @see \Hofff\Contao\Selectri\Model\Node::hasPath()
	 */
	public function hasPath() {
		return $this->delegate->hasPath();
	}

	/**
	 * @see \Hofff\Contao\Selectri\Model\Node::hasSelectableDescendants()
	 */
	public function hasSelectableDescendants() {
		return $this->delegate->hasSelectableDescendants();
	}

	/**
	 * @see \Hofff\Contao\Selectri\Model\Node::isOpen()
	 */
	public function isOpen() {
		return $this->delegate->isOpen();
	}

	/**
	 * @see \Hofff\Contao\Selectri\Model\Node::isSelectable()
	 */
	public function isSelectable() {
		return $this->delegate->isSelectable();
	}

}
