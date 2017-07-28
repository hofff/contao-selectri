<?php

namespace Hofff\Contao\Selectri\Model\KeyMapping;

use Hofff\Contao\Selectri\Model\AbstractDataDecoratorFactory;
use Hofff\Contao\Selectri\Model\Data;
use Hofff\Contao\Selectri\Model\DataFactory;

class KeyMappingDataDecoratorFactory extends AbstractDataDecoratorFactory {

	/**
	 * @var KeyMapper
	 */
	private $mapper;

	/**
	 * @param DataFactory $delegate
	 * @param KeyMapper $mapper
	 */
	public function __construct(DataFactory $delegate, KeyMapper $mapper = null) {
		parent::__construct($delegate);

		$this->mapper = $mapper;
	}

	/**
	 * @return KeyMapper|null
	 */
	public function getKeyMapper() {
		return $this->mapper;
	}

	/**
	 * @param KeyMapper $mapper
	 * @return void
	 */
	public function setKeyMapper(KeyMapper $mapper) {
		$this->mapper = $mapper;
	}

	/**
	 * @param callable $localToGlobal
	 * @param callable $globalToLocal
	 * @return void
	 */
	public function setKeyMapperCallbacks(callable $localToGlobal, callable $globalToLocal) {
		$this->mapper = new CallbackKeyMapper($localToGlobal, $globalToLocal);
	}

	/**
	 * @see \Hofff\Contao\Selectri\Model\AbstractDataDecoratorFactory::createDecorator()
	 */
	public function createDecorator(Data $decoratedData) {
		if(!isset($this->mapper)) {
			throw new \LogicException('No KeyMapper set');
		}

		return new KeyMappingDataDecorator($decoratedData, $this->mapper);
	}

}
