<?php

namespace Hofff\Contao\Selectri\Model\Tree;

class SQLAdjacencyTreeDataConfig {

	use \Hofff\Contao\Selectri\Util\SQLDataConfigTrait;

	/**
	 * @var string
	 */
	const SELECTION_MODE_INNER = 'inner';

	/**
	 * @var string
	 */
	const SELECTION_MODE_LEAF = 'leaf';

	/**
	 * @var string
	 */
	const SELECTION_MODE_ALL = 'all';

	/**
	 * @var string
	 */
	private $parentKeyColumn;

	/**
	 * @var mixed
	 */
	private $rootValue;

	/**
	 * @var array<string>
	 */
	private $roots;

	/**
	 * @var string
	 */
	private $selectionMode;

	/**
	 */
	public function __construct() {
	}

	/**
	 * @return string
	 */
	public function getParentKeyColumn() {
		return $this->parentKeyColumn;
	}

	/**
	 * @param string $column
	 * @return void
	 */
	public function setParentKeyColumn($column) {
		$this->parentKeyColumn = strval($column);
	}

	/**
	 * @return mixed
	 */
	public function getRootValue() {
		return $this->rootValue;
	}

	/**
	 * @param mixed $value
	 */
	public function setRootValue($value) {
		$this->rootValue = $value;
	}

	/**
	 * @return array<string>
	 */
	public function getRoots() {
		return $this->roots ? $this->roots : array($this->getRootValue());
	}

	/**
	 * @param array<string>|null $roots
	 * @return void
	 */
	public function setRoots($roots) {
		$this->roots = array_values((array) $roots);
	}

	/**
	 * @return string
	 */
	public function getSelectionMode() {
		return $this->selectionMode;
	}

	/**
	 * @param string $mode
	 * @return void
	 */
	public function setSelectionMode($mode) {
		switch($mode) {
			case self::SELECTION_MODE_LEAF: break;
			case self::SELECTION_MODE_INNER: break;
			default: $mode = self::SELECTION_MODE_ALL; break;
		}
		$this->selectionMode = $mode;
	}

}
