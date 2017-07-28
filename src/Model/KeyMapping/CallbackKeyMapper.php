<?php

namespace Hofff\Contao\Selectri\Model\KeyMapping;

class CallbackKeyMapper implements KeyMapper {

	/**
	 * @var callable
	 */
	private $localToGlobal;

	/**
	 * @var callable
	 */
	private $globalToLocal;

	/**
	 * @param callable $localToGlobal
	 * @param callable $globalToLocal
	 */
	public function __construct(callable $localToGlobal, callable $globalToLocal) {
		$this->localToGlobal = $localToGlobal;
		$this->globalToLocal = $globalToLocal;
	}

	/**
	 * @param string|null $key
	 * @return string|null
	 */
	public function localToGlobal($key) {
		return call_user_func($this->localToGlobal, $key);
	}

	/**
	 * @param string|null $key
	 * @return string|null
	 */
	public function globalToLocal($key) {
		return call_user_func($this->globalToLocal, $key);
	}

}
