<?php

class SelectriTableTreeData implements SelectriData {

	protected $db;

	protected $widget;

	protected $cfg;

	protected $treeNodeQuery;

	public function __construct(Database $db, SelectriWidget $widget, SelectriTableDataConfig $cfg) {
		$this->db = $db;
		$this->widget = $widget;
		$this->cfg = clone $cfg;
	}

	public function validate() {
		if(!$this->cfg->hasTree()) {
			throw new Exception('invalid table data config: no tree table given');
		}
		try {
			$nodes = $this->fetchTreeNodes(array($this->cfg->getTreeRootValue()), true, true, 1);
		} catch(Exception $e) {
			throw new Exception('invalid tree table configuration: ' . $e->getMessage());
		}
		if(!$nodes) {
			throw new Exception('inconsistent data in tree table: no nodes parent key references root value');
		}
	}

	public function filter(array $selection) {
		return $this->_filter($selection);
	}

	protected function _filter(array $selection, array &$children = null) {
		foreach($this->fetchTreeNodes($selection) as $key => $node) if($node['_isSelectable']) {
			$nodes[] = $key;
		}
		if(!$nodes) {
			return array();
		}
		$selection = array_intersect($selection, $nodes);

		$roots = $this->cfg->getRoots();
		$children = $this->fetchAncestorOrSelfTree(array_merge($roots, $selection));
		$descendants = $this->getDescendantsPreorder($roots, $children, true);
		$selection = array_intersect($selection, $descendants);

		return $selection;
	}

	public function getSelectionIterator(array $selection) {
		$selection = $this->_filter($selection, $children);
		if(!$selection) {
			return new EmptyIterator();
		}

		$tree = new stdClass();
		$tree->children = $children;
		$tree->parents = $this->getParentsFromTree($tree->children);
		$tree->nodes = $this->fetchTreeNodes(array_keys($tree->parents));

		foreach($selection as $key) {
			$nodes[] = new SelectriTableTreeDataNode($this, $tree, $key);
		}

		return new ArrayIterator($nodes);
	}

	public function getTreeIterator($start = null) {
		$roots = $this->cfg->getRoots();
		if(!$roots) {
			return null;
		}
		$rootValue = strval($this->cfg->getTreeRootValue());

		$endpoints = $roots;

		// clean start
		if($start === null) {
			$rootStart = true;
		} else {
			$start = strval($start);
			$start === $rootValue ? $rootStart = true : $endpoints[] = $start;
		}

		// start tree
		$tree = new stdClass();
		$tree->children = $this->fetchAncestorOrSelfTree($endpoints);

		// prepare roots
		if($rootStart) {
			$roots = $this->getPreorder($roots, $tree->children, true);
			$roots && $tree->nodes = $roots[0] === $rootValue // implies count($roots) == 1 because unnested
				? $this->fetchTreeNodes(array($rootValue), true, true)
				: $this->fetchTreeNodes($roots);

		} else {
			// filter start
			if(!in_array($start, $this->getDescendantsPreorder($roots, $tree->children, true))) {
				return null;
			}
			$this->fetchLevels($tree, array($start));
		}

		$roots = array_keys((array) $tree->nodes);
		if(!$roots) {
			return null;
		}

		$tree->parents = $this->getParentsFromTree($tree->children);
		$this->addUnfolded($tree, $roots);

		// build first level
		foreach($roots as $rootKey) {
			$first[] = new SelectriTableTreeDataNode($this, $tree, $rootKey);
		}

		// get & fetch path node of first level
		foreach($first as $node) foreach($node->getPathKeys() as $key) {
			$pathKeys[$key] = true;
		}
		unset($pathKeys[$rootValue]);
		if($pathKeys) foreach($this->fetchTreeNodes(array_keys($pathKeys)) as $key => $node) {
			$tree->nodes[$key] = $node;
		}

		return array(new ArrayIterator($first), $rootStart ? null : $start);
	}

