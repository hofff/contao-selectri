<?php

namespace Hofff\Contao\Selectri\Model\Tree;

use Contao\Database;
use Hofff\Contao\Selectri\Exception\SelectriException;
use Hofff\Contao\Selectri\Model\AbstractData;
use Hofff\Contao\Selectri\Util\SearchUtil;
use Hofff\Contao\Selectri\Util\SQLUtil;
use Hofff\Contao\Selectri\Widget;

class SQLAdjacencyTreeData extends AbstractData {

	/**
	 * @var Database
	 */
	protected $db;

	/**
	 * @var SQLAdjacencyTreeDataConfig
	 */
	protected $cfg;

	/**
	 * @var string
	 */
	private $nodeQuery;

	/**
	 * @param Widget $widget
	 * @param Database $db
	 * @param SQLAdjacencyTreeDataConfig $cfg
	 */
	public function __construct(Widget $widget, Database $db, SQLAdjacencyTreeDataConfig $cfg) {
		parent::__construct($widget);
		$this->db = $db;
		$this->cfg = $cfg;
	}

	/**
	 * @return SQLAdjacencyTreeDataConfig
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
			$this->fetchTreeNodes(array($this->cfg->getRootValue()), true, true, 1);
		} catch(\Exception $e) {
			throw new SelectriException('invalid tree table configuration: ' . $e->getMessage(), $e->getCode(), $e);
		}
	}

	/**
	 * @see \Hofff\Contao\Selectri\Model\Data::getNodes()
	 */
	public function getNodes(array $keys, $selectableOnly = true) {
		$children = null;
		$keys = $this->filterKeys($keys, $children, $selectableOnly);
		if(!$keys) {
			return new \EmptyIterator;
		}

		$tree = new Tree($this->cfg->getRootValue());
		$tree->children = $children;
		$tree->parents = $tree->getParentsFromChildren();
		$tree->nodes = $this->fetchTreeNodes(array_keys($tree->parents));

		$nodes = array();
		foreach($keys as $key) {
			$nodes[] = $this->createNode($tree, $key);
		}

		return new \ArrayIterator($nodes);
	}

	/**
	 * @see \Hofff\Contao\Selectri\Model\Data::filter()
	 */
	public function filter(array $keys, $selectableOnly = true) {
		$children = null;

		return $this->filterKeys($keys, $children, $selectableOnly);
	}

	/**
	 * @see \Hofff\Contao\Selectri\Model\Data::isBrowsable()
	 */
	public function isBrowsable() {
		return true;
	}

	/**
	 * @see \Hofff\Contao\Selectri\Model\Data::browseFrom()
	 */
	public function browseFrom($key = null) {
		$roots = $this->cfg->getRoots();
		if(!$roots) {
			throw new SelectriException('No roots configured');
		}

		$startKey = $key;
		$tree = new Tree($this->cfg->getRootValue());

		$rootValue = $tree->getRootValue();
		$endpoints = $roots;

		// clean start
		if($startKey === null) {
			$rootStart = true;
		} else {
			$startKey = strval($startKey);
			$startKey === $rootValue ? $rootStart = true : $endpoints[] = $startKey;
		}

		$tree->children = $this->fetchAncestorOrSelfTree($endpoints);

		// prepare roots
		if($rootStart) {
			$roots = $tree->getPreorder($roots, true);
			if(!$roots) {
				return [ new \EmptyIterator, null ];
			}

			if($roots[0] === $rootValue) { // implies count($roots) == 1 because unnested
				$tree->nodes = $this->fetchTreeNodes(array($rootValue), true, true);
			} else {
				$tree->nodes = $this->fetchTreeNodes($roots);
			}

		} else {
			// filter start
			if(!in_array($startKey, $tree->getDescendantsPreorder($roots, true))) {
				return [ new \EmptyIterator, null ];
			}
			$this->fetchLevels($tree, array($startKey));
		}

		$first = array_keys((array) $tree->nodes);
		if(!$first) {
			return [ new \EmptyIterator, null ];
		}

		$tree->parents = $tree->getParentsFromChildren();
		$this->addUnfolded($tree, $first);

		$first = $this->createFirstLevelNodes($tree, $first);
		return array(new \ArrayIterator($first), $rootStart ? null : $startKey);
	}

