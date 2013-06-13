<?php

interface SelectriData {

	/**
	 * @throws Exception If this data instance is not configured correctly
	 * @return void
	 */
	public function validate();

	/**
	 * Filters the given primary keys for values identifing only existing
	 * records.
	 *
	 * @param array<string> $selection An array of primary key values in their
	 * 		string representation
	 * @return array<string> The input array with all invalid values removed
	 */
	public function filter(array $selection);

	/**
	 * @return SelectriWidget The widget this data belongs to
	 */
	public function getWidget();

	/**
	 * Returns an iterator over selected nodes identified by the given primary
	 * keys.
	 *
	 * The returned nodes should NOT be traversed recursivly through the node's
	 * getChildrenIterator method.
	 *
	 * @param array<string> $selection An array of primary key values in their
	 * 		string representation
	 * @return Iterator<SelectriNode> An iterator over the nodes identified by
	 * 		the given primary keys
	 */
	public function getSelectionIterator(array $selection);

	/**
	 * Returns an iterator over the children of the node identified by the given
	 * primary key or an iterator over the root nodes, if no primary key value
	 * is given.
	 *
	 * When recursivly traversing the structure through the node's
	 * getChildrenIterator, all nodes for that all ancestors are unfolded (open)
	 * are visited.
	 *
	 * Whether or not a node is considered unfolded is implementation specific,
	 * but implementors are recommended to use the getUnfolded method of this
	 * data's widget to determine a nodes unfolded state.
	 *
	 * @param string|null $start A primary key value in its string
	 * 		representation or null
	 * @return Iterator<SelectriNode> An iterator over nodes
	 */
	public function getTreeIterator($start = null);

	/**
	 * Returns an iterator over the roots nodes.
	 *
	 * When recursivly traversing the structure through the node's
	 * getChildrenIterator, all nodes on levels, that are on the path down to
	 * the given primary key, are visited.
	 *
	 * @param string $key A primary key value in its string
	 * 		representation
	 * @return Iterator<SelectriNode> An iterator over the root nodes
	 */
	public function getPathIterator($key);

	/**
	 * Returns an iterator over nodes matching the given search query.
	 *
	 * The returned nodes should NOT be traversed recursivly through the node's
	 * getChildrenIterator method.
	 *
	 * @param string $query The search query to match nodes against
	 * @return Iterator<SelectriNode> An iterator over nodes matched by the given
	 * 		search query
	 */
	public function getSearchIterator($query);

}
