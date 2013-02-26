<?php

/**
 * Concept based on John Brands original code.
 * 
 * @copyright	backboneIT | Oliver Hoff 2012
 * @author		Oliver Hoff <oliver@hofff.com>
 * @license		LGPL v3
 */
class SelectriWidget extends Widget {

	protected $strTemplate = 'be_widget';
	
	protected $data;
	protected $min = 0;
	protected $max = 1;
	protected $mode = 'all';
	protected $sort = 'list';
	protected $height;
	
	protected $table;
	protected $field;

	public function __construct($attrs = false) {
		parent::__construct($attrs);
	}

	public function __get($key) {
		switch($key) {
			case 'value':
				if($this->getMaxSelected() == 1) {
					return count($values) ? reset($values) : null;
				} elseif($this->findInSet) {
					return implode(',', $this->varValue);
				} else {
					return $this->varValue;
				}
				break;
			
			case 'table':
			case 'strTable':
				return $this->table;
				break;
			
			case 'field':
			case 'strField':
				return $this->field;
				break;
				
			case 'mandatory':
				return $this->getMinSelected() > 0;
				break;
				
			case 'multiple':
				return $this->getMaxSelected() > 1;
				break;
				
			default:
				return parent::__get($key);
				break;
		}
	}

	public function __set($key, $value) {
		switch ($key) {
			case 'value':
				// convert previous values stored as an array
				$value = deserialize($value);
				$this->varValue = $this->findInSet && !is_array($value) ? explode(',', $value) : (array) $value;
				break;
			
			case 'table':
			case 'strTable':
				$this->table = $value;
				break;
					
			case 'field':
			case 'strField':
				$this->field = $value;
				break;

			case 'mandatory':
				if(!$value) {
					$this->setMinSelected(0);
				} elseif($this->getMinSelected() == 0) {
					$this->setMinSelected(1);
				}
				break;
				
			case 'multiple':
				if(!$value) {
					$this->setMaxSelected(1);
				} elseif($this->getMaxSelected() == 1) {
					$this->setMaxSelected(PHP_INT_MAX);
				}
				break;
			
			default:
				parent::__set($key, $value);
				break;
		}
	}
	
	public function addAttributes($attrs) {
		if(!is_array($attrs)) {
			return;
		}
		
		$data = $attrs['data'];
		
		if(!is_object($data)) {
			if(!strlen($data)) {
				$data = new SelectriContaoTableDataFactory();
			} elseif(is_subclass_of($data, 'SelectriDataFactory')) {
				$data = new $data();
			} else {
				throw new Exception('invalid selectri data factory configuration');
			}
			$data->setParameters($attrs);
			$data->setWidget($this);
			$this->setData($data->getData());
			
		} elseif($data instanceof SelectriDataFactory) {
			$data->setWidget($this);
			$this->setData($data->getData());
			
		} else {
			throw new Exception('invalid selectri data factory configuration');
		}

		isset($attrs['mandatory']) && $this->mandatory = $attrs['mandatory'];
		isset($attrs['multiple']) && $this->multiple = $attrs['multiple'];
		
		isset($attrs['height']) && $this->setHeight($attrs['height']);
		isset($attrs['sort']) && $this->setSort($attrs['sort']);
		isset($attrs['mode']) && $this->setMode($attrs['mode']);
		isset($attrs['min']) && $this->setMinSelected($attrs['min']);
		isset($attrs['max']) && $this->setMaxSelected($attrs['max']);
		
		unset(
			$attrs['mandatory'],
			$attrs['multiple'],
			$attrs['height'],
			$attrs['sort'],
			$attrs['mode'],
			$attrs['min'],
			$attrs['max'],
			$attrs['table'],
			$attrs['data']
		);
		parent::addAttributes($attrs);
	}

	public function validate() {
		$name = $this->strName;
		if(preg_match('@^([a-z_][a-z0-9_-]*)((?:\[[a-z_][a-z0-9_-]*\])+)$@i', $name, $match)) {
			$name = $match[1];
			$path = explode('][', trim($match[2], '[]'));
		}
		
		$values = $this->Input->postRaw($name);
		if($path) { $i = 0; $n = count($path); do {
			if(!is_array($values)) {
				unset($values);
				break;
			}
			$values = $values[$path[$i]];
		} while(++$i < $n); }
		
		$values = $this->getData()->filter((array) $values);
		
		if(count($values) < $this->getMinSelected()) {
			if($this->getMinSelected() > 1) {
				$this->addError(sprintf($GLOBALS['TL_LANG']['stri']['errMin'],
					$this->label,
					$this->getMinSelected()
				));
			} else {
				$this->addError(sprintf($GLOBALS['TL_LANG']['ERR']['mandatory'],
					$this->label
				));
			}
		
		} elseif(count($values) > $this->getMaxSelected()) {
			$this->addError(sprintf($GLOBALS['TL_LANG']['stri']['errMax'],
				$this->label,
				$this->getMaxSelected()
			));
		}

		$this->hasErrors() && $this->class = 'error';
		$this->varValue = $values;
		$this->blnSubmitInput = true;
	}

