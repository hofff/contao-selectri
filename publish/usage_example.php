<?php
array(
	'label'			=> &$GLOBALS['TL_LANG']['tl_module']['rootPage'],
	'exclude'		=> true,
	'inputType'		=> 'tableTree',
	'eval'			=> array(
		// treeSelect specific settings
		'itemColumn'	=> 'tl_article.title',	// string,	required, optional if treeColumn given, takes precedence over tableColumn
		'treeColumn'	=> 'tl_page.title',		// string,	required, optional if itemColumn or tableColumn given
//		'sortColumn'	=> 'sorting',			// string,	optional, defaults to "sorting"
		'root'			=> array(1),			// scalar/array, optional, defaults to 0
//		'multiple'		=> true,				// boolean,	optional, defaults to false
//		'findInSet'		=> false,				// boolean,	optional, defaults to false
//		'title'			=> 'MyTitle',			// string,	optional
//		'titleValues'	=> true,				// boolean,	optional, defaults to true
//		'children'		=> false,				// boolean,	optional, defaults to false, unused in tree & item mode
//		'childrenOnly'	=> false,				// boolean,	optional, defaults to false, unused in tree & item mode
		'mandatory'		=> true,				// boolean,	optional, defaults to false
//		'alwaysSave'	=> false,				// boolean,	optional, defaults to false
//		'encrypt'		=> false,				// boolean,	optional, defaults to false, borken in Contao (missing padding)
//		'treeTemplate'	=> 'tabletree',			// string,	optional, defaults to "tabletree"
//		'rgxp'			=> 'date',				// string,	optional, supported, but senseless
		'tl_class'		=> 'clr',				// string,	optional

//		'treeSource'	=> 'table|options|callback', // string,		future
//		'treeCallback'	=> array(),				// callback,		future
//		'labelCallback'	=> array(),				// callback,		future
//		'selectionMode' => '|children|childrenOnly', // string,		future

//		'itemTable'		=> 'tl_article',		// string,			future
//		'itemValue'		=> 'id',				// string,			future
//		'itemParent'	=> 'pid',				// string,			future
//		'itemLabel'		=> 'title',				// string/array,	future
//		'itemSorting'	=> 'sorting',			// string/array,	future
//		'treeTable'		=> 'tl_page',			// string,			future
//		'treeValue'		=> 'id',				// string,			future
//		'treeParent'	=> 'pid',				// string,			future
//		'treeLabel'		=> 'title',				// string/array,	future
//		'treeSorting'	=> 'sorting',			// string/array,	future

//		'fieldType'		=> 'radio',				// deprecated, use multiple instead, defaults to "radio"
//		'tableColumn'	=> 'tl_article.title',	// deprecated, use itemColumn and/or treeColumn
	)
);