<?php

namespace Hofff\Contao\Selectri\Model\KeyMapping;

interface KeyMapper {

	/**
	 * @param string|null $key
	 * @return string|null
	 */
	public function localToGlobal($key);

	/**
	 * @param string|null $key
	 * @return string|null
	 */
	public function globalToLocal($key);

}