	public function generate() {
		$data = $this->getData();
		if(!$data) {
			throw new Exception('no selectri data configuration set');
		}
		$data->validate();
		
		$action = $this->Input->get('striAction');
		if($action) {
			return $this->generateAjax($action);
		}
		
		$data->setSelection($this->varValue);
		
		static $blnScriptsInjected;
		if(!$blnScriptsInjected) {
			$GLOBALS['TL_JAVASCRIPT'][] = 'system/modules/backboneit_selectri/html/selectri.js';
			$GLOBALS['TL_CSS'][] = 'system/modules/backboneit_selectri/html/selectri.css';
		}
		
		ob_start();
		include $this->getTemplate('selectri_container');
		return ob_get_clean();
	}

	public function generateAjax($action) {
		while(ob_end_clean());
		switch($action) {
			case 'levels':
				$start = $this->Input->get('striStart');
				strlen($start) || $start = null;
				$response = $this->generateLevels($start);
				break;				
			case 'search':
				$search = $this->Input->get('striSearch');
				$response = $this->generateSearch($search);
				break;
		}
		header('Content-Type: application/json');
		echo json_encode($response);
		exit;
	}
	
	public function generateLevels($start = null) {
		$level = $this->getData()->getTreeIterator($start);
		$level->rewind();
		if(!$level->valid()) { // empty iterator
			return null;
		}
		
		$iterators = array();
		$start && $response['start'] = $start;
		$insert = &$response['first'];
		
		for(;;) {
			ob_start();
			include $this->getTemplate('selectri_children');
			$insert = ob_get_clean();
			
			// push levels onto stack
			foreach($level as $node) if($node->hasChildren()) {
				$iterators[] = array($node->getKey(), $node->getChildrenIterator());
			}
			
			// next non empty level iterator
			do {
				list($key, $level) = array_pop($iterators);
				if(!$level) {
					break 2;
				}
				$level->rewind();
			} while(!$level->valid());
			
			$insert = &$response['levels'][strval($key)];
		}
		
		return $response;
	}
	
	public function getData() {
		return $this->data;
	}
	
	public function setData(SelectriData $data) {
		$this->data = $data;
	}
	
	public function isOpen() {
		return false;
	}
	
	public function getInputName() {
		return $this->name . '[]';
	}
	
	public function getHeight() {
		if(!strlen($this->height)) {
			return 'auto';
		}
		if(preg_match('@^[1-9][0-9]*$@', $this->height)) {
			return $this->height . 'px';
		}
		return $this->height;
	}
	
	public function setHeight($height) {
		$this->height = $height;
		return $this;
	}
	
	public function getSort() {
		return $this->sort;
	}
	
	public function setSort($sort) {
		switch($sort) {
			case true: $sort = 'list'; break;
			case 'list': break;
			case 'tree': throw new Exception('tree sortable not implemented'); break;
			default: $sort = 'preorder'; break;
		}
		$this->sort = $sort;
		return $this;
	}
	
	public function getMode() {
		return $this->mode;
	}
	
	public function setMode($mode) {
		switch($mode) {
			case 'leaf': break;
			case 'inner': break;
			default: $mode = 'all'; break;
		}
		$this->mode = $mode;
		return $this;
	}
	
	public function getMinSelected() {
		return min($this->min, $this->getMaxSelected());
	}
	
	public function setMinSelected($min) {
		$this->min = max(0, intval($min));
		return $this;
	}
	
	public function getMaxSelected() {
		return $this->max;
	}
	
	public function setMaxSelected($max) {
		$this->max = max(1, intval($max));
		return $this;
	}
	
	public function isDataContainerDriven() {
		return strlen($this->table) && strlen($this->field);
	}
	
	public function getDataContainerTable() {
		return $this->table;
	}
	
	public function getDataContainerField() {
		return $this->field;
	}
	
	public function getFieldDCA() {
		return $this->isDataContainerDriven()
			? $GLOBALS['TL_DCA'][$table]['fields'][$field]
			: array();
	}
	
	public function getUnfolded() {
		if(!$this->isDataContainerDriven()) {
			return array();
		}
		$strSessionKey = sprintf('selectri$%s$%s',
			$this->getDataContainerTable(),
			$this->getDataContainerField()
		);
		return (array) $this->Session->get($strSessionKey);
	}
	
	public function setUnfolded(array $arrUnfolded) {
		if(!$this->isDataContainerDriven()) {
			return;
		}
		$strSessionKey = sprintf('selectri$%s$%s',
			$this->getDataContainerTable(),
			$this->getDataContainerField()
		);
		$this->Session->set($strSessionKey, $arrUnfolded);
	}
	
}
