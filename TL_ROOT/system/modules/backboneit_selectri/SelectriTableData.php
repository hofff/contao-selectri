<?php

class SelectriTableData extends SelectriAbstractData {
	
	protected $db;
	
	protected $cfg;
	
	public function __construct(SelectriWidget $widget, SelectriTableDataConfig $cfg) {
		parent::__construct($widget);
		$this->db = Database::getInstance();
		$this->cfg = clone $cfg;
	}
	
	protected function getTreeSelectFieldsExpr(array &$groups = null) {
		$groups = array();
		
		$groups['key'] = array(count($columns), 1, 'single');
		$columns[] = $this->cfg->getTreeKeyColumn();
		
		$groups['parentKey'] = array(count($columns), 1, 'single');
		$columns[] = $this->cfg->getTreeParentKeyColumn();

		$labelColumns = $this->cfg->getTreeLabelColumns();
		$groups['label'] = array(count($columns), count($labelColumns));
		$columns = array_merge($columns, $labelColumns);
		
// 		$searchColumns = $this->cfg->getTreeSearchColumns();
// 		$groups['search'] = array(count($columns), count($searchColumns));
// 		$columns = array_merge($columns, $searchColumns);

		$additionalColumns = array_unique($this->cfg->getTreeAdditionalColumns());
		$groups['additional'] = array(count($columns), count($additionalColumns), 'assoc', $additionalColumns);
		$columns = array_merge($columns, $additionalColumns);

		return implode(', ', $columns);
	}
	
	protected function getItemSelectFieldsExpr(array &$groups = null) {
		$columns['key'] = $this->cfg->getItemKeyColumn();
		$this->cfg->hasTree() && $columns['treeKey'] = $this->cfg->getItemTreeKeyColumn();
		$columns['label'] = $this->cfg->getItemLabelColumns();
		$columns['search'] = $this->cfg->getItemSearchColumns();
		$columns['addition'] = $this->cfg->getItemAdditionalColumns();

		$groups = array();
		$offset = 0;
		foreach($columns as $key => $cnt) {
			if(is_array($cnt)) {
				$cnt = count($cnt);
				$groups[$key] = array($offset, $cnt);
				$offset += $cnt;
			} else {
				$groups[$key] = array($offset, 1, true);
				$offset++;
			}
		}
		
		return implode(', ', call_user_func_array('array_merge', $columns));
	}
	
	protected function prepareConditionExpr($expr, $clause = 'AND') {
		strlen($expr) && $expr = $clause . ' (' . $expr . ')';
		return $expr;
	}
	
	protected function prepareOrderByExpr($expr, $clause = 'ORDER BY') {
		strlen($expr) && $expr = $clause . ' ' . $expr;
		return $expr;
	}
	
	protected function fetchTreeNodes(array $ids = null, $children = false, $order = false, $limit = PHP_INT_MAX) {
		if(!$ids) {
			return array();
		}
		$query = sprintf(
			'SELECT 	%s,
						COUNT(child._id) != 0 AS _hasChildren,
						COUNT(grandchild._id) != 0 AS _hasGrandChildren
			
			FROM		%s AS tree
			
			LEFT JOIN	( SELECT %s AS _id, %s AS _pid FROM %s %s
						) AS child ON child._pid = tree.%s
			
			LEFT JOIN	( SELECT %s AS _id, %s AS _pid FROM %s %s
						) AS grandchild ON grandchild._pid = child._id
			
			WHERE		%s IN (%s)
			%s
			GROUP BY	%s
			%s
			%s',
			
			// select
			$this->getTreeSelectFieldsExpr($groups),
			
			// from
			$this->cfg->getTreeTable(),
			
			// child join
			$this->cfg->getTreeKeyColumn(),
			$this->cfg->getTreeParentKeyColumn(),
			$this->cfg->getTreeTable(),
			$this->prepareConditionExpr($this->cfg->getTreeConditionExpr(), 'WHERE'),
			$this->cfg->getTreeKeyColumn(),

			// grandchild join
			$this->cfg->getTreeKeyColumn(),
			$this->cfg->getTreeParentKeyColumn(),
			$this->cfg->getTreeTable(),
			$this->prepareConditionExpr($this->cfg->getTreeConditionExpr(), 'WHERE'),
			
			// where
			$children ? $this->cfg->getTreeParentKeyColumn() : $this->cfg->getTreeKeyColumn(),
			self::generateWildcards($ids),
			$this->prepareConditionExpr($this->cfg->getTreeConditionExpr()),
			
			// group by
			$this->cfg->getTreeKeyColumn(),
			
			// having
			$this->getWidget()->getMode() == 'inner' ? 'HAVING _hasChildren' : '',
			
			// order by
			$order ? $this->prepareOrderByExpr($this->cfg->getTreeOrderByExpr()) : ''
		);
		$result = $this->db->prepare($query)->limit($limit)->execute($ids);
		$nodes = array();
		while($row = $result->fetchRow()) {
			$key = strval($row[0]);
			foreach($groups as $group => $slice) {
				list($offset, $length, $mode, $keys) = $slice;
				if($mode == 'single') {
					$nodes[$key][$group] = $row[$offset];
					continue;
				}
				$slice = array_slice($row, $offset, $length);
				if($mode == 'assoc' && $length) {
					$slice = array_combine($keys, $slice);
				}
				$nodes[$key][$group] = $slice;
			}
			$offset += $length;
			$nodes[$key]['hasChildren'] = $row[$offset];
			$offset++;
			$nodes[$key]['hasGrandChildren'] = $row[$offset];
		}
		return $nodes;
	}
	
