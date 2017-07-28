<?php

namespace Hofff\Contao\Selectri\Model\KeyMapping;

use Hofff\Contao\Selectri\Model\Node;
use Hofff\Contao\Selectri\Model\NodeDelegate;

class KeyMappedNode extends NodeDelegate {

	/**
	 * @var KeyMapper
	 */
	private $mapper;

	/**
	 * @param Node $delegate
	 * @param KeyMapper $mapper
	 */
	public function __construct(Node $delegate, KeyMapper $mapper) {
		parent::__construct($delegate);

		$this->mapper = $mapper;
	}

	/**
	 * @return \Hofff\Contao\Selectri\Model\KeyMapping\KeyMapper
	 */
	public function getMapper() {
		return $this->mapper;
	}

	/**
	 * @see \Hofff\Contao\Selectri\Model\NodeDelegate::getKey()
	 */
	public function getKey() {
		return $this->mapper->localToGlobal(parent::getKey());
	}

	/**
	 * @see \Hofff\Contao\Selectri\Model\NodeDelegate::getChildrenIterator()
	 */
	public function getChildrenIterator() {
		return new KeyMappingNodeIterator(parent::getChildrenIterator(), $this->mapper);
	}

	/**
	 * @see \Hofff\Contao\Selectri\Model\NodeDelegate::getItemIterator()
	 */
	public function getItemIterator() {
		return new KeyMappingNodeIterator(parent::getItemIterator(), $this->mapper);
	}

	/**
	 * @see \Hofff\Contao\Selectri\Model\NodeDelegate::getPathIterator()
	 */
	public function getPathIterator() {
		return new KeyMappingNodeIterator(parent::getPathIterator(), $this->mapper);
	}

}
