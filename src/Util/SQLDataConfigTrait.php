<?php

namespace Hofff\Contao\Selectri\Util;

trait SQLDataConfigTrait {

	/**
	 * @var string
	 */
	private $table;

	/**
	 * @var string
	 */
	private $keyColumn;

	/**
	 * @var array<string>
	 */
	private $columns;

	/**
	 * @var array<string>
	 */
	private $searchColumns;

	/**
	 * @var string
	 */
	private $conditionExpr;

	/**
	 * @var string
	 */
	private $selectableExpr;

	/**
	 * @var string
	 */
	private $orderByExpr;

	/**
	 * @var callable
	 */
	private $labelCallback;

	/**
	 * @var callable
	 */
	private $iconCallback;

	/**
	 * @var callable|null
	 */
	private $contentCallback;

	/**
	 * @return string
	 */
	public function getTable() {
		return $this->table;
	}

	/**
	 * @param string $table
	 * @return void
	 */
	public function setTable($table) {
		$this->table = strval($table);
	}

	/**
	 * @return string
	 */
	public function getKeyColumn() {
		return $this->keyColumn;
	}

	/**
	 * @param string $column
	 * @return void
	 */
	public function setKeyColumn($column) {
		$this->keyColumn = strval($column);
	}

	/**
	 * @return array<string>
	 */
	public function getColumns() {
		return (array) $this->columns;
	}

	/**
	 * @param array<string>|null $columns
	 * @return void
	 */
	public function setColumns($columns) {
		$this->columns = SQLUtil::getCleanedColumns($columns);
	}

	/**
	 * @param array<string>|string $columns
	 * @return void
	 */
	public function addColumns($columns) {
		return $this->setColumns(array_merge($this->getColumns(), (array) $columns));
	}

	/**
	 * @return array<string>
	 */
	public function getSearchColumns() {
		return (array) $this->searchColumns;
	}

	/**
	 * @param array<string>|null $columns
	 * @return void
	 */
	public function setSearchColumns($columns) {
		$this->searchColumns = SQLUtil::getCleanedColumns($columns);
	}

	/**
	 * @param array<string>|string $columns
	 * @return void
	 */
	public function addSearchColumns($columns) {
		return $this->setSearchColumns(array_merge($this->getSearchColumns(), (array) $columns));
	}

	/**
	 * @param string $clause
	 * @return string
	 */
	public function getConditionExpr($clause = null) {
		$expr = strval($this->conditionExpr);
		$clause = strval($clause);
		strlen($expr) && strlen($clause) && $expr = $clause . ' (' . $expr . ')';
		return $expr;
	}

	/**
	 * @param string $expr
	 * @return void
	 */
	public function setConditionExpr($expr) {
		$this->conditionExpr = $expr;
	}

	/**
	 * @param string $clause
	 * @return string
	 */
	public function getSelectableExpr($clause = null) {
		$expr = strval($this->selectableExpr);
		$clause = strval($clause);
		strlen($expr) && strlen($clause) && $expr = $clause . ' (' . $expr . ')';
		return $expr;
	}

	/**
	 * @param string $expr
	 * @return void
	 */
	public function setSelectableExpr($expr) {
		$this->selectableExpr = $expr;
	}

	/**
	 * @param string $clause
	 * @return string
	 */
	public function getOrderByExpr($clause = null) {
		$expr = strval($this->orderByExpr);
		$clause = strval($clause);
		strlen($expr) && strlen($clause) && $expr = $clause . ' ' . $expr;
		return $expr;
	}

	/**
	 * @param string $expr
	 * @return void
	 */
	public function setOrderByExpr($expr) {
		$this->orderByExpr = strval($expr);
	}

	/**
	 * @return callable
	 */
	public function getLabelCallback() {
		return $this->labelCallback;
	}

	/**
	 * @param callable $callback
	 * @return void
	 */
	public function setLabelCallback($callback) {
		$this->labelCallback = $callback;
	}

	/**
	 * @return callable
	 */
	public function getIconCallback() {
		return $this->iconCallback;
	}

	/**
	 * @param callable $callback
	 * @return void
	 */
	public function setIconCallback($callback) {
		$this->iconCallback = $callback;
	}

	/**
	 * @return callable|null
	 */
	public function getContentCallback() {
		return $this->contentCallback;
	}

	/**
	 * @param callable|null $callback
	 * @return void
	 */
	public function setContentCallback($callback) {
		$this->contentCallback = $callback;
	}

}
