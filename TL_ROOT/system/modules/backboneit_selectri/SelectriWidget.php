<?php

class SelectriWidget extends Widget {

	protected $strTemplate = 'be_widget';

	protected $data;
	protected $min = 0;
	protected $max = 1;
	protected $searchLimit = 20;
	protected $jsOptions = array();
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
				$value = $this->getValue();
				if($this->getMaxSelected() == 1) {
					if(!count($value)) {
						return null;
					}
					$value = reset($value);
					return $this->canonical ? $value : $value['_key'];
				}
				if($this->findInSet) {
					return implode(',', array_keys($value));
				}
				if($this->canonical) {
					return $value;
				}
				return array_keys($value);
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
				if(!is_array($value)) {
					$value = $this->findInSet ? explode(',', $value) : (array) $value;
				}
				$converted = array();
				if($this->canonical) {
					foreach($value as $key => $row) {
						if(!is_array($row)) {
							$converted[$row] = array('_key' => $row);
						} else {
							isset($row['_key']) ? $key = $row['_key'] : $row['_key'] = $key;
							$converted[$key] = $row;
						}
					}
				} else {
					foreach($value as $key) {
						$converted[$key] = array('_key' => $key);
					}
				}
				$this->setValue($converted);
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

		if(isset($attrs['data'])) {
			$data = $attrs['data'];

			if(is_callable($data)) {
				$data = call_user_func($data, $this, $attrs);
			}

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
				$this->setData($data->createData());

			} elseif($data instanceof SelectriDataFactory) {
				$data->setWidget($this);
				$this->setData($data->createData());

			} else {
				throw new Exception('invalid selectri data factory configuration');
			}

