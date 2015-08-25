<?php

namespace Hofff\Contao\Selectri\Model\Tree;

use Hofff\Contao\Selectri\Model\DataFactory;
use Hofff\Contao\Selectri\Util\Icons;
use Hofff\Contao\Selectri\Widget;
use Hofff\Contao\Selectri\Util\LabelFormatter;
use Hofff\Contao\Selectri\Exception\SelectriException;
use Hofff\Contao\Selectri\Util\SQLUtil;
use Hofff\Contao\Selectri\Model\Node;
use Hofff\Contao\Selectri\Model\Data;

class SQLAdjacencyTreeDataFactory implements DataFactory {

	/**
	 * @var \Database
	 */
	private $db;

	/**
	 * @var SQLAdjacencyTreeDataConfig
	 */
	private $cfg;

	/**
	 */
	public function __construct() {
		$this->db = \Database::getInstance();
		$this->cfg = new SQLAdjacencyTreeDataConfig;

		$this->cfg->setKeyColumn('id');
		$this->cfg->setParentKeyColumn('pid');
		$this->cfg->setRootValue(0);
		$this->cfg->setSelectionMode('all');
	}

	/**
	 * @return void
	 */
	public function __clone() {
		$this->cfg = clone $this->cfg;
	}

	/**
	 * @see \Hofff\Contao\Selectri\Model\DataFactory::setParameters()
	 */
	public function setParameters($params) {
		$params = (array) $params;
		isset($params['treeTable']) && $this->getConfig()->setTable($params['treeTable']);
		isset($params['mode']) && $this->getConfig()->setSelectionMode($params['mode']);
	}

	/**
	 * @see \Hofff\Contao\Selectri\Model\DataFactory::createData()
	 */
	public function createData(Widget $widget = null) {
		if(!$widget) {
			throw new SelectriException('Selectri widget is required to create a SQLAdjacencyTreeData');
		}

		$cfg = clone $this->getConfig();
		$this->prepareConfig($cfg);
		return new SQLAdjacencyTreeData($widget, $this->getDatabase(), $cfg);
	}

	/**
	 * @return \Database
	 */
	public function getDatabase() {
		return $this->db;
	}

	/**
	 * @return SQLAdjacencyTreeDataConfig
	 */
	public function getConfig() {
		return $this->cfg;
	}

	/**
	 * @param SQLAdjacencyTreeDataConfig $cfg
	 * @return void
	 */
	protected function prepareConfig(SQLAdjacencyTreeDataConfig $cfg) {
		$db = $this->getDatabase();

		if(!$cfg->setOrderByExpr() && $db->fieldExists('sorting', $cfg->getTable())) {
			$cfg->setOrderByExpr('sorting');
		}

		if(!$cfg->getLabelCallback()) {
			$formatter = SQLUtil::createLabelFormatter($db, $cfg->getTable(), $cfg->getKeyColumn());
			$cfg->setLabelCallback($formatter->getCallback());
		}

		$callback = $cfg->getLabelCallback();
		if(is_array($callback) && is_object($callback[0]) && $callback[0] instanceof LabelFormatter) {
			$fields = $callback[0]->getFields();
			$cfg->addColumns($fields);

			if(!strlen($cfg->setOrderByExpr())) {
				$cfg->setOrderByExpr($fields[0]);
			}
		}

		if(!$cfg->getIconCallback()) {
			list($callback, $columns) = Icons::getTableIconCallback($cfg->getTable());
			if($callback) {
				$cfg->setIconCallback($callback);
				$cfg->addColumns($columns);
			} else {
				$cfg->setIconCallback(function(Node $node, Data $data, SQLAdjacencyTreeDataConfig $cfg) {
					return Icons::getIconPath(Icons::getTableIcon($cfg->getTable()));
				});
			}
		}
	}

}