	public function getPathIterator($key) {
		$roots = $this->cfg->getRoots();
		if(!$roots) {
			return null;
		}

		// start tree
		$tree = new stdClass();
		$tree->children = $this->fetchAncestorOrSelfTree(array_merge($roots, array($key)));

		// prepare roots
		$roots = $this->getPreorder($roots, $tree->children, true);
		$rootValue = strval($this->cfg->getTreeRootValue());
		$tree->nodes = $roots[0] === $rootValue // implies count($roots) == 1 because unnested
			? $this->fetchTreeNodes(array($rootValue), true, true)
			: $this->fetchTreeNodes($roots);
		$roots = array_keys($tree->nodes);

		$tree->parents = $this->getParentsFromTree($tree->children);

		// fetch levels along the path
		if(!in_array($key, $roots)) {
			$node = new SelectriTableTreeDataNode($this, $tree, $key);
			$pathKeys = $node->getPathKeys();
			foreach($pathKeys as $i => $key) if(in_array($key, $roots)) {
				$rootsInPath = true;
				$pathKeys = array_slice($pathKeys, 0, $i + 1);
				break;
			}
			if(!$rootsInPath) {
				return null;
			}
			$this->fetchLevels($tree, $pathKeys);
		}

		// build first level
		foreach($roots as $rootKey) {
			$first[] = new SelectriTableTreeDataNode($this, $tree, $rootKey);
		}

		// get & fetch path node of first level
		foreach($first as $node) foreach($node->getPathKeys() as $key) {
			$pathKeys[] = $key;
		}
		if($pathKeys) foreach($this->fetchTreeNodes($pathKeys) as $key => $node) {
			$tree->nodes[$key] = $node;
		}

		return new ArrayIterator($first);
	}

