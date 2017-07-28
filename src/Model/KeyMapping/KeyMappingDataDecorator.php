<?php

namespace Hofff\Contao\Selectri\Model\KeyMapping;

use Hofff\Contao\Selectri\Model\Data;
use Hofff\Contao\Selectri\Model\DataDelegate;

class KeyMappingDataDecorator extends DataDelegate {

	/**
	 * @var KeyMapper
	 */
	private $mapper;

	/**
	 * @param Data $delegate
	 * @param KeyMapper $mapper
	 */
	public function __construct(Data $delegate, KeyMapper $mapper) {
		parent::__construct($delegate);

		$this->mapper = $mapper;
	}

	/**
	 * @return callable
	 */
	public function getMapper() {
		return $this->mapper;
	}

	/**
	 * @see \Hofff\Contao\Selectri\Model\DataDelegate::getNodes()
	 */
	public function getNodes(array $keys, $selectableOnly = true) {
		$localKeys = $this->mapGlobalToLocal($keys);
		$localIterator = parent::getNodes($localKeys, $selectableOnly);

		return new KeyMappingNodeIterator($localIterator, $this->mapper);
	}

	/**
	 * @see \Hofff\Contao\Selectri\Model\DataDelegate::filter()
	 */
	public function filter(array $keys, $selectableOnly = true) {
		$keys = $this->mapGlobalToLocal($keys);
		$keys = parent::filter($keys, $selectableOnly);
		$keys = $this->mapLocalToGlobal($keys);

		return $keys;
	}

	/**
	 * @see \Hofff\Contao\Selectri\Model\DataDelegate::browseFrom()
	 */
	public function browseFrom($key = null) {
		$localKey = $this->mapper->globalToLocal($key);
		list($localIterator, $localStartKey) = parent::browseFrom($localKey);

		$iterator = new KeyMappingNodeIterator($localIterator, $this->mapper);
		$startKey = $this->mapper->localToGlobal($localStartKey);

		return [ $iterator, $startKey ];
	}

	/**
	 * @see \Hofff\Contao\Selectri\Model\DataDelegate::browseTo()
	 */
	public function browseTo($key) {
		$localKey = $this->mapper->globalToLocal($key);
		$localIterator = parent::browseTo($localKey);

		return new KeyMappingNodeIterator($localIterator, $this->mapper);
	}

	/**
	 * @see \Hofff\Contao\Selectri\Model\DataDelegate::search()
	 */
	public function search($query, $limit, $offset = 0) {
		$localIterator = parent::search($query, $limit, $offset);

		return new KeyMappingNodeIterator($localIterator, $this->mapper);
	}

	/**
	 * @see \Hofff\Contao\Selectri\Model\Data::suggest()
	 */
	public function suggest($limit, $offset = 0) {
		$localIterator = parent::suggest($limit, $offset);

		return new KeyMappingNodeIterator($localIterator, $this->mapper);
	}

	/**
	 * @param string[]|array $global
	 * @return string[]
	 */
	protected function mapLocalToGlobal(array $local) {
		$global = [];
		foreach($local as $key) {
			$global[] = $this->mapper->localToGlobal($key);
		}

		return $global;
	}

	/**
	 * @param string[]|array $global
	 * @return string[]
	 */
	protected function mapGlobalToLocal(array $global) {
		$local = [];
		foreach($global as $key) {
			$local[] = $this->mapper->globalToLocal($key);
		}

		return $local;
	}

}
