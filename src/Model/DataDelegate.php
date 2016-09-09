<?php

namespace Hofff\Contao\Selectri\Model;

abstract class DataDelegate implements Data {

	/**
	 * @var Data
	 */
	private $delegate;

	/**
	 * @param Data $delegate
	 */
	protected function __construct(Data $delegate = null) {
		$this->delegate = $delegate;
	}

	/**
	 * @return Data
	 */
	public function getDelegate() {
		return $this->delegate;
	}

	/**
	 * @param Data $delegate
	 * @return void
	 */
	protected function setDelegate(Data $delegate) {
		$this->delegate = $delegate;
	}

	/**
	 * @see \Hofff\Contao\Selectri\Model\Data::getWidget()
	 */
	public function getWidget() {
		return $this->delegate->getWidget();
	}

	/**
	 * @see \Hofff\Contao\Selectri\Model\Data::validate()
	 */
	public function validate() {
		$this->delegate->validate();
	}

	/**
	 * @see \Hofff\Contao\Selectri\Model\Data::getNodes()
	 */
	public function getNodes(array $keys, $selectableOnly = true) {
		return $this->delegate->getNodes($keys, $selectableOnly);
	}

	/**
	 * @see \Hofff\Contao\Selectri\Model\Data::filter()
	 */
	public function filter(array $keys) {
		return $this->delegate->filter($keys);
	}

	/**
	 * @see \Hofff\Contao\Selectri\Model\Data::isBrowsable()
	 */
	public function isBrowsable() {
		return $this->delegate->isBrowsable();
	}

	/**
	 * @see \Hofff\Contao\Selectri\Model\Data::browseFrom()
	 */
	public function browseFrom($key = null) {
		return $this->delegate->browseFrom($key);
	}

	/**
	 * @see \Hofff\Contao\Selectri\Model\Data::browseTo()
	 */
	public function browseTo($key) {
		return $this->delegate->browseTo($key);
	}

	/**
	 * @see \Hofff\Contao\Selectri\Model\Data::isSearchable()
	 */
	public function isSearchable() {
		return $this->delegate->isSearchable();
	}

	/**
	 * @see \Hofff\Contao\Selectri\Model\Data::search()
	 */
	public function search($query, $limit, $offset = 0) {
		return $this->delegate->search($query, $limit, $offset);
	}

	/**
	 * @see \Hofff\Contao\Selectri\Model\Data::hasSuggestions()
	 */
	public function hasSuggestions() {
		return $this->delegate->hasSuggestions();
	}

	/**
	 * @see \Hofff\Contao\Selectri\Model\Data::suggest()
	 */
	public function suggest($limit, $offset = 0) {
		return $this->delegate->suggest($limit, $offset);
	}

}