	public function getSearchIterator($search) {
		$keywords = $this->parseKeywords($search);
		if(!$keywords) {
			return new EmptyIterator();
		}

		$query = $this->buildTreeSearchQuery();
		$expr = $this->getTreeSearchExpr(count($keywords), $columnCnt);
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

	public function generateTreeLabel(array $node) {
		return call_user_func($this->cfg->getTreeLabelCallback(), $node, $this, $this->cfg);
	}

	public function generateTreeIcon(array $node) {
		return call_user_func($this->cfg->getTreeIconCallback(), $node, $this, $this->cfg);
	}

	public function generateTreeContent(array $node) {
		return '';
	}

	protected function buildTreeSelectExpr() {
		$columns = $this->cfg->getTreeColumns();
		$columns[] = $this->cfg->getTreeKeyColumn();
		$columns[] = $this->cfg->getTreeParentKeyColumn();
		return implode(', ', array_unique($columns));
	}

	protected function buildSelectableExpr() {
		return $this->cfg->getSelectableExpr() ? $this->cfg->getSelectableExpr() : '1';
	}

	protected function buildTreeNodeQuery() {
		if($this->treeNodeQuery) {
			return $this->treeNodeQuery;
		}

		$query = <<<EOT
SELECT		%s AS _key,
			%s AS _parentKey,
			COUNT(child._key) != 0 AS _hasChildren,
			COUNT(grandchild._key) != 0 AS _hasGrandChildren,
			(%s) AS _isSelectable,
			%s

FROM		%s AS tree

LEFT JOIN	( SELECT %s AS _key, %s AS _parentKey FROM %s %s
			) AS child ON child._parentKey = tree.%s

LEFT JOIN	( SELECT %s AS _key, %s AS _parentKey FROM %s %s
			) AS grandchild ON grandchild._parentKey = child._key

WHERE		%%s IN (%%s)
%s
GROUP BY	%s
EOT;
		// select
		$params[] = $this->cfg->getTreeKeyColumn();
		$params[] = $this->cfg->getTreeParentKeyColumn();
		$params[] = $this->buildSelectableExpr();
		$params[] = $this->buildTreeSelectExpr();
		// from
		$params[] = $this->cfg->getTreeTable();
		// child join
		$params[] = $this->cfg->getTreeKeyColumn();
		$params[] = $this->cfg->getTreeParentKeyColumn();
		$params[] = $this->cfg->getTreeTable();
		$params[] = $this->cfg->getTreeConditionExpr('WHERE');
		$params[] = $this->cfg->getTreeKeyColumn();
		// grandchild join
		$params[] = $this->cfg->getTreeKeyColumn();
		$params[] = $this->cfg->getTreeParentKeyColumn();
		$params[] = $this->cfg->getTreeTable();
		$params[] = $this->cfg->getTreeConditionExpr('WHERE');
		// where
		$params[] = $this->cfg->getTreeConditionExpr('AND');
		// group by
		$params[] = $this->cfg->getTreeKeyColumn();

		$query = vsprintf($query, $params);

		$this->getConfig()->getTreeMode() == 'inner' && $query .= PHP_EOL . 'HAVING _hasChildren';

		return $this->treeNodeQuery = $query;
	}

	protected function fetchTreeNodes(array $ids = null, $children = false, $order = false, $limit = PHP_INT_MAX) {
		if(!$ids) {
			return array();
		}

		$refColumn = $children ? $this->cfg->getTreeParentKeyColumn() : $this->cfg->getTreeKeyColumn();
		$query = sprintf($this->buildTreeNodeQuery(), $refColumn, self::generateWildcards($ids));
		$order && $query .= PHP_EOL . $this->cfg->getTreeOrderByExpr('ORDER BY');

		$result = $this->db->prepare($query)->limit($limit)->execute($ids);

		$nodes = array();
		while($result->next()) {
			$nodes[strval($result->_key)] = $result->row();
		}
		return $nodes;
	}

	protected function fetchLevels(stdClass $tree, array $parentKeys) {
		$nodes = $this->fetchTreeNodes($parentKeys, true, true);
		if(!$nodes) {
			return;
		}

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

	protected function addUnfolded(stdClass $tree, array $roots) {
		$unfolded = $this->getWidget()->getUnfolded();
		if(!$unfolded) {
			return;
		}

		$unfolded = $this->fetchTreeNodes($unfolded);
		if(!$unfolded) {
			return;
		}

		$unfoldedChildren = array();
		foreach($unfolded as $key => $node) {
			$unfoldedChildren[strval($node['_parentKey'])][$key] = true;
		}

		$unfolded = array_keys($unfolded);
		$this->getWidget()->setUnfolded($unfolded); // cleaned out inexistant values to avoid longterm leaking...

		$unfolded = $this->getDescendantsPreorder(array_intersect($roots, $unfolded), $unfoldedChildren, true);
		$unfolded && $this->fetchLevels($tree, $unfolded);
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

	protected function buildTreeSearchQuery() {
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

		// select
		$params[] = $this->cfg->getTreeKeyColumn();
		// from
		$params[] = $this->cfg->getTreeTable();
		// child join
		$params[] = $this->cfg->getTreeKeyColumn();
		$params[] = $this->cfg->getTreeParentKeyColumn();
		$params[] = $this->cfg->getTreeTable();
		$params[] = $this->cfg->getTreeConditionExpr('WHERE');
		$params[] = $this->cfg->getTreeKeyColumn();
		// where
		$params[] = $this->cfg->getTreeConditionExpr('AND');
		$params[] = $this->cfg->getSelectableExpr('AND');
		// group by
		$params[] = $this->cfg->getTreeKeyColumn();

		$query = vsprintf($query, $params);

		// having
		$this->getConfig()->getTreeMode() == 'inner' && $query .= PHP_EOL . 'HAVING COUNT(child._id) != 0';

		return $query;
	}

	protected function getTreeSearchExpr($keywordCnt, &$columnCnt) {
		$columns = $this->cfg->getTreeSearchColumns();
		$keyColumn = $this->cfg->getTreeKeyColumn();
		in_array($keyColumn, $columns) || $columns[] = $keyColumn;

		foreach($columns as $column) {
			$condition[] = $column . ' LIKE CONCAT(\'%\', CONCAT(?, \'%\'))';
		}
		$condition = implode(' OR ', $condition);

		$columnCnt = count($columns);
		return '(' . implode(') AND (', array_fill(0, $keywordCnt, $condition)) . ')';
	}

	protected function fetchAncestorOrSelfTree(array $ids) {
		if(!$ids) {
			return array();
		}

		$root = strval($this->cfg->getTreeRootValue());
		$qids = array_map('strval', $ids);
		$qids = array_diff($qids, array($root));
		$ids = array_flip($qids);
		$ids[$root] = true;

		$query = sprintf('SELECT %s AS pid FROM %s WHERE %s IN (',
			$this->cfg->getTreeParentKeyColumn(),
			$this->cfg->getTreeTable(),
			$this->cfg->getTreeKeyColumn()
		);
		while($qids) {
			$nodes = $this->db->prepare($query . self::generateWildcards($qids) . ')')->execute($qids);
			$qids = array();
			while($nodes->next()) {
				$id = strval($nodes->pid);
				isset($ids[$id]) || $qids[] = $id;
				$ids[$id] = true;
			}
		}

		$query = sprintf(
			'SELECT	%s AS id, %s AS pid
			FROM	%s
			WHERE	%s IN (%s)
			%s
			%s',
			$this->cfg->getTreeKeyColumn(),
			$this->cfg->getTreeParentKeyColumn(),
			$this->cfg->getTreeTable(),
			$this->cfg->getTreeKeyColumn(),
			self::generateWildcards($ids),
			$this->cfg->getTreeConditionExpr('AND'),
			$this->cfg->getTreeOrderByExpr('ORDER BY')
		);
		$nodes = $this->db->prepare($query)->execute(array_keys($ids));
		$tree[$root] = array();
		while($nodes->next()) {
			$id = strval($nodes->id);
			$tree[strval($nodes->pid)][$id] = true;
			$tree[$id] = (array) $tree[$id];
		}

		return $tree;
	}

	protected function getParentsFromTree(array $tree) {
		if(!$tree) {
			return array();
		}

		$parents = array();
		foreach($tree as $pid => $children) {
			foreach($children as $id => $_) {
				$parents[$id] = $pid;
			}
		}

		return $parents;
	}

	/**
	 * Returns the given node IDs of the given tree in preorder,
	 * optionally removing nested node IDs.
	 *
	 * Removes duplicates.
	 *
	 * @param array $ids
	 * @param array $tree
	 * @param boolean $unnest
	 * @return array
	 */
	protected function getPreorder(array $ids, array $tree, $unnest = false) {
		if(!$ids) {
			return array();
		}

		$root = strval($this->cfg->getTreeRootValue());
		$ids = array_flip(array_map('strval', $ids));
		$preorder = array();

		if(isset($ids[$root])) {
			$preorder[] = $root;
			if($unnest) {
				return $preorder;
			}
		}

		$helper = $unnest ? 'getPreorderHelperUnnest' : 'getPreorderHelper';
		$this->$helper($preorder, $ids, $tree, $root);

		return $preorder;
	}

	private function getPreorderHelper(array &$preorder, array $ids, array $tree, $current) {
		foreach($tree[$current] as $id => $_) {
			isset($ids[$id]) && $preorder[] = $id;
			$tree[$id] && $this->getPreorderHelper($preorder, $ids, $tree, $id);
		}
	}

	private function getPreorderHelperUnnest(array &$preorder, array $ids, array $tree, $current) {
		foreach($tree[$current] as $id => $_) {
			if(isset($ids[$id])) {
				$preorder[] = $id;
			} elseif($tree[$id]) {
				$this->getPreorderHelperUnnest($preorder, $ids, $tree, $id);
			}
		}
	}

	/**
	 * Returns the descendants of each of the given node IDs of the given tree
	 * in preorder, optionally adding the given node IDs themselves.
	 * Duplicates are not removed, invalid and nested nodes are not removed. Use
	 * getPreorder(..) with $unnest set to true before calling this method,
	 * if this is the desired behavior.
	 *
	 * @param array $ids
	 * @param string $tree
	 * @param boolean $self
	 * @return array
	 */
	protected function getDescendantsPreorder(array $ids, array $tree, $self = false) {
		if(!$ids) {
			return array();
		}

		$ids = array_map('strval', $ids);
		$preorder = array();
		foreach($ids as $id) {
			$self && $preorder[] = $id;
			isset($tree[$id]) && $this->getDescendantsPreorderHelper($preorder, $tree, $id);
		}

		return $preorder;
	}

	private function getDescendantsPreorderHelper(array &$preorder, array $tree, $current) {
		foreach($tree[$current] as $id => $_) {
			$preorder[] = $id;
			isset($tree[$id]) && self::getDescendantsPreorderHelper($preorder, $tree, $id);
		}
	}

	public static function generateWildcards(array $args) {
		return rtrim(str_repeat('?,', count($args)), ',');
	}

}
