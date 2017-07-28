<?php

namespace Hofff\Contao\Selectri\Model\Chain;

use Hofff\Contao\Selectri\Widget;
use Hofff\Contao\Selectri\Exception\SelectriException;
use Hofff\Contao\Selectri\Model\AbstractData;
use Hofff\Contao\Selectri\Model\Data;

class ChainData extends AbstractData {

	/**
	 * @var Data[]|array
	 */
	protected $dataProviders;

	/**
	 * @param Widget $widget
	 * @param Data[]|array $dataProviders
	 */
	public function __construct(Widget $widget, array $dataProviders) {
		parent::__construct($widget);
		$this->dataProviders = $dataProviders;
	}

	/**
	 * @see \Hofff\Contao\Selectri\Model\Data::validate()
	 */
	public function validate() {
		foreach($this->dataProviders as $provider) {
			$provider->validate();
		}
	}

	/**
	 * @see \Hofff\Contao\Selectri\Model\Data::getNodes()
	 */
	public function getNodes(array $keys, $selectableOnly = true) {
		$iterator = new \AppendIterator();

		foreach($this->dataProviders as $provider) {
			$iterator->append($provider->getNodes($keys, $selectableOnly));
		}

		return $iterator;
	}

	/**
	 * @see \Hofff\Contao\Selectri\Model\Data::filter()
	 */
	public function filter(array $keys, $selectableOnly = true) {
		$validKeys = [];

		foreach($this->dataProviders as $provider) {
			foreach($provider->filter($keys, $selectableOnly) as $validKey) {
				$validKeys[] = $validKey;
			}
		}

		return array_intersect($keys, $validKeys);
	}

	/**
	 * @see \Hofff\Contao\Selectri\Model\AbstractData::isBrowsable()
	 */
	public function isBrowsable() {
		foreach($this->dataProviders as $provider) {
			if($provider->isBrowsable()) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @see \Hofff\Contao\Selectri\Model\AbstractData::browseFrom()
	 */
	public function browseFrom($key = null) {
		if($key !== null) {
			return $this->findDataProviderFor($key)->browseFrom($key);
		}

		$iterator = new \AppendIterator();

		foreach($this->dataProviders as $provider) {
			$iterator->append($provider->browseFrom()[0]);
		}

		return [ $iterator, null ];
	}

	/**
	 * @see \Hofff\Contao\Selectri\Model\AbstractData::browseTo()
	 */
	public function browseTo($key) {
		$targetProvider = $this->findDataProviderFor($key);
		$iterator = new \AppendIterator();

		$unfolded = $this->getWidget()->getUnfolded();
		$this->getWidget()->setUnfolded([]);

		foreach($this->dataProviders as $provider) {
			if($provider !== $targetProvider) {
				$iterator->append($provider->browseFrom()[0]);
				continue;
			}

			$this->getWidget()->setUnfolded($unfolded);
			$iterator->append($provider->browseTo($key));
			$this->getWidget()->setUnfolded([]);
		}

		$this->getWidget()->setUnfolded($unfolded);

		return $iterator;
	}

	/**
	 * @param string $key
	 * @throws SelectriException
	 * @return Data
	 */
	protected function findDataProviderFor($key) {
		foreach($this->dataProviders as $provider) {
			if($provider->filter([ $key ], false)) {
				return $provider;
			}
		}

		throw new SelectriException(sprintf('Unknown node key "%s"', $key));
	}

	/**
	 * @see \Hofff\Contao\Selectri\Model\AbstractData::isSearchable()
	 */
	public function isSearchable() {
		foreach($this->dataProviders as $provider) {
			if($provider->isSearchable()) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @see \Hofff\Contao\Selectri\Model\AbstractData::search()
	 */
	public function search($query, $limit, $offset = 0) {
		$iterator = new \AppendIterator();

		foreach($this->dataProviders as $provider) {
			if(!$provider->isSearchable()) {
				continue;
			}

			$iterator->append($provider->search($query, $limit, $offset));
		}

		return $iterator;
	}

	/**
	 * @see \Hofff\Contao\Selectri\Model\AbstractData::hasSuggestions()
	 */
	public function hasSuggestions() {
		foreach($this->dataProviders as $provider) {
			if($provider->hasSuggestions()) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @see \Hofff\Contao\Selectri\Model\AbstractData::suggest()
	 */
	public function suggest($limit, $offset = 0) {
		$iterator = new \AppendIterator();

		foreach($this->dataProviders as $provider) {
			if(!$provider->hasSuggestions()) {
				continue;
			}

			$iterator->append($provider->suggest($limit, $offset));
		}

		return $iterator;
	}

}
