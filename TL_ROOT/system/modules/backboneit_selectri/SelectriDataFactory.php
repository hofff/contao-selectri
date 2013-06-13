<?php

interface SelectriDataFactory {

	/**
	 * @param mixed $params Configuration parameters (usally the eval array of
	 * 		the DCA field the widget using this factory)
	 * @return SelectriDataFactory This factory
	 */
	public function setParameters($params);

	/**
	 * @param SelectriWidget $widget The widget the created data instances will
	 * 		belong to
	 * @return SelectriDataFactory This factory
	 */
	public function setWidget(SelectriWidget $widget);

	/**
	 * @return SelectriData A new data instance
	 */
	public function createData();

}
