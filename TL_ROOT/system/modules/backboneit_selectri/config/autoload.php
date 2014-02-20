<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2013 Leo Feyer
 *
 * @package Backboneit_selectri
 * @link    https://contao.org
 * @license http://www.gnu.org/licenses/lgpl-3.0.html LGPL
 */


/**
 * Register the classes
 */
ClassLoader::addClasses(array
(
	'SelectriAbstractDataFactory'    => 'system/modules/backboneit_selectri/SelectriAbstractDataFactory.php',
	'SelectriContaoTableDataFactory' => 'system/modules/backboneit_selectri/SelectriContaoTableDataFactory.php',
	'SelectriData'                   => 'system/modules/backboneit_selectri/SelectriData.php',
	'SelectriDataFactory'            => 'system/modules/backboneit_selectri/SelectriDataFactory.php',
	'SelectriLabelFormatter'         => 'system/modules/backboneit_selectri/SelectriLabelFormatter.php',
	'SelectriNode'                   => 'system/modules/backboneit_selectri/SelectriNode.php',
	'SelectriTableItemData'          => 'system/modules/backboneit_selectri/SelectriTableItemData.php',
	'SelectriTableItemDataNode'      => 'system/modules/backboneit_selectri/SelectriTableItemDataNode.php',
	'SelectriTableDataConfig'        => 'system/modules/backboneit_selectri/SelectriTableDataConfig.php',
	'SelectriTableDataFactory'       => 'system/modules/backboneit_selectri/SelectriTableDataFactory.php',
	'SelectriTableTreeData'          => 'system/modules/backboneit_selectri/SelectriTableTreeData.php',
	'SelectriTableTreeDataNode'      => 'system/modules/backboneit_selectri/SelectriTableTreeDataNode.php',
	'SelectriTreeIterator'           => 'system/modules/backboneit_selectri/SelectriTreeIterator.php',
	'SelectriWidget'                 => 'system/modules/backboneit_selectri/SelectriWidget.php',
));


/**
 * Register the templates
 */
TemplateLoader::addFiles(array
(
	'selectri_children'  => 'system/modules/backboneit_selectri/templates',
	'selectri_container' => 'system/modules/backboneit_selectri/templates',
	'selectri_search'    => 'system/modules/backboneit_selectri/templates',
));
