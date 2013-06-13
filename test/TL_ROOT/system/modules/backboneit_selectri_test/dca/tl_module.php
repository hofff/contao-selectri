<?php

$data = SelectriContaoTableDataFactory::create();
$data->setTreeTable('tl_page');
$data->getConfig()->setTreeSearchColumns(array('title'));
// $data->getConfig()->setSelectableExpr('id IN (' . implode(',', range(1,10)) . ')');

$GLOBALS['TL_DCA']['tl_module']['fields']['backboneit_navigation_roots'] = array(
	'label'			=> &$GLOBALS['TL_LANG']['tl_module']['backboneit_navigation_roots'],
	'exclude'		=> true,
	'inputType'		=> 'selectri',
	'eval'			=> array(
		// basic widget configuration

		/**
		 * How many nodes must be selected
		 * @var integer defaults to 0
		 */
		'min'			=> 0,

		/**
		 * How many nodes can be selected at most
		 * @var integer defaults to 1
		 */
		'max'			=> 1,

		/**
		 * The input is converted to a comma separated list.
		 * Already stored arrays are converted, when saved again.
		 * @var boolean defaults to false
		 */
		'findInSet'		=> false,

		/**
		 * The selection type
		 *
		 * - list: the selection is a list (order matters), the selection is
		 * 		sortable
		 * - preorder (not implemented): the selection is a set (order does not
		 * 		matter), the selection is not sortable and preordered
		 * - tree (not implemented): the selection is a tree, the selection can
		 * 		be arranged
		 *
		 * @var string defaults to "list"
		 */
		'sort'			=> 'list',

		/**
		 * The CSS value and unit of the height of the selection tree
		 * @var string default to "auto"
		 */
		'height'		=> 'auto',

		/**
		 * @var string
		 */
		'tl_class'		=> 'clr',

		/**
		 * If a factory object is given, these factory will be used to generate
		 * a data instance for created widgets.
		 *
		 * If a string is given, it must name a class implementing
		 * SelectriDataFactory. When creating the widget, a new factory instance
		 * is created and its setParameters method is called with the attributes
		 * of the widgets (the widgets attributes contains all settings from the
		 * DCA eval array).
		 *
		 * @var string|SelectriDataFactory defaults to "SelectriContaoTableDataFactory"
		 */
		'data'			=> $data,

		// factory specific configuration (used by setParameters method)

		/**
		 * The parameter is used by SelectriContaoTableDataFactory::setParameters
		 * method and preconfigures the data config according to common Contao
		 * standards (key = id, parentKey = pid etc.)
		 *
		 * @var string The name of the table to fetch tree nodes from
		 */
		'treeTable'		=> 'tl_page',

		/**
		 * The parameter is used by SelectriTableDataFactory::setParameters
		 * method and the behavior is implemented by SelectriTableTreeData
		 *
		 * - all: all nodes are selectable
		 * - leaf: only leaf nodes are selectable
		 * - inner: only inner nodes are selectable
		 *
		 * @var string defaults "all"
		 */
		'mode'			=> 'all',

	)
);