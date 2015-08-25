<?php

namespace Hofff\Contao\Selectri\Model\Flat;

use Hofff\Contao\Selectri\Util\SearchUtil;
use Hofff\Contao\Selectri\Util\SQLUtil;
use Hofff\Contao\Selectri\Model\AbstractData;
use Hofff\Contao\Selectri\Widget;
use Hofff\Contao\Selectri\Exception\SelectriException;

class SQLListData extends AbstractData {

	/**
	 * @var \Database
	 */
	protected $db;

	/**
	 * @var SQLListDataConfig
	 */
	protected $cfg;

	/**
	 * @param Widget $widget
	 * @param \Database $db
	 * @param SQLListDataConfig $cfg
	 */
	public function __construct(Widget $widget, \Database $db, SQLListDataConfig $cfg) {
		parent::__construct($widget);
		$this->db = $db;
		$this->cfg = $cfg;
	}

	/**
	 * @return SQLListDataConfig
	 */
	public function getConfig() {
		return $this->cfg;
	}

	/**
	 * @see \Hofff\Contao\Selectri\Model\Data::validate()
	 */
	public function validate() {
		if(!strlen($this->cfg->getTable())) {
			throw new SelectriException('invalid config: no table given');
		}

		try {
			$sql = $this->buildNodeQuery();
			$sql = sprintf($sql, '1');
			$this->db->prepare($sql)->limit(1)->execute();

		} catch(Exception $e) {
			throw new SelectriException('invalid table configuration: ' . $e->getMessage());
		}
	}

	/**
	 * @see \Hofff\Contao\Selectri\Model\Data::getNodes()
	 */
	public function getNodes(array $keys, $selectableOnly = true) {
		if(!$keys) {
			return new \EmptyIterator;
		}

		$sql = $this->buildNodeQuery();
		$condition = sprintf(
			'%s IN (%s) %s',
			$this->cfg->getKeyColumn(),
			SQLUtil::generateWildcards($keys),
			$this->cfg->getSelectableExpr('AND')
		);
		$sql = sprintf($sql, $condition);
		$result = $this->db->prepare($sql)->execute(array_values($keys));

		$nodes = array();
		while($result->next()) {
			$nodes[] = $this->createNode($result->row());
		}

		// maintain key order
		$nodes = array_replace(array_intersect_key(array_flip($keys), $nodes), $nodes);

		return new \ArrayIterator($nodes);
	}

	/**
	 * @see \Hofff\Contao\Selectri\Model\Data::filter()
	 */
	public function filter(array $keys) {
		return array_keys(iterator_to_array($this->getNodes($keys), true));
	}

	/**
	 * @see \Hofff\Contao\Selectri\Model\AbstractData::isBrowsable()
	 */
	public function isBrowsable() {
		return true;
	}

	/**
	 * @see \Hofff\Contao\Selectri\Model\AbstractData::browseFrom()
	 */
	public function browseFrom($key = null) {
		$sql = $this->buildNodeQuery();
		$sql = sprintf($sql, 1);
		$result = $this->db->prepare($sql)->execute();

		$nodes = array();
		while($result->next()) {
			$nodes[] = $this->createNode($result->row());
		}

		return array(new \ArrayIterator($nodes), 0);
	}

	/**
	 * @see \Hofff\Contao\Selectri\Model\AbstractData::browseTo()
	 */
	public function browseTo($key) {
		return new \EmptyIterator;
	}

	/**
	 * @see \Hofff\Contao\Selectri\Model\AbstractData::isSearchable()
	 */
	public function isSearchable() {
		return true;
	}

	/**
	 * @see \Hofff\Contao\Selectri\Model\AbstractData::search()
	 */
	public function search($query, $limit, $offset = 0) {
		$keywords = SearchUtil::parseKeywords($query);
		if(!$keywords) {
			return new \EmptyIterator;
		}

		$sql = $this->buildNodeQuery();
		$columnCnt = null;
		$expr = $this->buildSearchExpr(count($keywords), $columnCnt);
		$sql = sprintf($sql, $expr);

		$params = array();
		foreach($keywords as $word) {
			$params[] = array_fill(0, $columnCnt, $word);
		}
		$params = call_user_func_array('array_merge', $params);
		$keys = $this->db->prepare($sql)->limit($limit, $offset)->execute($params)->fetchEach('_key');

		return $this->getNodes($keys);
	}

	/**
	 * @param array $node
	 * @return SQLListNode
	 */
	protected function createNode(array $node) {
		return new SQLListNode($this, $node);
	}

	/**
	 * @return string
	 */
	protected function buildSelectExpr() {
		$columns = $this->cfg->getColumns();
		$columns[] = $this->cfg->getKeyColumn();
		return implode(', ', array_unique($columns));
	}

	/**
	 * @return string
	 */
	protected function buildSelectableExpr() {
		return $this->cfg->getSelectableExpr() ? $this->cfg->getSelectableExpr() : '1';
	}

	/**
	 * @return string
	 */
	protected function buildNodeQuery() {
		$query = <<<EOT
SELECT		%s AS _key,
			(%s) AS _isSelectable,
			%s

FROM		%s

WHERE		(%%s)
%s
%s
EOT;

		$params = array();
		// select
		$params[] = $this->cfg->getKeyColumn();
		$params[] = $this->buildSelectableExpr();
		$params[] = $this->buildSelectExpr();
		// from
		$params[] = $this->cfg->getTable();
		// where
		$params[] = $this->cfg->getConditionExpr('AND');
		// order
		$params[] = $this->cfg->getOrderByExpr('ORDER BY');

		$query = vsprintf($query, $params);

		return $query;
	}

	/**
	 * @param integer $keywordCnt
	 * @param integer $columnCnt
	 * @return string
	 */
	protected function buildSearchExpr($keywordCnt, &$columnCnt) {
		$columns = $this->cfg->getItemSearchColumns();
		$keyColumn = $this->cfg->getItemKeyColumn();
		in_array($keyColumn, $columns) || $columns[] = $keyColumn;

		$condition = array();
		foreach($columns as $column) {
			$condition[] = $column . ' LIKE CONCAT(\'%\', CONCAT(?, \'%\'))';
		}
		$condition = implode(' OR ', $condition);

		$columnCnt = count($columns);
		return '(' . implode(') AND (', array_fill(0, $keywordCnt, $condition)) . ')';
	}

}
