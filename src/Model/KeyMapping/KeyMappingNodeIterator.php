<?php

namespace Hofff\Contao\Selectri\Model\KeyMapping;

class KeyMappingNodeIterator extends \IteratorIterator {

	/**
	 * @var KeyMapper
	 */
	private $mapper;

	/**
	 * @param \Iterator $inner
	 * @param KeyMapper $mapper
	 */
	public function __construct(\Iterator $inner, KeyMapper $mapper) {
		parent::__construct($inner);

		$this->mapper = $mapper;
	}

	/**
	 * @return \Hofff\Contao\Selectri\Model\KeyMapping\KeyMapper
	 */
	public function getMapper() {
		return $this->mapper;
	}

	/**
	 * @see \IteratorIterator::current()
	 */
	public function current() {
		return new KeyMappedNode(parent::current(), $this->mapper);
	}

}
