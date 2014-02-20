<?php

class SelectriTableDataConfig {

	private $roots;
	private $selectableExpr;

	private $treeMode;
	private $treeTable;
	private $treeKeyColumn;
	private $treeParentKeyColumn;
	private $treeSearchColumns;
	private $treeColumns;
	private $treeRootValue;
	private $treeConditionExpr;
	private $treeOrderByExpr;
	private $treeLabelCallback;
	private $treeIconCallback;

	private $itemTable;
	private $itemKeyColumn;
	private $itemTreeKeyColumn;
	private $itemSearchColumns;
	private $itemColumns;
	private $itemConditionExpr;
	private $itemOrderByExpr;
	private $itemLabelCallback;
	private $itemIconCallback;

	public function __construct() {
	}

	public function getRoots() {
		return $this->roots ? $this->roots : array($this->getTreeRootValue());
	}

	public function setRoots($roots) {
		$this->roots = array_values((array) $roots);
		return $this;
	}

	public function getSelectableExpr($clause = null) {
		$expr = strval($this->selectableExpr);
		$clause = strval($clause);
		strlen($expr) && strlen($clause) && $expr = $clause . ' (' . $expr . ')';
		return $expr;
	}

	public function setSelectableExpr($selectableExpr) {
		$this->selectableExpr = $selectableExpr;
		return $this;
	}

	public function isTreeItemMode() {
		return $this->hasTree() && $this->hasItem();
	}

	public function isTreeOnlyMode() {
		return $this->hasTree() && !$this->hasItem();
	}

	public function isItemOnlyMode() {
		return $this->hasItem() && !$this->hasTree();
	}

	public function hasTree() {
		return strlen($this->getTreeTable()) != 0;
	}

	public function getTreeMode() {
		return $this->treeMode;
	}

	public function setTreeMode($treeMode) {
		switch($treeMode) {
			case 'leaf':
			case 'inner': break;
			default: $treeMode = 'all'; break;
		}
		$this->treeMode = $treeMode;
		return $this;
	}

	public function getTreeTable() {
		return $this->treeTable;
	}

	public function setTreeTable($treeTable) {
		$this->treeTable = strval($treeTable);
		return $this;
	}

	public function getTreeKeyColumn() {
		return $this->treeKeyColumn;
	}

	public function setTreeKeyColumn($treeKeyColumn) {
		$this->treeKeyColumn = strval($treeKeyColumn);
		return $this;
	}

	public function getTreeParentKeyColumn() {
		return $this->treeParentKeyColumn;
	}

	public function setTreeParentKeyColumn($treeParentKeyColumn) {
		$this->treeParentKeyColumn = strval($treeParentKeyColumn);
		return $this;
	}

	public function getTreeSearchColumns() {
		return (array) $this->treeSearchColumns;
	}

	public function setTreeSearchColumns($treeSearchColumns) {
		$this->treeSearchColumns = self::cleanColumns($treeSearchColumns);
		return $this;
	}

	public function addTreeSearchColumns($treeSearchColumns) {
		return $this->setTreeSearchColumns(array_merge($this->getTreeSearchColumns(), (array) $treeSearchColumns));
	}

	public function getTreeColumns() {
		return (array) $this->treeColumns;
	}

	public function setTreeColumns($treeColumns) {
		$this->treeColumns = self::cleanColumns($treeColumns);
		return $this;
	}

	public function addTreeColumns($treeColumns) {
		return $this->setTreeColumns(array_merge($this->getTreeColumns(), (array) $treeColumns));
	}

	public function getTreeRootValue() {
		return $this->treeRootValue;
	}

	public function setTreeRootValue($treeRootValue) {
		$this->treeRootValue = $treeRootValue;
		return $this;
	}

	public function getTreeConditionExpr($clause = null) {
		$expr = strval($this->treeConditionExpr);
		$clause = strval($clause);
		strlen($expr) && strlen($clause) && $expr = $clause . ' (' . $expr . ')';
		return $expr;
	}

	public function setTreeConditionExpr($treeConditionExpr) {
		$this->treeConditionExpr = $treeConditionExpr;
		return $this;
	}

	public function getTreeOrderByExpr($clause = null) {
		$expr = strval($this->treeOrderByExpr);
		$clause = strval($clause);
		strlen($expr) && strlen($clause) && $expr = $clause . ' ' . $expr;
		return $expr;
	}

	public function setTreeOrderByExpr($treeOrderByExpr) {
		$this->treeOrderByExpr = strval($treeOrderByExpr);
		return $this;
	}

	public function getTreeLabelCallback() {
		return $this->treeLabelCallback;
	}

	public function setTreeLabelCallback($treeLabelCallback) {
		$this->treeLabelCallback = $treeLabelCallback;
		return $this;
	}

	public function getTreeIconCallback() {
		return $this->treeIconCallback;
	}

	public function setTreeIconCallback($treeIconCallback) {
		$this->treeIconCallback = $treeIconCallback;
		return $this;
	}

	public function hasItem() {
		return strlen($this->getItemTable()) != 0;
	}

	public function getItemTable() {
		return $this->itemTable;
	}

	public function setItemTable($itemTable) {
		$this->itemTable = $itemTable;
		return $this;
	}

	public function getItemKeyColumn() {
		return $this->itemKeyColumn;
	}

	public function setItemKeyColumn($itemKeyColumn) {
		$this->itemKeyColumn = $itemKeyColumn;
		return $this;
	}

	public function getItemTreeKeyColumn() {
		return $this->itemTreeKeyColumn;
	}

	public function setItemTreeKeyColumn($itemTreeKeyColumn) {
		$this->itemTreeKeyColumn = $itemTreeKeyColumn;
		return $this;
	}

	public function getItemSearchColumns() {
		return (array) $this->itemSearchColumns;
	}

	public function setItemSearchColumns($itemSearchColumns) {
		$this->itemSearchColumns = self::cleanColumns($itemSearchColumns);
		return $this;
	}

	public function addItemSearchColumns($itemSearchColumns) {
		return $this->setItemSearchColumns(array_merge($this->getItemSearchColumns(), (array) $itemSearchColumns));
	}

	public function getItemColumns() {
		return (array) $this->itemColumns;
	}

	public function setItemColumns($itemColumns) {
		$this->itemColumns = self::cleanColumns($itemColumns);
		return $this;
	}

	public function addItemColumns($itemColumns) {
		return $this->setItemColumns(array_merge($this->getItemColumns(), (array) $itemColumns));
	}

	public function getItemConditionExpr() {
		return $this->itemConditionExpr;
	}

	public function setItemConditionExpr($itemConditionExpr) {
		$this->itemConditionExpr = $itemConditionExpr;
		return $this;
	}

	public function getItemOrderByExpr($clause = null) {
		$expr = $this->itemOrderByExpr;
		$clause = strval($clause);
		strlen($expr) && strlen($clause) && $expr = $clause . ' ' . $expr;
		return $expr;
	}

	public function setItemOrderByExpr($itemOrderByExpr) {
		$this->itemOrderByExpr = strval($itemOrderByExpr);
		return $this;
	}

	public function getItemLabelCallback() {
		return $this->itemLabelCallback;
	}

	public function setItemLabelCallback($itemLabelCallback) {
		$this->itemLabelCallback = $itemLabelCallback;
		return $this;
	}

	public function getItemIconCallback() {
		return $this->itemIconCallback;
	}

	public function setItemIconCallback($itemIconCallback) {
		$this->itemIconCallback = $itemIconCallback;
		return $this;
	}

	public static function cleanColumns($columns) {
		return array_unique(array_values(array_filter(array_map('strval', (array) $columns))));
	}

}
