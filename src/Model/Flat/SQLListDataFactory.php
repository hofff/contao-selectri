<?php

namespace Hofff\Contao\Selectri\Model\Flat;

use Contao\Database;
use Hofff\Contao\Selectri\Exception\SelectriException;
use Hofff\Contao\Selectri\Model\Data;
use Hofff\Contao\Selectri\Model\DataFactory;
use Hofff\Contao\Selectri\Model\Node;
use Hofff\Contao\Selectri\Util\Icons;
use Hofff\Contao\Selectri\Util\LabelFormatter;
use Hofff\Contao\Selectri\Util\SQLUtil;
use Hofff\Contao\Selectri\Widget;

class SQLListDataFactory implements DataFactory {

	/**
	 * @var Database
	 */
	private $db;

	/**
	 * @var SQLListDataConfig
	 */
	private $cfg;

	/**
	 */
	public function __construct() {
		$this->db = Database::getInstance();
		$this->cfg = new SQLListDataConfig;
		$this->cfg->setKeyColumn('id');
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
		isset($params['itemTable']) && $this->getConfig()->setTable($params['itemTable']);
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
		return new SQLListData($widget, $this->getDatabase(), $cfg);
	}

	/**
	 * @return Database
	 */
	public function getDatabase() {
		return $this->db;
	}

	/**
	 * @return SQLListDataConfig
	 */
	public function getConfig() {
		return $this->cfg;
	}

	/**
	 * @param SQLListDataConfig $cfg
	 * @return void
	 */
	protected function prepareConfig(SQLListDataConfig $cfg) {
		$db = $this->getDatabase();

		if(!$cfg->getOrderByExpr() && $db->fieldExists('sorting', $cfg->getTable())) {
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

			if(!strlen($cfg->getOrderByExpr())) {
				$cfg->setOrderByExpr($fields[0]);
			}
		}

		if(!$cfg->getIconCallback()) {
			list($callback, $columns) = Icons::getTableIconCallback($cfg->getTable());
			if($callback) {
				$cfg->setIconCallback($callback);
				$cfg->addColumns($columns);
			} else {
				$cfg->setIconCallback(function(Node $node, Data $data, SQLListDataConfig $cfg) {
					return Icons::getIconPath(Icons::getTableIcon($cfg->getTable()));
				});
			}
		}
	}

}
