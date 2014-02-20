<?php

class SelectriTableItemData implements SelectriData {

	protected $db;

	protected $widget;

	protected $cfg;

	protected $nodeQuery;

	public function __construct(Database $db, SelectriWidget $widget, SelectriTableDataConfig $cfg) {
		$this->db = $db;
		$this->widget = $widget;
		$this->cfg = clone $cfg;
	}

	public function validate() {
		if($this->cfg->hasTree()) {
			throw new Exception('invalid table data config: table is a tree');
		}
		try {
			$this->fetchNodes(array(), true, true, 1);
		} catch(Exception $e) {
			throw new Exception('invalid table configuration: ' . $e->getMessage());
		}
	}

	public function filter(array $selection) {
		$nodes = array();
		foreach($this->fetchNodes($selection) as $key => $node) if($node['_isSelectable']) {
			$nodes[] = $key;
		}
		return $nodes ? array_intersect($selection, $nodes) : array();
	}

	public function getSelectionIterator(array $selection) {
		if(!$selection) {
			return new EmptyIterator();
		}
		$selection = $this->filter($selection);
		$selection = $this->fetchNodes($selection);
		$nodes = array();
		foreach($selection as $node) {
			$nodes[] = new SelectriTableItemDataNode($this, $node);
		}
		return new ArrayIterator($nodes);
	}

	public function getTreeIterator($start = null) {
		$selection = $this->fetchNodes();
		$nodes = array();
		foreach($selection as $node) {
			$nodes[] = new SelectriTableItemDataNode($this, $node);
		}
		return array(new ArrayIterator($nodes), 0);
	}

	public function getPathIterator($key) {
		return new EmptyIterator();
	}

	public function getSearchIterator($search) {
		$keywords = $this->parseKeywords($search);
		if(!$keywords) {
			return new EmptyIterator();
		}

		$query = $this->buildSearchQuery();
		$expr = $this->getItemSearchExpr(count($keywords), $columnCnt);
		$query = sprintf($query, $expr);

		foreach($keywords as $word) {
			$params[] = array_fill(0, $columnCnt, $word);
		}
		$params = call_user_func_array('array_merge', $params);
		$found = $this->db->prepare($query)->limit($this->getWidget()->getSearchLimit())->execute($params)->fetchEach('_key');
		return $this->getSelectionIterator($found);
	}

	public function getConfig() {
		return $this->cfg;
	}

	public function getWidget() {
		return $this->widget;
	}

	public function generateLabel(array $node) {
		return call_user_func($this->cfg->getItemLabelCallback(), $node, $this, $this->cfg);
	}

	public function generateIcon(array $node) {
		return call_user_func($this->cfg->getItemIconCallback(), $node, $this, $this->cfg);
	}

	protected function buildItemSelectExpr() {
		$columns = $this->cfg->getItemColumns();
		$columns[] = $this->cfg->getItemKeyColumn();
		return implode(', ', array_unique($columns));
	}

	protected function buildSelectableExpr() {
		return $this->cfg->getSelectableExpr() ? $this->cfg->getSelectableExpr() : '1';
	}

	protected function buildSelectionQuery() {
		if($this->nodeQuery) {
			return $this->nodeQuery;
		}

		$query = <<<EOT
SELECT		%s AS _key,
			(%s) AS _isSelectable,
			%s

FROM		%s

WHERE		%%s IN (%%s)
%s
EOT;
		// select
		$params[] = $this->cfg->getItemKeyColumn();
		$params[] = $this->buildSelectableExpr();
		$params[] = $this->buildItemSelectExpr();
		// from
		$params[] = $this->cfg->getItemTable();
		// where
		$params[] = $this->cfg->getItemConditionExpr('AND');

		$query = vsprintf($query, $params);

		return $this->nodeQuery = $query;
	}

	protected function buildSearchQuery() {
		$query = <<<EOT
SELECT		%s AS _key,
			(%s) AS _isSelectable,
			%s
FROM		%s

WHERE		(%%s)
%s
%s
EOT;

		// select
		$params[] = $this->cfg->getItemKeyColumn();
		$params[] = $this->buildSelectableExpr();
		$params[] = $this->buildItemSelectExpr();
		// from
		$params[] = $this->cfg->getItemTable();
		// where
		$params[] = $this->cfg->getItemConditionExpr('AND');
		$params[] = $this->cfg->getSelectableExpr('AND');

		$query = vsprintf($query, $params);

		return $query;
	}

	protected function getItemSearchExpr($keywordCnt, &$columnCnt) {
		$columns = $this->cfg->getItemSearchColumns();
		$keyColumn = $this->cfg->getItemKeyColumn();
		in_array($keyColumn, $columns) || $columns[] = $keyColumn;

		foreach($columns as $column) {
			$condition[] = $column . ' LIKE CONCAT(\'%\', CONCAT(?, \'%\'))';
		}
		$condition = implode(' OR ', $condition);

		$columnCnt = count($columns);
		return '(' . implode(') AND (', array_fill(0, $keywordCnt, $condition)) . ')';
	}

	protected function fetchNodes($selection = array(), $order = false, $limit = PHP_INT_MAX) {
		if ($selection) {
			$refColumn = $this->cfg->getItemKeyColumn();
			$query = sprintf($this->buildSelectionQuery(), $refColumn, self::generateWildcards($selection));
			$order && $query .= PHP_EOL . $this->cfg->getItemOrderByExpr('ORDER BY');

			$result = $this->db->prepare($query)->limit($limit)->execute($selection);
		}
		else {
			$query = sprintf($this->buildSearchQuery(), 1);
			$order && $query .= PHP_EOL . $this->cfg->getItemOrderByExpr('ORDER BY');

			$result = $this->db->prepare($query)->limit($limit)->execute();
		}

		$nodes = array();
		while($result->next()) {
			$nodes[strval($result->_key)] = $result->row();
		}
		return $nodes;
	}

	protected function parseKeywords($search) {
		if(defined('PREG_BAD_UTF8_OFFSET')) {
			return preg_split('/[^\pL\pN]+/iu', $search, null, PREG_SPLIT_NO_EMPTY);
// 			return preg_split('/[^\pL\pN]+(?:[\pL\pN][^\pL\pN]+)?/iu', $search, null, PREG_SPLIT_NO_EMPTY);
		} else {
			return preg_split('/[^\w]+/i', $search, null, PREG_SPLIT_NO_EMPTY);
// 			return preg_split('/(?:^|[^\w]+)(?:[\w](?:$|[^\w]+))*/i', $search, null, PREG_SPLIT_NO_EMPTY);
		}
	}

	public static function generateWildcards(array $args) {
		return rtrim(str_repeat('?,', count($args)), ',');
	}

}