	/**
	 * @see \Hofff\Contao\Selectri\Model\Data::browseTo()
	 */
	public function browseTo($key) {
		$roots = $this->cfg->getRoots();
		if(!$roots) {
			throw new SelectriException('No roots configured');
		}

		$toKey = $key;

		$endpoints = $roots;
		$endpoints[] = $toKey;

		$tree = new Tree($this->cfg->getRootValue());
		$tree->children = $this->fetchAncestorOrSelfTree($endpoints);
		$tree->parents = $tree->getParentsFromChildren();

		// prepare roots
		$roots = $tree->getPreorder($roots, true);
		$rootValue = $tree->getRootValue();
		if($roots[0] === $rootValue) { // implies count($roots) == 1 because unnested
			$tree->nodes = $this->fetchTreeNodes(array($rootValue), true, true);
		} else {
			$tree->nodes = $this->fetchTreeNodes($roots);
		}

		$first = array_keys($tree->nodes);

		// fetch levels along the path
		if(!isset($tree->nodes[$toKey])) {
			$node = $this->createNode($tree, $toKey);
			$pathKeys = $node->getPathKeys();
			$rootInPath = false;
			foreach($pathKeys as $i => $key) {
				if(isset($tree->nodes[$key])) {
					$rootInPath = true;
					$pathKeys = array_slice($pathKeys, 0, $i + 1);
					break;
				}
			}
			if(!$rootInPath) {
				throw new SelectriException(sprintf('Node "%s" not reachable from configured roots', $toKey));
			}
			$this->fetchLevels($tree, $pathKeys);
		}

		$first = $this->createFirstLevelNodes($tree, $first);
		return new \ArrayIterator($first);
	}

	/**
	 * @see \Hofff\Contao\Selectri\Model\Data::isSearchable()
	 */
	public function isSearchable() {
		return true;
	}

