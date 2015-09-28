<?php

namespace Hofff\Contao\Selectri\Model;

use Hofff\Contao\Selectri\Widget;

abstract class AbstractData implements Data {

	/**
	 * @var Widget
	 */
	private $widget;

	/**
	 * @param Widget $widget
	 */
	public function __construct(Widget $widget = null) {
		$this->widget = $widget;
	}

	/**
	 * @see \Hofff\Contao\Selectri\Model\Data::getWidget()
	 */
	public function getWidget() {
		return $this->widget;
	}

	/**
	 * @param Widget $widget
	 */
	protected function setWidget(Widget $widget) {
		$this->widget = $widget;
	}

	/**
	 * @see \Hofff\Contao\Selectri\Model\Data::isBrowsable()
	 */
	public function isBrowsable() {
		return false;
	}

	/**
	 * @see \Hofff\Contao\Selectri\Model\Data::browseFrom()
	 */
	public function browseFrom($key = null) {
		return new \EmptyIterator;
	}

	/**
	 * @see \Hofff\Contao\Selectri\Model\Data::browseTo()
	 */
	public function browseTo($key) {
		return new \EmptyIterator;
	}

	/**
	 * @see \Hofff\Contao\Selectri\Model\Data::isSearchable()
	 */
	public function isSearchable() {
		return false;
	}

	/**
	 * @see \Hofff\Contao\Selectri\Model\Data::search()
	 */
	public function search($query, $limit, $offset = 0) {
		return new \EmptyIterator;
	}


	/**
	 * @see \Hofff\Contao\Selectri\Model\Data::hasSuggestions()
	 */
	public function hasSuggestions() {
		return false;
	}

	/**
	 * @see \Hofff\Contao\Selectri\Model\Data::suggest()
	 */
	public function suggest($limit, $offset = 0) {
		return new \EmptyIterator;
	}

}
