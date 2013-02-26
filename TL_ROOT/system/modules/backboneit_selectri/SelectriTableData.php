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
		$columns['key'] = $this->cfg->getTreeKeyColumn();
		$columns['parentKey'] = $this->cfg->getTreeParentKeyColumn();
		$columns['label'] = $this->cfg->getTreeLabelColumns();
		$columns['search'] = $this->cfg->getTreeSearchColumns();
		$columns['addition'] = $this->cfg->getTreeAdditionalColumns();

		$groups = array();
		$offset = 0;
		foreach($columns as $key => &$cnt) {
			if(is_array($cnt)) {
				$groups[$key] = array($offset, count($cnt));
				$offset += count($cnt);
			} else {
				$cnt = array($cnt);
				$groups[$key] = array($offset, 1, true);
				$offset++;
			}
		}
		
		return implode(', ', call_user_func_array('array_merge', $columns));
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
	
	protected function getTreeNodeData(array $ids = null, $children = false, $order = false, $limit = PHP_INT_MAX) {
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
				$nodes[$key][$group] = $slice[2] ? $row[$slice[0]] : array_slice($row, $slice[0], $slice[1]);
			}
			$offset = $slice[0] + $slice[1];
			$nodes[$key]['hasChildren'] = $row[$offset];
			$offset++;
			$nodes[$key]['hasGrandChildren'] = $row[$offset];
		}
		return $nodes;
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
			$nodes = $this->getTreeNodeData(array($this->cfg->getTreeRootValue()), true, true, 1);
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
	
	public function hasSelectableNodes() {
		return true; // TODO
	}
	
	public function getSelectionIterator() {
		if(!$this->getSelection()) {
			return new EmptyIterator();
		}
		if($this->cfg->isTreeOnlyMode()) {
			return $this->getTreeOnlyModeSelectionIterator();
		}
		if($this->cfg->isItemOnlyMode()) {
			return $this->getItemOnlyModeSelectionIterator();
		}
		return $this->getTreeItemModeSelectionIterator();
	}
	
	protected function getTreeOnlyModeSelectionIterator() {
		$tree = new stdClass();
		$tree->children = $this->getAncestorOrSelfTree($this->getSelection());
		$tree->parents = $this->getParentsFromTree($tree->children);
		$tree->nodes = $this->getTreeNodeData(array_keys($tree->parents));
		foreach($this->getSelection() as $key) {
			$selection[] = new SelectriTableDataTreeNode($this, $tree, $key);
		}
		return new ArrayIterator($selection);
	}
	
	protected function getItemOnlyModeSelectionIterator() {
		throw new Exception('item mode not implemented');
	}
	
	protected function getTreeItemModeSelectionIterator() {
		throw new Exception('tree and item mode not implemented');
	}
	
	public function getTreeIterator($start = null) {
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
		$tree = new stdClass();
		$roots = $this->cfg->getRoots();
		
		// filter start	
		$tree->children = $this->getAncestorOrSelfTree(array_merge($roots, $start));
		$roots = $this->getPreorder($roots, $tree->children, true);
		if(!$start) {
			$start = $roots;
		} elseif(!array_intersect($start, $this->getDescendantsPreorder($roots, $tree->children, true))) {
			return new EmptyIterator();
		}
		
		// add unfolded
		$unfolded = $this->getWidget()->getUnfolded();
		$unfolded = $this->getTreeNodeData($unfolded); // TODO performance? half wayne
		$this->getWidget()->setUnfolded(array_keys($unfolded)); // cleaned out inexistant values to avoid longterm leaking...
		foreach($unfolded as $node) $tree->children[strval($node['parentKey'])][strval($node['key'])] = true;
		
		// fetch node data
		$render = $this->getDescendantsPreorder($start, $tree->children, true);
		$tree->nodes = $this->getTreeNodeData($render, true, true);
		
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
		
		// build first level
		foreach($start as $startKey) {
			foreach($tree->children[$startKey] as $key => $_) {
				$first[] = new SelectriTableDataTreeNode($this, $tree, $key);
			}
		}
		
		return new ArrayIterator($first);
	}
	
	protected function getItemOnlyModeTreeIterator() {
		throw new Exception('item mode not implemented');
	}
	
	protected function getTreeItemModeTreeIterator() {
		throw new Exception('tree and item mode not implemented');
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
		
		$ids = array_unique(array_map('strval', $ids));
		if(count($ids) < 2) {
			return $ids;
		}
		
		$root = strval($this->cfg->getTreeRootValue());
		$ids = array_flip($ids);
		$preorder = array();
		
		if(isset($ids[$root])) {
			if($unnest) {
				return array($root);
			} elseif(count($ids) < 3) {
				unset($ids[$root]);
				return array($root) + array_keys($ids);
			}
			$preorder[] = $root;
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
