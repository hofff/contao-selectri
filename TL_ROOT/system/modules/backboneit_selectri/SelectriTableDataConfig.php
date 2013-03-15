<?php

class SelectriTableDataConfig {
	
	private $roots;
	
	private $treeTable;
	private $treeKeyColumn;
	private $treeParentKeyColumn;
	private $treeLabelColumns;
	private $treeSearchColumns;
	private $treeAdditionalColumns;
	private $treeRootValue;
	private $treeConditionExpr;
	private $treeOrderByExpr;
	private $treeLabelFormat;
	private $treeLabelFormatFunction;
	private $treeIcon;
	private $treeIconFunction;
	
	private $itemTable;
	private $itemKeyColumn;
	private $itemTreeKeyColumn;
	private $itemLabelColumns;
	private $itemSearchColumns;
	private $itemAdditionalColumns;
	private $itemConditionExpr;
	private $itemOrderByExpr;
	private $itemLabelFormat;
	private $itemLabelFormatFunction;
	private $itemIcon;
	private $itemIconFunction;
	
	public function __construct() {
	}

	public function getRoots() {
		return $this->roots ? $this->roots : array($this->getTreeRootValue());
	}

	public function setRoots($roots) {
		$this->roots = array_values((array) $roots);
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
	
	public function getTreeLabelColumns() {
		return (array) $this->treeLabelColumns;
	}
	
	public function setTreeLabelColumns($treeLabelColumns) {
		$this->treeLabelColumns = self::cleanColumns($treeLabelColumns);
		return $this;
	}
	
	public function getTreeSearchColumns() {
		return (array) $this->treeSearchColumns;
	}
	
	public function setTreeSearchColumns($treeSearchColumns) {
		$this->treeSearchColumns = self::cleanColumns($treeSearchColumns);
		return $this;
	}
	
	public function getTreeAdditionalColumns() {
		return (array) $this->treeAdditionalColumns;
	}
	
	public function setTreeAdditionalColumns($treeAdditionalColumns) {
		$this->treeAdditionalColumns = self::cleanColumns($treeAdditionalColumns);
		return $this;
	}
	
	public function getTreeRootValue() {
		return $this->treeRootValue;
	}
	
	public function setTreeRootValue($treeRootValue) {
		$this->treeRootValue = $treeRootValue;
		return $this;
	}
	
	public function getTreeConditionExpr() {
		return $this->treeConditionExpr;
	}
	
	public function setTreeConditionExpr($treeConditionExpr) {
		$this->treeConditionExpr = $treeConditionExpr;
		return $this;
	}
	
	public function getTreeOrderByExpr() {
		return $this->treeOrderByExpr;
	}
	
	public function setTreeOrderByExpr($treeOrderByExpr) {
		$this->treeOrderByExpr = strval($treeOrderByExpr);
		return $this;
	}
	
	public function getTreeLabelFormat() {
		return $this->treeLabelFormat;
	}
	
	public function setTreeLabelFormat($treeLabelFormat) {
		if(is_callable($treeLabelFormat)) {
			$this->treeLabelFormat = $treeLabelFormat;
			$this->treeLabelFormatFunction = $treeLabelFormat;
		} else {
			$this->treeLabelFormat = strval($treeLabelFormat);
			$this->treeLabelFormatFunction = array($this, 'formatTreeLabel');
		}
		return $this;
	}
	
	public function resolveTreeLabelFormat(array $node, SelectriTableData $data) {
		return call_user_func($this->treeLabelFormatFunction, $node, $data, $this);
	}
	
	public function formatTreeLabel(array $node, SelectriTableData $data, SelectriTableDataConfig $cfg) {
		return vsprintf($this->treeLabelFormat, $node['label']);
	}
	
	public function getTreeIcon() {
		return $this->treeIcon;
	}
	
	public function setTreeIcon($treeIcon) {
		if(is_callable($treeIcon)) {
			$this->treeIcon = $treeIcon;
			$this->treeIconFunction = $treeIcon;
		} else {
			$this->treeIcon = strval($treeIcon);
			$this->treeIconFunction = array($this, 'getTreeIcon');
		}
		return $this;
	}
	
	public function resolveTreeIcon(array $node, SelectriTableData $data) {
		return call_user_func($this->treeIconFunction, $node, $data, $this);
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
	
	public function getItemLabelColumns() {
		return (array) $this->itemLabelColumns;
	}
	
	public function setItemLabelColumns($itemLabelColumns) {
		$this->itemLabelColumns = self::cleanColumns($itemLabelColumns);
		return $this;
	}
	
	public function getItemSearchColumns() {
		return (array) $this->itemSearchColumns;
	}
	
	public function setItemSearchColumns($itemSearchColumns) {
		$this->itemSearchColumns = self::cleanColumns($itemSearchColumns);
		return $this;
	}
	
	public function getItemAdditionalColumns() {
		return (array) $this->itemAdditionalColumns;
	}
	
	public function setItemAdditionalColumns($itemAdditionalColumns) {
		$this->itemAdditionalColumns = self::cleanColumns($itemAdditionalColumns);
		return $this;
	}
	
	public function getItemConditionExpr() {
		return $this->itemConditionExpr;
	}
	
	public function setItemConditionExpr($itemConditionExpr) {
		$this->itemConditionExpr = $itemConditionExpr;
		return $this;
	}
	
	public function getItemOrderByExpr() {
		return $this->itemOrderByExpr;
	}
	
	public function setItemOrderByExpr($itemOrderByExpr) {
		$this->itemOrderByExpr = strval($itemOrderByExpr);
		return $this;
	}
	
	public function getItemLabelFormat() {
		return $this->treeLabelFormat;
	}
	
	public function setItemLabelFormat($itemLabelFormat) {
		if(is_callable($itemLabelFormat)) {
			$this->itemLabelFormat = $itemLabelFormat;
			$this->itemLabelFormatFunction = $itemLabelFormat;
		} else {
			$this->itemLabelFormat = strval($itemLabelFormat);
			$this->itemLabelFormatFunction = array($this, 'formatItemLabel');
		}
		return $this;
	}
	
	public function resolveItemLabelFormat(array $node, SelectriTableData $data) {
		return call_user_func($this->itemLabelFormatFunction, $node, $data, $this);
	}
	
	public function formatItemLabel(array $node, SelectriTableData $data, SelectriTableDataConfig $cfg) {
		return vsprintf($this->itemLabelFormat, $node['label']);
	}
	
	public function getItemIcon() {
		return $this->itemIcon;
	}
	
	public function setItemIcon($itemIcon) {
		if(is_callable(itemIcon)) {
			$this->itemIcon = $itemIcon;
			$this->itemIconFunction = $itemIcon;
		} else {
			$this->itemIcon = strval($itemIcon);
			$this->itemIconFunction = array($this, 'getItemIcon');
		}
		return $this;
	}
	
	public function resolveItemIcon(array $node, SelectriTableData $data) {
		return call_user_func($this->itemIconFunction, $node, $data, $this);
	}
	
	public static function cleanColumns($columns) {
		return array_unique(array_values(array_filter(array_map('strval', (array) $columns))));
	}
	
}
