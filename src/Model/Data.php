<?php

namespace Hofff\Contao\Selectri\Model;

use Hofff\Contao\Selectri\Exception\SelectriException;
use Hofff\Contao\Selectri\Widget;

interface Data {

	/**
	 * @return Widget The widget this data belongs to
	 */
	public function getWidget();

	/**
	 * @throws SelectriException If this data instance is not configured correctly
	 * @return void
	 */
	public function validate();

	/**
	 * Returns an iterator over nodes identified by the given primary
	 * keys.
	 *
	 * The returned nodes should NOT be traversed recursivly through the node's
	 * getChildrenIterator method.
	 *
	 * @param string[]|array $keys An array of primary key values in their
	 * 		string representation
	 * @param boolean $selectableOnly
	 * @return \Iterator An iterator over the nodes identified by
	 * 		the given primary keys
	 */
	public function getNodes(array $keys, $selectableOnly = true);

	/**
	 * Filters the given primary keys for values identifing only existing
	 * records.
	 *
	 * @param string[]|array $keys An array of primary key values in their
	 * 		string representation
	 * @return string[]|array The input array with all invalid values removed
	 */
	public function filter(array $keys);

	/**
	 * @return boolean
	 */
	public function isBrowsable();

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
	 * @param string|null $key A primary key value in its string
	 * 		representation or null
	 * @return \Iterator An iterator over nodes
	 */
	public function browseFrom($key = null);

	/**
	 * Returns an iterator over the roots nodes.
	 *
	 * When recursivly traversing the structure through the node's
	 * getChildrenIterator, all nodes on levels, that are on the path down to
	 * the given primary key, are visited.
	 *
	 * @param string $key A primary key value in its string
	 * 		representation
	 * @return \Iterator An iterator over the root nodes
	 */
	public function browseTo($key);

	/**
	 * @return boolean
	 */
	public function isSearchable();

	/**
	 * Returns an iterator over nodes matching the given search query.
	 *
	 * The returned nodes should NOT be traversed recursivly through the node's
	 * getChildrenIterator method.
	 *
	 * @param string $query The search query to match nodes against
	 * @param integer $limit
	 * @param integer $offset
	 * @return \Iterator An iterator over nodes matched by the given
	 * 		search query
	 */
	public function search($query, $limit, $offset = 0);

	/**
	 * @return boolean
	 */
	public function hasSuggestions();

	/**
	 * Return an iterator over nodes that are being suggest to select.
	 *
	 * The returned nodes should NOT be traversed recursivly through the node's
	 * getChildrenIterator method.
	 *
	 * @param integer $limit
	 * @param integer $offset
	 * @return \Iterator An iterator over suggested nodes
	 */
	public function suggest($limit, $offset = 0);

}
