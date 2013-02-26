<?php

$GLOBALS['TL_DCA']['tl_module']['palettes']['navigation']
	= '{title_legend},name,headline,type;{nav_legend},levelOffset,showLevel,hardLimit,showProtected;{reference_legend:hide},rootPage;{template_legend:hide},navigationTpl;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID,space';

$GLOBALS['TL_DCA']['tl_module']['fields']['rootPage'] = array(
	'label'			=> &$GLOBALS['TL_LANG']['tl_module']['rootPage'],
	'exclude'		=> true,
	'inputType'		=> 'selectri',
	'eval'			=> array(
		// dca
//		'alwaysSave'	=> false,				// boolean,	optional, defaults to false
//		'encrypt'		=> false,				// boolean,	optional, defaults to false, borken in Contao (missing padding)
// 		'tl_class'		=> 'clr',				// string,	optional
		
		// widget
//		'multiple'		=> true,				// boolean,	optional, defaults to false
//		'mandatory'		=> true,				// boolean,	optional, defaults to false
//		'min'			=> 1,
//		'max'			=> 1,
//		'findInSet'		=> false,				// boolean,	optional, defaults to false
//		'mode'			=> '|leaf|inner',		// string,		future
//		'sort'			=> 'preorder|list|tree',
// 		'height'		=> 'auto|<css value and unit>'

		// data (sql adjacency list)
		'treeTable' => 'tl_page',
// 		'data' => SelectriTableDataFactory::create()
// 			->setTreeTable('tl_page')
// 			->setItemConfig('tl_article', 'id', 'pid', 'title', 'sorting')
// 			->setRoots(0)
// 		,
		
		// treeSelect specific settings


//		'treeSource'	=> 'table|options|callback', // string,		future
//		'treeCallback'	=> array(),				// callback,		future
//		'labelCallback'	=> array(),				// callback,		future


	)
);