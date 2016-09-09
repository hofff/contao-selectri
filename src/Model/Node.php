<?php

namespace Hofff\Contao\Selectri\Model;

interface Node {

	/**
	 * @return string
	 */
	public function getKey();

	/**
	 * @return array
	 */
	public function getData();

	/**
	 * @return string
	 */
	public function getLabel();

	/**
	 * @return string
	 */
	public function getContent();

	/**
	 * @param string $key
	 * @return string
	 */
	public function getAdditionalInputName($key);

	/**
	 * @return string
	 */
	public function getIcon();

	/**
	 * @return boolean
	 */
	public function isSelectable();

	/**
	 * @return boolean
	 */
	public function hasPath();

	/**
	 * @return \Iterator<Node>
	 */
	public function getPathIterator();

	/**
	 * @return boolean
	 */
	public function hasItems();

	/**
	 * @return \Iterator<Node>
	 */
	public function getItemIterator();

	/**
	 * @return boolean
	 */
	public function hasSelectableDescendants();

	/**
	 * @return boolean
	 */
	public function isOpen();

	/**
	 * @return \Iterator<Node>
	 */
	public function getChildrenIterator();

}
