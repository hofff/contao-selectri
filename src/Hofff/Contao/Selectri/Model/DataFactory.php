<?php

namespace Hofff\Contao\Selectri\Model;

use Hofff\Contao\Selectri\Widget;

interface DataFactory {

	/**
	 * @param mixed $params Configuration parameters (usally the eval array of
	 * 		the DCA field the widget using this factory)
	 * @return void
	 */
	public function setParameters($params);

	/**
	 * @param Widget $widget The widget the created data instance will belong to
	 * @return Data A new data instance
	 */
	public function createData(Widget $widget = null);

}
