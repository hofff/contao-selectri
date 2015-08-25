<?php

namespace Hofff\Contao\Selectri\Model\Tree;

class Tree {

	/**
	 * A map of ordered identity maps mapping tree keys to their children lists
	 * @var array<string, array<string, string>>
	 */
	public $children = array();

	/**
	 * A map of tree keys mapping to their parent tree keys
	 * @var array<string, string>
	 */
	public $parents = array();

	/**
	 * A map of tree keys mapping to arbitrary data assoicated with this node
	 * @var array<string, mixed>
	 */
	public $nodes = array();

	/**
	 * @var string
	 */
	private $rootValue;

	/**
	 */
	public function __construct($rootValue) {
		$this->rootValue = strval($rootValue);
	}

	/**
	 * @return string
	 */
	public function getRootValue() {
		return $this->rootValue;
	}

	/**
	 * Builds a parents map from this tree's children map
	 * @return array<string, string>
	 */
	public function getParentsFromChildren() {
		$parents = array();

		foreach($this->children as $parentKey => $children) {
			foreach($children as $key => $_) {
				$parents[$key] = $parentKey;
			}
		}

		return $parents;
	}

	/**
	 * Returns the given node IDs of the given tree in preorder,
	 * optionally removing nested node IDs.
	 *
	 * Removes duplicates.
	 *
	 * @param array $keys
	 * @param boolean $unnest
	 * @return array
	 */
	public function getPreorder(array $keys, $unnest = false) {
		if(!$keys) {
			return array();
		}

		$rootValue = $this->getRootValue();
		$keys = array_flip(array_map('strval', $keys));
		$preorder = array();

		if(isset($keys[$rootValue])) {
			$preorder[] = $rootValue;
			if($unnest) {
				return $preorder;
			}
		}

		$this->getPreorderHelper($preorder, $keys, $unnest, $rootValue);

		return $preorder;
	}

	/**
	 * @param array $preorder
	 * @param array $keys
	 * @param boolean $unnest
	 * @param string $parentKey
	 * @return void
	 */
	private function getPreorderHelper(array &$preorder, array $keys, $unnest, $parentKey) {
		if(!isset($this->children[$parentKey]) || !count($this->children[$parentKey])) {
			return;
		}

		foreach($this->children[$parentKey] as $key => $_) {
			if(isset($keys[$key])) {
				$preorder[] = $key;
				if($unnest) {
					continue;
				}
			}
			$this->getPreorderHelper($preorder, $keys, $unnest, $key);
		}
	}

	/**
	 * Returns the descendants of each of the given node IDs of the given tree
	 * in preorder, optionally adding the given node IDs themselves.
	 * Duplicates are not removed, invalid and nested nodes are not removed. Use
	 * getPreorder(..) with $unnest set to true before calling this method,
	 * if this is the desired behavior.
	 *
	 * @param array $keys
	 * @param boolean $andSelf
	 * @return array<string>
	 */
	public function getDescendantsPreorder(array $keys, $andSelf = false) {
		$preorder = array();

		foreach(array_map('strval', $keys) as $key) {
			$andSelf && $preorder[] = $key;
			$this->getDescendantsPreorderHelper($preorder, $key);
		}

		return $preorder;
	}

	/**
	 * @param array $preorder
	 * @param string $parentKey
	 * @return void
	 */
	private function getDescendantsPreorderHelper(array &$preorder, $parentKey) {
		if(!isset($this->children[$parentKey]) || !count($this->children[$parentKey])) {
			return;
		}

		foreach($this->children[$parentKey] as $key => $_) {
			$preorder[] = $key;
			$this->getDescendantsPreorderHelper($preorder, $key);
		}
	}

}
