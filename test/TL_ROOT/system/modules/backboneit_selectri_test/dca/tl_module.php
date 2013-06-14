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
		'min'			=> 0,
		'max'			=> 1,
		'mandatory'		=> null,
		'multiple'		=> null,
		'searchLimit'	=> 20,
		'findInSet'		=> false,
		'sort'			=> 'list',
		'height'		=> 'auto',
		'tl_class'		=> 'clr',
		'data'			=> $data,
		'treeTable'		=> 'tl_page',
		'mode'			=> 'all',
	)
);