			unset($attrs['data']);
		}

		isset($attrs['mandatory']) && $this->mandatory = $attrs['mandatory'];
		isset($attrs['multiple']) && $this->multiple = $attrs['multiple'];
		unset($attrs['mandatory'], $attrs['multiple']);

		foreach(array(
			'height'			=> 'setHeight',
			'sort'				=> 'setSort',
			'min'				=> 'setMinSelected',
			'max'				=> 'setMaxSelected',
			'searchLimit'		=> 'setSearchLimit',
			'jsOptions'			=> 'setJSOptions',
		) as $key => $method) if(isset($attrs[$key])) {
			$this->$method($attrs[$key]);
			unset($attrs[$key]);
		}

		parent::addAttributes($attrs);
	}

	public function validate() {
		$name = $this->name;
		if(preg_match('@^([a-z_][a-z0-9_-]*)((?:\[[^\]]+\])+)$@i', $name, $match)) {
			$name = $match[1];
			$path = explode('][', trim($match[2], '[]'));
		}

		$values = $this->Input->postRaw($name);
		if($path) for($i = 0, $n = count($path); $i < $n; $i++) {
			if(!is_array($values)) {
				unset($values);
				break;
			}
			$values = $values[$path[$i]];
		}

		$values = (array) $values;
		$selection = $this->getData()->filter((array) $values['selection']);

		if(count($selection) < $this->getMinSelected()) {
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

		} elseif(count($selection) > $this->getMaxSelected()) {
			$this->addError(sprintf($GLOBALS['TL_LANG']['stri']['errMax'],
				$this->label,
				$this->getMaxSelected()
			));
		}

		$selection = array_combine($selection, $selection);
		foreach($selection as $key => &$data) {
			$data = (array) $values['data'][$key];
			$data['_key'] = $key;
		}

		$this->hasErrors() && $this->class = 'error';
		$this->setValue($selection);
		$this->blnSubmitInput = true;
	}

	public function generate() {
		$data = $this->getData();
		if(!$data) {
			throw new Exception('no selectri data configuration set');
		}
		$data->validate();

		if($this->Input->get('striID') == $this->strId) {
			$action = $this->Input->get('striAction');
			return $action ? $this->generateAjax($action) : '';
		}

		$GLOBALS['TL_JAVASCRIPT']['selectri.js'] = 'system/modules/backboneit_selectri/html/js/selectri.js';
		$GLOBALS['TL_CSS']['selectri.css'] = 'system/modules/backboneit_selectri/html/css/selectri.css';

		$options = array(
			'name' => $this->getInputName(),
			'min' => $this->getMinSelected(),
			'max' => $this->getMaxSelected()
		);
		$options = array_merge($options, $this->getJSOptions());

		ob_start();
		include $this->getTemplate('selectri_container');
		return ob_get_clean();
	}

	public function generateAjax($action) {
		while(ob_end_clean());
		switch($action) {
			case 'levels':
				$key = $this->Input->get('striKey');
				strlen($key) || $key = null;
				list($level, $start) = $this->getData()->getTreeIterator($key);
				$response = $this->generateLevels($level, $start);
				$response['key'] = $key;
				break;
			case 'path':
				$key = $this->Input->get('striKey');
				strlen($key) || $key = null;
				$level = $this->getData()->getPathIterator($key);
				$response = $this->generateLevels($level);
				$response['key'] = $key;
				break;
			case 'search':
				$search = $this->Input->get('striSearch');
				$response = $this->generateSearch($search);
				$response['search'] = $search;
				break;
			case 'toggle':
				$key = $this->Input->post('striKey');
				$unfolded = $this->getUnfolded();
				if($this->Input->post('striOpen')) {
					$unfolded[] = $key;
				} else {
					$unfolded = array_diff($unfolded, array($key));
				}
				$this->setUnfolded($unfolded);
				$response['key'] = $key;
				break;
		}
		$response['action'] = $action;
		$response['token'] = REQUEST_TOKEN;
		header('Content-Type: application/json');
		echo json_encode($response);
		exit;
	}

	protected function renderChildren($level) {
		ob_start();
		include $this->getTemplate('selectri_children');
		return ob_get_clean();
	}

	public function generateLevels($level, $start = null) {
		if(!$level) {
			return array('empty' => true, 'messages' => array($GLOBALS['TL_LANG']['stri']['noOptions']));
		}

		if($start) {
			$response['start'] = $start;
			$insert = &$response['levels'][$start];
		} else {
			$insert = &$response['first'];
		}

		$iterators = array();
		for(;;) {
			$insert = $this->renderChildren($level);

			// push levels onto stack
			foreach($level as $node) if($node->isOpen()) {
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

	public function generateSearch($search) {
		$result = $this->getData()->getSearchIterator($search);
		$result->rewind();
		if(!$result->valid()) {
			return array('messages' => array(sprintf($GLOBALS['TL_LANG']['stri']['searchEmpty'], $search)));
		}
		ob_start();
		include $this->getTemplate('selectri_search');
		return array('result' => ob_get_clean());
	}

	public function getData() {
		return $this->data;
	}

	public function setData(SelectriData $data) {
		$this->data = $data;
	}

	public function getValue() {
		return $this->varValue;
	}

	public function setValue($value) {
		$this->varValue = $value;
		return $this;
	}

	public function isOpen() {
		return $this->mandatory && !$this->varValue;
	}

	public function getInputName() {
		return $this->name . '[selection][]';
	}

	public function getAdditionalInputBaseName() {
		return $this->name . '[data]';
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

	public function getJSOptions() {
		return $this->jsOptions;
	}

	public function setJSOptions($jsOptions) {
		$this->jsOptions = (array) $jsOptions;
		return $this;
	}

	public function getSearchLimit() {
		return $this->searchLimit;
	}

	public function setSearchLimit($searchLimit) {
		$this->searchLimit = max(1, intval($searchLimit));
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
			? $GLOBALS['TL_DCA'][$this->getDataContainerTable()]['fields'][$this->getDataContainerField()]
			: array();
	}

	public function getUnfolded() {
		if(!$this->isDataContainerDriven()) {
			return array();
		}
		return array_map('strval', array_keys((array) $this->Session->get($this->getSessionKey())));
	}

	public function setUnfolded(array $unfolded) {
		if(!$this->isDataContainerDriven()) {
			return;
		}
		$this->Session->set($this->getSessionKey(), array_flip(array_map('strval', array_values($unfolded))));
	}

	public function getSessionKey() {
		return sprintf('selectri$%s$%s',
			$this->getDataContainerTable(),
			$this->getDataContainerField()
		);
	}

	// fuck contao...
	public function _getTheme() {
		return parent::getTheme();
	}

}