	/**
	 * @see \Hofff\Contao\Selectri\Model\Data::search()
	 */
	public function search($query, $limit, $offset = 0) {
		$keywords = SearchUtil::parseKeywords($query);
		if(!$keywords) {
			return new \EmptyIterator();
		}

		$sql = $this->buildSearchQuery();
		$columnCnt = 0;
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
	 * @param Tree $tree
	 * @param string $key
	 * @return SQLAdjacencyTreeNode
	 */
	protected function createNode(Tree $tree, $key) {
		return new SQLAdjacencyTreeNode($this, $tree, $key);
	}

	/**
	 * @param array<string> $keys
	 * @param array<string, array<string, string>> $children
	 * @return array<string>
	 */
	protected function filterKeys(array $keys, array &$children = null, $selectableOnly = true) {
		$nodes = array();
		foreach($this->fetchTreeNodes($keys) as $key => $node) {
			if(!$selectableOnly || $node['_isSelectable']) {
				$nodes[] = $key;
			}
		}
		if(!$nodes) {
			return array();
		}
		$keys = array_intersect($keys, $nodes);

		$roots = $this->cfg->getRoots();
		$tree = new Tree($this->cfg->getRootValue());
		$tree->children = $this->fetchAncestorOrSelfTree(array_merge($roots, $keys));
		$descendants = $tree->getDescendantsPreorder($roots, true);
		$keys = array_intersect($keys, $descendants);

		$children = $tree->children;
		return $keys;
	}

	/**
	 * @return string
	 */
	protected function buildNodeSelectExpr() {
		$columns = $this->cfg->getColumns();
		$columns[] = $this->cfg->getKeyColumn();
		$columns[] = $this->cfg->getParentKeyColumn();
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
	protected function buildTreeNodeQuery() {
		if($this->nodeQuery) {
			return $this->nodeQuery;
		}

		$query = <<<EOT
SELECT
	%s AS _key,
	%s AS _parentKey,
	descendants._hasChildren AS _hasChildren,
	descendants._hasGrandChildren AS _hasGrandChildren,
	(%s) AS _isSelectable,
	%s
FROM
	%s
	AS tree
JOIN
	(
		SELECT
			%s AS _key,
			COUNT(child1._key) != 0 AS _hasChildren,
			COUNT(grandchild1._key) != 0 AS _hasGrandChildren
		FROM
			%s
			AS tree1
		LEFT JOIN
			(SELECT %s AS _key, %s AS _parentKey FROM %s %s)
			AS child1
			ON child1._parentKey = tree1.%s
		LEFT JOIN
			(SELECT %s AS _key, %s AS _parentKey FROM %s %s)
			AS grandchild1
			ON grandchild1._parentKey = child1._key
		WHERE
			%%s IN (%%s)
			%s
		GROUP BY
			%s
		%s
	)
	AS descendants
	ON descendants._key = tree.%s
EOT;

		$params = array();
		// select
		$params[] = $this->cfg->getKeyColumn();
		$params[] = $this->cfg->getParentKeyColumn();
		$params[] = $this->buildSelectableExpr();
		$params[] = $this->buildNodeSelectExpr();
		// from
		$params[] = $this->cfg->getTable();

		// select
		$params[] = $this->cfg->getKeyColumn();
		// from
		$params[] = $this->cfg->getTable();
		// child join
		$params[] = $this->cfg->getKeyColumn();
		$params[] = $this->cfg->getParentKeyColumn();
		$params[] = $this->cfg->getTable();
		$params[] = $this->cfg->getConditionExpr('WHERE');
		$params[] = $this->cfg->getKeyColumn();
		// grandchild join
		$params[] = $this->cfg->getKeyColumn();
		$params[] = $this->cfg->getParentKeyColumn();
		$params[] = $this->cfg->getTable();
		$params[] = $this->cfg->getConditionExpr('WHERE');
		// where
		$params[] = $this->cfg->getConditionExpr('AND');
		// group by
		$params[] = $this->cfg->getKeyColumn();
		// having
		$params[] = $this->cfg->getSelectionMode() == SQLAdjacencyTreeDataConfig::SELECTION_MODE_INNER
			? 'HAVING _hasChildren'
			: '';

		// join
		$params[] = $this->cfg->getKeyColumn();

		$query = vsprintf($query, $params);

		return $this->nodeQuery = $query;
	}

	/**
	 * @param array $keys
	 * @param boolean $children
	 * @param boolean $order
	 * @param integer $limit
	 * @return array<string, array>
	 */
	protected function fetchTreeNodes(array $keys = null, $children = false, $order = false, $limit = PHP_INT_MAX) {
		if(!$keys) {
			return array();
		}

		$refColumn = $children ? $this->cfg->getParentKeyColumn() : $this->cfg->getKeyColumn();
		$query = sprintf($this->buildTreeNodeQuery(), $refColumn, SQLUtil::generateWildcards($keys));
		$order && $query .= PHP_EOL . $this->cfg->getOrderByExpr('ORDER BY');

		$result = $this->db->prepare($query)->limit($limit)->execute($keys);

		$nodes = array();
		while($result->next()) {
			$nodes[strval($result->_key)] = $result->row();
		}
		return $nodes;
	}

	/**
	 * @param Tree $tree
	 * @param array $parentKeys
	 * @return void
	 */
	protected function fetchLevels(Tree $tree, array $parentKeys) {
		if(!$parentKeys) {
			return;
		}

		$nodes = $this->fetchTreeNodes($parentKeys, true, true);

		// add fetched node data and remove existing children arrays for fetched nodes (to maintain order)
		foreach($nodes as $key => $node) {
			$tree->nodes[$key] = $node;
			unset($tree->children[strval($node['_parentKey'])]);
		}

		// insert nodes into tree
		foreach($nodes as $key => $node) {
			$parentKey = strval($node['_parentKey']);
			$tree->children[$parentKey][$key] = true;
			$tree->parents[$key] = $parentKey;
		}
	}

	/**
	 * @param Tree $tree
	 * @param array $roots
	 * @return void
	 */
	protected function addUnfolded(Tree $tree, array $roots) {
		$unfolded = $this->getWidget()->getUnfolded();
		if(!$unfolded) {
			return;
		}

		$nodes = $this->fetchTreeNodes($unfolded);
		$unfolded = array_keys($nodes);

		// clean out inexistant values to avoid longterm leaking...
		$this->getWidget()->setUnfolded($unfolded);

		if(!$nodes) {
			return;
		}

		$unfoldedTree = new Tree($this->cfg->getRootValue());
		foreach($nodes as $key => $node) {
			$unfoldedTree->children[strval($node['_parentKey'])][$key] = true;
		}

		$unfolded = $unfoldedTree->getDescendantsPreorder(array_intersect($roots, $unfolded), true);
		$this->fetchLevels($tree, $unfolded);
	}

	/**
	 * @param Tree $tree
	 * @param array<string> $keys
	 * @return array<SQLAdjacencyTreeNode>
	 */
	protected function createFirstLevelNodes(Tree $tree, array $keys) {
		$first = array();
		foreach($keys as $key) {
			$first[] = $this->createNode($tree, $key);
		}

		// fetch path nodes of first level
		$pathKeys = array();
		foreach($first as $node) {
			foreach($node->getPathKeys() as $key) {
				$pathKeys[$key] = $key;
			}
		}
		unset($pathKeys[$tree->getRootValue()]);

		foreach($this->fetchTreeNodes($pathKeys) as $key => $node) {
			$tree->nodes[$key] = $node;
		}

		return $first;
	}

	/**
	 * @return string
	 */
	protected function buildSearchQuery() {
		$query = <<<EOT
SELECT		%s AS _key
FROM		%s AS tree

LEFT JOIN	( SELECT %s AS _key, %s AS _parentKey FROM %s %s
			) AS child ON child._parentKey = tree.%s

WHERE		(%%s)
%s
%s
GROUP BY	%s
EOT;

		$params = array();
		// select
		$params[] = $this->cfg->getKeyColumn();
		// from
		$params[] = $this->cfg->getTable();
		// child join
		$params[] = $this->cfg->getKeyColumn();
		$params[] = $this->cfg->getParentKeyColumn();
		$params[] = $this->cfg->getTable();
		$params[] = $this->cfg->getConditionExpr('WHERE');
		$params[] = $this->cfg->getKeyColumn();
		// where
		$params[] = $this->cfg->getConditionExpr('AND');
		$params[] = $this->cfg->getSelectableExpr('AND');
		// group by
		$params[] = $this->cfg->getKeyColumn();

		$query = vsprintf($query, $params);

		// having
		if($this->cfg->getSelectionMode() == SQLAdjacencyTreeDataConfig::SELECTION_MODE_INNER) {
			$query .= PHP_EOL . 'HAVING COUNT(child._id) > 0';
		}

		return $query;
	}

	/**
	 * @param integer $keywordCnt
	 * @param integer $columnCnt
	 * @return string
	 */
	protected function buildSearchExpr($keywordCnt, &$columnCnt) {
		$columns = $this->cfg->getSearchColumns();
		$keyColumn = $this->cfg->getKeyColumn();
		in_array($keyColumn, $columns) || $columns[] = $keyColumn;

		$condition = array();
		foreach($columns as $column) {
			$condition[] = $column . ' LIKE CONCAT(\'%\', CONCAT(?, \'%\'))';
		}
		$condition = implode(' OR ', $condition);

		$columnCnt = count($columns);
		return '(' . implode(') AND (', array_fill(0, $keywordCnt, $condition)) . ')';
	}

	/**
	 * @param array $keys
	 * @return array<string, array<string, string>>
	 */
	protected function fetchAncestorOrSelfTree(array $keys) {
		if(!$keys) {
			return array();
		}

		$root = strval($this->cfg->getRootValue());
		$qids = array_map('strval', $keys);
		$qids = array_diff($qids, array($root));
		$keys = array_flip($qids);
		$keys[$root] = true;

		$query = sprintf('SELECT %s AS pid FROM %s WHERE %s IN (%%s)',
			$this->cfg->getParentKeyColumn(),
			$this->cfg->getTable(),
			$this->cfg->getKeyColumn()
		);
		while($qids) {
			$nodes = $this->db->prepare(sprintf($query, SQLUtil::generateWildcards($qids)))->execute($qids);
			$qids = array();
			while($nodes->next()) {
				$id = strval($nodes->pid);
				isset($keys[$id]) || $qids[] = $id;
				$keys[$id] = true;
			}
		}

		$query = sprintf(
			'SELECT	%s AS id, %s AS pid
			FROM	%s
			WHERE	%s IN (%s)
			%s
			%s',
			$this->cfg->getKeyColumn(),
			$this->cfg->getParentKeyColumn(),
			$this->cfg->getTable(),
			$this->cfg->getKeyColumn(),
			SQLUtil::generateWildcards($keys),
			$this->cfg->getConditionExpr('AND'),
			$this->cfg->getOrderByExpr('ORDER BY')
		);
		$nodes = $this->db->prepare($query)->execute(array_keys($keys));

		$children = array();
		$children[$root] = array();
		while($nodes->next()) {
			$id = strval($nodes->id);
			$children[strval($nodes->pid)][$id] = true;
			$children[$id] = (array) $children[$id];
		}

		return $children;
	}

}
