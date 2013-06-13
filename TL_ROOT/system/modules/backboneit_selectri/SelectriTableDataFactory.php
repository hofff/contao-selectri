<?php

class SelectriTableDataFactory extends SelectriAbstractDataFactory {

	const DEFAULT_ICON = 'iconPLAIN.gif';

	private $db;

	private $cfg;

	public function __construct() {
		parent::__construct();
		$this->db = Database::getInstance();
		$this->cfg = $cfg = new SelectriTableDataConfig();
		$cfg->setTreeMode('all');
	}

	public function __clone() {
		parent::__clone();
		$this->cfg = clone $this->cfg;
	}

	public function getDatabase() {
		return $this->db;
	}

	public function getConfig() {
		return $this->cfg;
	}

	protected function prepareConfig(SelectriTableDataConfig $cfg) {
		$cfg->hasTree() && $this->prepareTreeConfig($cfg);
		$cfg->hasItem() && $this->prepareItemConfig($cfg);
	}

	protected function prepareTreeConfig(SelectriTableDataConfig $cfg) {
		$callback = $cfg->getTreeLabelCallback();
		if(!$callback) {
			$cfg->setTreeLabelCallback(array(__CLASS__, 'defaultTreeLabelCallback'));
		} elseif(is_array($callback) && is_object($callback[0]) && $callback[0] instanceof SelectriLabelFormatter) {
			$fields = $callback[0]->getFields();
			$cfg->addTreeColumns($fields);
			if(!strlen($cfg->getTreeOrderByExpr())) {
				$cfg->setTreeOrderByExpr($fields[0]);
			}
		}
		if(!$cfg->getTreeIconCallback()) {
			$cfg->setTreeIconCallback(array(__CLASS__, 'defaultIconCallback'));
		}
	}

	protected function prepareItemConfig(SelectriTableDataConfig $cfg) {
		$callback = $cfg->getItemLabelCallback();
		if(!$callback) {
			$cfg->setItemLabelCallback(array(__CLASS__, 'defaultItemLabelCallback'));
		} elseif(is_array($callback) && is_object($callback[0]) && $callback[0] instanceof SelectriLabelFormatter) {
			$fields = $callback[0]->getFields();
			$cfg->addItemColumns($fields);
			if(!strlen($cfg->getItemOrderByExpr())) {
				$cfg->setItemOrderByExpr($fields[0]);
			}
		}
		if(!$cfg->getItemIconCallback()) {
			$cfg->setItemIconCallback(array(__CLASS__, 'defaultIconCallback'));
		}
	}

	public function setParameters($params) {
		parent::setParameters($params);
		$params = (array) $params;
		isset($params['mode']) && $this->getConfig()->setTreeMode($params['mode']);
		return $this;
	}

	public function createData() {
		$cfg = clone $this->getConfig();
		$this->prepareConfig($cfg);
		if($cfg->isTreeOnlyMode()) {
			return new SelectriTableTreeData($this->getDatabase(), $this->getWidget(), $cfg);
		} elseif($cfg->isItemOnlyMode()) {
			throw new Exception('item mode not implemented');
		} else {
			throw new Exception('tree and item mode not implemented');
		}
	}

	public static function defaultTreeLabelCallback(array $node, SelectriData $data, SelectriTableDataConfig $cfg) {
		return $node[$cfg->getTreeKeyColumn()];
	}

	public static function defaultItemLabelCallback(array $node, SelectriData $data, SelectriTableDataConfig $cfg) {
		return $node[$cfg->getItemKeyColumn()];
	}

	public static function defaultIconCallback(array $node, SelectriData $data) {
		return self::getIconPath($data->getWidget());
	}

	public static function getIconPath(SelectriWidget $widget, $icon = null) {
		strlen($icon) || $icon = self::DEFAULT_ICON;
		return strpos($icon, '/') === false
			? 'system/themes/' . $widget->_getTheme() . '/images/' . $icon
			: $icon;
	}

}
