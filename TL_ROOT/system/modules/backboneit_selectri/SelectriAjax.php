<?php

/**
 * @copyright	backboneIT | Oliver Hoff 2012
 * @author		Oliver Hoff <oliver@hofff.com>
 * @license		LGPL v3
 */
class SelectriAjax extends System {

	public function executePreActions($strAction) {
		if($strAction == 'toggleTabletree') {
			$blnState = $this->Input->post('state');
		} elseif($strAction == 'loadTabletree') {
			$blnState = true;
		} else {
			return;
		}
		
		$varKey = $this->Input->post('key');
		strlen($varKey) || $this->outputAndExit();
		
		$arrConfig = $this->Input->post('config');
		$this->validateWidgetConfig($arrConfig, $this->Input->post('hash'));
		
		if($strAction == 'loadTabletree') {
			$objTableTree = new $GLOBALS['BE_FFL']['tableTree']($arrConfig);
			$strTableTree = $objTableTree->generateAjax($varKey, intval($this->Input->post('level')));
		}
	
		$this->updateToggleState($arrConfig['strTable'], $arrConfig['strField'], $varKey, $blnState);
		$this->outputAndExit($strTableTree);
	}
	
	private function updateToggleState($strTable, $strField, $varKey, $blnState) {
		if(!strlen($strTable) || !strlen($strField)) {
			return;
		}
		
		$strSessionKey = 'tblt$' . $strTable . '$' . $strField;
		
		$arrNodes = $this->Session->get($strSessionKey);
		if($blnState) {
			$arrNodes[$varKey] = 1;
		} else {
			unset($arrNodes[$varKey]);
		}
		$this->Session->set($strSessionKey, $arrNodes);
	}
	
	private function validateWidgetConfig($arrConfig, $strHash) {
		$arrConfig['token'] = $GLOBALS['TL_CONFIG']['encryptionKey'];
		ksort($arrConfig);
		if($strHash != sha1(serialize($arrConfig))) {
			header($_SERVER['SERVER_PROTOCOL'] . ' 400 Bad Request');
			exit;
		}
	}

	private function outputAndExit($strContent = '') {
		$arrJSON = array('content' => preg_replace('@\s{2,}@', ' ', $strContent));
		version_compare(VERSION, '2.10', '<') || $arrJSON['token'] = REQUEST_TOKEN;
		echo json_encode($arrJSON);
		exit;
	}
	
	protected function __construct() {
		parent::__construct();
	}
	
	private function __clone() {	
	}
	
	private static $objInstance;
	
	public static function getInstance() {
		if(!isset(self::$objInstance))
			self::$objInstance = new self();
			
		return self::$objInstance;
	}
	
}
