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
		return array_intersect($selection, array_keys($this->fetchTreeNodes($selection)));
	}
	
	public function getSelectionIterator(array $selection) {
		if(!$selection) {
			return new EmptyIterator();
		}
		$roots = $this->cfg->getRoots();
		$tree = new stdClass();
		$tree->children = $this->getAncestorOrSelfTree(array_merge($roots, $selection));
		$tree->parents = $this->getParentsFromTree($tree->children);
		$tree->nodes = $this->fetchTreeNodes(array_keys($tree->parents));
		$selection = array_intersect($selection, $this->getDescendantsPreorder($roots, $tree->children));
		$nodes = array();
		foreach($selection as $key) {
			$nodes[] = new SelectriTableTreeDataNode($this, $tree, $key);
		}
		return new ArrayIterator($nodes);
	}
	
	public function getTreeIterator($start = null) {
		$start === $this->cfg->getTreeRootValue() && $start = null;
		$start = $start === null ? array() : array($start);
		$roots = $this->cfg->getRoots();
		
		// start tree
		$tree = new stdClass();
		$tree->children = $this->getAncestorOrSelfTree(array_merge($roots, $start));
		
		// filter start
		if(!$start) {
			$rootStart = true;
			$start = $this->getPreorder($roots, $tree->children, true);
			if(!$start) {
				return null;
			}
		} elseif(!array_intersect($start, $this->getDescendantsPreorder($roots, $tree->children))) {
			return null;
		}
		
		// add unfolded
		$unfolded = $this->getWidget()->getUnfolded();
		$unfolded = $this->fetchTreeNodes($unfolded); // TODO performance? half wayne
		$this->getWidget()->setUnfolded(array_keys($unfolded)); // cleaned out inexistant values to avoid longterm leaking...
		foreach($unfolded as $key => $node) {
			$tree->children[strval($node['_parentKey'])][$key] = true;
		}
		
		$tree->parents = $this->getParentsFromTree($tree->children);
		$this->fetchLevels($tree, $this->getDescendantsPreorder($start, $tree->children, true));
		
		// build first level
		foreach($start as $startKey) foreach($tree->children[$startKey] as $key => $_) {
			$first[] = new SelectriTableTreeDataNode($this, $tree, $key);
		}
		
		// get path node keys of first level
		foreach($first as $node) foreach($node->getPathKeys() as $key) {
			$pathKeys[$key] = true;
		}
		
		// fetch data for path nodes
		unset($pathKeys[$this->cfg->getTreeRootValue()]);
		if($pathKeys) foreach($this->fetchTreeNodes(array_keys($pathKeys)) as $key => $node) {
			$tree->nodes[$key] = $node;
		}
		
		return array(new ArrayIterator($first), $rootStart ? null : $start[0]);
	}
	
	public function getPathIterator($key) {
		$roots = $this->cfg->getRoots();
		
		// start tree
		$tree = new stdClass();
		$tree->children = $this->getAncestorOrSelfTree(array_merge($roots, array($key)));
		
		// prepare roots
		$roots = $this->getPreorder($roots, $tree->children, true);
		if(!in_array($key, $this->getDescendantsPreorder($roots, $tree->children))) {
			return null;
		}
		
		$tree->parents = $this->getParentsFromTree($tree->children);
		unset($tree->children[$tree->parents[$key]]);
		$this->fetchLevels($tree, $this->getDescendantsPreorder($roots, $tree->children, true));
		
		// build first level
		foreach($roots as $rootKey) foreach($tree->children[$rootKey] as $key => $_) {
			$first[] = new SelectriTableTreeDataNode($this, $tree, $key);
		}
		
		// get path node keys of first level
		foreach($first as $node) foreach($node->getPathKeys() as $key) {
			$pathKeys[] = $key;
		}
		// fetch data for path nodes
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
		
		$query = $this->getTreeSearchQuery();
		$expr = $this->getTreeSearchExpr(count($keywords), $columnCnt);
		
		foreach($search as $word) {
			$params[] = array_fill(0, $columnCnt, $word);
		}
		$params = call_user_func_array('array_merge', $params);
		$found = $this->db->prepare($query)->limit($this->getWidget()->getSearchLimit())->execute($params)->fetchEach('id');
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
	
	protected function buildTreeNodeQuery() {
		if($this->treeNodeQuery) {
			return $this->treeNodeQuery;
		}
		
		$query = <<<EOT
SELECT		%s AS _key,
			%s AS _parentKey,
			COUNT(child._key) != 0 AS _hasChildren,
			COUNT(grandchild._key) != 0 AS _hasGrandChildren,
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
		
		$this->getWidget()->getMode() == 'inner' && $query .= PHP_EOL . 'HAVING _hasChildren';
		
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
		// group by
		$params[] = $this->cfg->getTreeKeyColumn();
		
		$query = vsprintf($query, $params);
		
		// having
		$this->getWidget()->getMode() == 'inner' && $query .= PHP_EOL . 'HAVING COUNT(child._id) != 0';
		
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
	
	protected function getAncestorOrSelfTree(array $ids) {
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
				$this->getPreorderHelperFilterUnnest($preorder, $ids, $tree, $id);
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
