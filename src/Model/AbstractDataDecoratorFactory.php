<?php

namespace Hofff\Contao\Selectri\Model;

use Hofff\Contao\Selectri\Model\Data;
use Hofff\Contao\Selectri\Model\DataFactory;
use Hofff\Contao\Selectri\Widget;

abstract class AbstractDataDecoratorFactory implements DataFactory {

	/**
	 * @var DataFactory
	 */
	private $factory;

	/**
	 * @param Data $delegate
	 */
	public function __construct(DataFactory $factory) {
		$this->factory = $factory;
	}

	/**
	 * @param DataFactory $factory
	 * @return void
	 */
	public function getDecoratedDataFactory() {
		return $this->factory;
	}

	/**
	 * @see \Hofff\Contao\Selectri\Model\DataFactory::setParameters()
	 */
	public function setParameters($params) {
		$this->getDecoratedDataFactory()->setParameters($params);
	}

	/**
	 * @see \Hofff\Contao\Selectri\Model\DataFactory::createData()
	 */
	public function createData(Widget $widget = null) {
		$decoratedData = $this->getDecoratedDataFactory()->createData($widget);
		return $this->createDecorator($decoratedData);
	}

	/**
	 * @param Data $decoratedData
	 * @return Data
	 */
	protected abstract function createDecorator(Data $decoratedData);

}