	protected function fetchLevels(stdClass $tree, array $parentKeys) {
		foreach($this->fetchTreeNodes($parentKeys, true, true) as $key => $node) {
			$tree->nodes[$key] = $node;
		}
		
		if(!$tree->nodes) {
			return;
		}
		
		// remove existing children arrays for fetched nodes (to maintain order)
		foreach($tree->nodes as $node) {
			unset($tree->children[strval($node['parentKey'])]);
		}
		// insert nodes into tree
		foreach($tree->nodes as $node) {
			$key = strval($node['key']);
			$parentKey = strval($node['parentKey']);
			$tree->children[$parentKey][$key] = true;
			$tree->parents[$key] = $parentKey;
		}
	}
	
	public function validate() {
		parent::validate();
		if(!$this->cfg->hasTree() && !$this->cfg->hasItem()) {
			throw new Exception('invalid table data config: neither a tree nor an item table given');
		}
		$this->validateTreeTable();
		$this->validateItemTable();
	}

	protected function validateTreeTable() {
		if(!$this->cfg->hasTree()) {
			return;
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
	
	protected function validateItemTable() {
		if(!$this->cfg->hasItem()) {
			return;
		}
		$query = sprintf(
			'SELECT %s
			FROM	%s
			%s
			ORDER BY %s
			LIMIT 1',
			$this->getItemSelectFieldsExpr(),
			$this->cfg->getItemTable(),
			$this->prepareConditionExpr($this->cfg->getItemConditionExpr(), 'WHERE'),
			$this->cfg->getItemOrderByExpr()
		);
		try {
			$result = $this->db->prepare($query)->execute();
		} catch(Exception $e) {
			throw new Exception('invalid item table configuration: ' . $e->getMessage());
		}
	}
	
	public function getConfig() {
		return $this->cfg;
	}
	
	public function filter(array $selection) {
		return array_keys($this->fetchTreeNodes($selection));
	}
	
	public function getSelectionIterator(array $selection) {
		if(!$selection) {
			return new EmptyIterator();
		}
		if($this->cfg->isTreeOnlyMode()) {
			return $this->getTreeOnlyModeSelectionIterator($selection);
		}
		if($this->cfg->isItemOnlyMode()) {
			return $this->getItemOnlyModeSelectionIterator($selection);
		}
		return $this->getTreeItemModeSelectionIterator($selection);
	}
	
	protected function getTreeOnlyModeSelectionIterator(array $selection) {
		$roots = $this->cfg->getRoots();
		$tree = new stdClass();
		$tree->children = $this->getAncestorOrSelfTree(array_merge($roots, $selection));
		$tree->parents = $this->getParentsFromTree($tree->children);
		$tree->nodes = $this->fetchTreeNodes(array_keys($tree->parents));
		$selection = array_intersect($selection, $this->getDescendantsPreorder($roots, $tree->children));
		$nodes = array();
		foreach($selection as $key) {
			$nodes[] = new SelectriTableDataTreeNode($this, $tree, $key);
		}
		return new ArrayIterator($nodes);
	}
	
	protected function getItemOnlyModeSelectionIterator() {
		throw new Exception('item mode not implemented');
	}
	
	protected function getTreeItemModeSelectionIterator() {
		throw new Exception('tree and item mode not implemented');
	}
	
	public function getTreeIterator($start = null) {
		$start === $this->cfg->getTreeRootValue() && $start = null;
		if($this->cfg->isTreeOnlyMode()) {
			return $this->getTreeOnlyModeTreeIterator($start);
		}
		if($this->cfg->isItemOnlyMode()) {
			return $this->getItemOnlyModeTreeIterator();
		}
		return $this->getTreeItemModeTreeIterator();
	}
	
	protected function getTreeOnlyModeTreeIterator($start = null) {
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
		foreach($unfolded as $node) {
			$tree->children[strval($node['parentKey'])][strval($node['key'])] = true;
		}
		
		$tree->parents = $this->getParentsFromTree($tree->children);
		$this->fetchLevels($tree, $this->getDescendantsPreorder($start, $tree->children, true));
		
		// build first level
		foreach($start as $startKey) foreach($tree->children[$startKey] as $key => $_) {
			$first[] = new SelectriTableDataTreeNode($this, $tree, $key);
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
	
	protected function getItemOnlyModeTreeIterator() {
		throw new Exception('item mode not implemented');
	}
	
	protected function getTreeItemModeTreeIterator() {
		throw new Exception('tree and item mode not implemented');
	}
	
	public function getPathIterator($key) {
		if($this->cfg->isTreeOnlyMode()) {
			return $this->getTreeOnlyModePathIterator($key);
		}
		if($this->cfg->isItemOnlyMode()) {
			return $this->getItemOnlyModePathIterator();
		}
		return $this->getTreeItemModePathIterator();
	}
	
	protected function getTreeOnlyModePathIterator($key) {
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
			$first[] = new SelectriTableDataTreeNode($this, $tree, $key);
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
	
	protected function getItemOnlyModePathIterator() {
		throw new Exception('item mode not implemented');
	}
	
	protected function getTreeItemModePathIterator() {
		throw new Exception('tree and item mode not implemented');
	}
	
	protected function prepareSearch($search) {
		if(defined('PREG_BAD_UTF8_OFFSET')) {
			return preg_split('/[^\pL\pN]+(?:[\pL\pN][^\pL\pN]+)?/iu', $search, null, PREG_SPLIT_NO_EMPTY);
		} else {
			return preg_split('/(?:^|[^\w]+)(?:[\w](?:$|[^\w]+))*/i', $search, null, PREG_SPLIT_NO_EMPTY);
		}
	}
	
	protected function getTreeSearchExpr($searchCnt, &$columnCnt) {
		$columns = $this->cfg->getTreeSearchColumns();
		$columns[] = $this->cfg->getTreeKeyColumn();
		$columnCnt = count($columns);
		
		foreach($columns as $column) {
			$condition[] = $column . ' LIKE CONCAT(\'%\', CONCAT(?, \'%\'))';
		}
		$condition = implode(' OR ', $condition);
			
		return '(' . implode(') AND (', array_fill(0, $searchCnt, $condition)) . ')';
	}
	
	public function getSearchIterator($search) {
		$search = $this->prepareSearch($search);
		if(!$search) {
			return new EmptyIterator();
		}
		if($this->cfg->isTreeOnlyMode()) {
			$query = sprintf(
				'SELECT 	%s AS id
				FROM		%s AS tree
				
				LEFT JOIN	( SELECT %s AS _id, %s AS _pid FROM %s %s
							) AS child ON child._pid = tree.%s
				
				WHERE		(%s)
				%s
				GROUP BY	%s
				%s',
					
				// select & from
				$this->cfg->getTreeKeyColumn(),
				$this->cfg->getTreeTable(),
					
				// child join
				$this->cfg->getTreeKeyColumn(),
				$this->cfg->getTreeParentKeyColumn(),
				$this->cfg->getTreeTable(),
				$this->prepareConditionExpr($this->cfg->getTreeConditionExpr(), 'WHERE'),
				$this->cfg->getTreeKeyColumn(),
				
				// where
				$this->getTreeSearchExpr(count($search), $columnCnt),
				$this->prepareConditionExpr($this->cfg->getTreeConditionExpr()),
					
				// group by
				$this->cfg->getTreeKeyColumn(),
				
				// having
				$this->getWidget()->getMode() == 'inner' ? 'HAVING COUNT(child._id) != 0' : ''
			);
			foreach($search as $word) {
				$params[] = array_fill(0, $columnCnt, $word);
			}
			$params = call_user_func_array('array_merge', $params);
			$found = $this->db->prepare($query)->limit(20)->execute($params)->fetchEach('id');
			return $this->getSelectionIterator($found);
		}
		if($this->cfg->isItemOnlyMode()) {
			return $this->getItemOnlyModePathIterator();
		}
		return $this->getTreeItemModePathIterator();
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
			$this->prepareConditionExpr($this->cfg->getTreeConditionExpr()),
			$this->prepareOrderByExpr($this->cfg->getTreeOrderByExpr())
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
