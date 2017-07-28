<?php

namespace Hofff\Contao\Selectri\Model\Chain;

use Hofff\Contao\Selectri\Widget;
use Hofff\Contao\Selectri\Exception\SelectriException;
use Hofff\Contao\Selectri\Model\DataFactory;

class ChainDataFactory implements DataFactory {

	/**
	 * @var DataFactory[]|array
	 */
	private $dataProviderFactories;

	/**
	 * @param array $dataProviderFactories
	 */
	public function __construct(array $dataProviderFactories = null) {
		$this->dataProviderFactories = (array) $dataProviderFactories;
	}

	/**
	 * @return void
	 */
	public function __clone() {
		foreach($this->dataProviderFactories as &$factory) {
			$factory = clone $factory;
		}
	}

	/**
	 * @see \Hofff\Contao\Selectri\Model\DataFactory::setParameters()
	 */
	public function setParameters($params) {
	}

	/**
	 * @see \Hofff\Contao\Selectri\Model\DataFactory::createData()
	 */
	public function createData(Widget $widget = null) {
		if(!$widget) {
			throw new SelectriException('Selectri widget is required to create a ChainData');
		}

		$providers = [];
		foreach($this->dataProviderFactories as $factory) {
			$providers[] = $factory->createData($widget);
		}

		return new ChainData($widget, $providers);
	}

	/**
	 * @param DataFactory $factory
	 */
	public function addDataProviderFactory(DataFactory $factory) {
		$this->dataProviderFactories[] = $factory;
	}
}
