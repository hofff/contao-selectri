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
		// widget
//		'min'			=> 1,
		'max'			=> PHP_INT_MAX,
//		'findInSet'		=> false,				// boolean,	optional, defaults to false
//		'mode'			=> '|leaf|inner',		// string,		future
//		'sort'			=> 'preorder|list|tree',
// 		'height'		=> 'auto|<css value and unit>',

// 		'treeTable'		=> 'tl_page',			// uses a SelectriContaoTableDataFactory
		'data'			=> $data,

		'tl_class'		=> 'clr',

	)
);