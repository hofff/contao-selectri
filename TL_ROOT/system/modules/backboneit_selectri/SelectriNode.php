<?php

interface SelectriNode {

	public function getKey();

	public function getLabel();

	public function getContent();

	public function getIcon();

	public function isSelectable();

	public function hasPath();

	public function getPathIterator();

	public function hasItems();

	public function getItemIterator();

	public function hasSelectableChildren();

	public function isOpen();

	public function getChildrenIterator();

}
