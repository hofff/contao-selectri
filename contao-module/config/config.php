<?php

$GLOBALS['BE_FFL']['selectri'] = 'Hofff\\Contao\\Selectri\\Widget';

if (defined('TL_MODE') && TL_MODE === 'BE') {
    $GLOBALS['TL_JAVASCRIPT'][]	= 'system/modules/hofff_selectri/assets/js/hofff_selectri.js';
    $GLOBALS['TL_CSS'][]		= 'system/modules/hofff_selectri/assets/css/hofff_selectri.css';
}
