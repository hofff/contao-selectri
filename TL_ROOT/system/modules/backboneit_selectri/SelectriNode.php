<?php

interface SelectriNode {

	public function getKey();

	public function getData();

	public function getLabel();

	public function getContent();

	public function getAdditionalInputName($key);

	public function getIcon();

	public function isSelectable();

	public function hasPath();

	public function getPathIterator();

	public function hasItems();

	public function getItemIterator();

	public function hasSelectableDescendants();

	public function isOpen();

	public function getChildrenIterator();

}
