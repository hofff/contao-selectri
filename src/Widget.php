<?php

namespace Hofff\Contao\Selectri;

use Contao\Input;
use Contao\System;
use Contao\Widget as BaseWidget;
use Hofff\Contao\Selectri\Exception\SelectriException;
use Hofff\Contao\Selectri\Model\Data;
use Hofff\Contao\Selectri\Model\DataFactory;
use Hofff\Contao\Selectri\Model\Node;
use Hofff\Contao\Selectri\Util\ContaoUtil;

/**
 * @author Oliver Hoff <oliver@hofff.com>
 */
class Widget extends BaseWidget {
	/**
	 * Submit user input
	 * @var boolean
	 */
	protected $blnSubmitInput = true;

	/**
	 * @var Data
	 */
	protected $data;

	/**
	 * @var integer
	 */
	protected $min = 0;

	/**
	 * @var integer
	 */
	protected $max = 1;

	/**
	 * @var integer
	 */
	protected $searchLimit = 20;

	/**
	 * @var integer
	 */
	protected $suggestLimit = 20;

	/**
	 * @var string
	 */
	protected $suggestionsLabel;

	/**
	 * @var boolean
	 */
	protected $disableBrowsing = false;

	/**
	 * @var boolean
	 */
	protected $disableSearching = false;

	/**
	 * @var boolean
	 */
	protected $disableSuggestions = false;

	/**
	 * @var boolean
	 */
	protected $suggestOnlyEmpty = false;

	/**
	 * @var boolean
	 */
	protected $contentToggleable = false;

	/**
	 * @var array
	 */
	protected $jsOptions = [];

	/**
	 * @var string
	 */
	protected $sort = 'list';

	/**
	 * @var string
	 */
	protected $height;

	/**
	 * @var string|null
	 */
	protected $table;

	/**
	 * @var string|null
	 */
	protected $field;

	/**
	 * @param array|null|boolean $attrs
	 */
	public function __construct($attrs = false) {
		parent::__construct($attrs);
		$this->strTemplate = 'hofff_selectri_widget';
	}

	/**
	 * @see \Contao\Widget::__get()
	 */
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
					return serialize($value);
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

	/**
	 * @see \Contao\Widget::__set()
	 */
	public function __set($key, $value) {
		switch ($key) {
			case 'value':
				// convert previous values stored as an array
				$value = deserialize($value);
				if(!is_array($value)) {
					$value = $this->findInSet ? explode(',', $value) : (array) $value;
				}
				$converted = [];
				if($this->canonical) {
					foreach($value as $key => $row) {
						if(!is_array($row)) {
							$converted[$row] = [ '_key' => $row ];
						} else {
							isset($row['_key']) ? $key = $row['_key'] : $row['_key'] = $key;
							$converted[$key] = $row;
						}
					}
				} else {
					foreach($value as $key) {
						$converted[$key] = [ '_key' => $key ];
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

	/**
	 * @see \Contao\Widget::addAttributes()
	 */
	public function addAttributes($attrs) {
		if(!is_array($attrs)) {
			return;
		}

		$this->createDataFromAttributes($attrs);
		unset($attrs['data']);
		unset($attrs['dataFactory']);

		foreach([
			'mandatory'				=> false,
			'multiple'				=> false,
			'height'				=> 'setHeight',
			'sort'					=> 'setSort',
			'min'					=> 'setMinSelected',
			'max'					=> 'setMaxSelected',
			'searchLimit'			=> 'setSearchLimit',
			'suggestLimit'			=> 'setSuggestLimit',
			'suggestionsLabel'		=> 'setSuggestionsLabel',
			'jsOptions'				=> 'setJSOptions',
			'disableBrowsing'		=> 'setDisableBrowsing',
			'disableSearching'		=> 'setDisableSearching',
			'disableSuggestions'	=> 'setDisableSuggestions',
			'suggestOnlyEmpty'		=> 'setSuggestOnlyEmpty',
			'contentToggleable'		=> 'setContentToggleable',
		] as $key => $method) {
			if(!isset($attrs[$key])) {
				continue;
			}
			if($method) {
				$this->$method($attrs[$key]);
			} else {
				$this->$key = $attrs[$key];
			}
			unset($attrs[$key]);
		}

		parent::addAttributes($attrs);
	}

	/**
	 * @see \Contao\Widget::validate()
	 */
	public function validate() {
		$name = $this->name;
		$match = null;
		$path  = '';
		if(preg_match('@^([a-z_][a-z0-9_-]*)((?:\[[^\]]+\])+)$@i', $name, $match)) {
			$name = $match[1];
			$path = explode('][', trim($match[2], '[]'));
		}

		$values = Input::postRaw($name);
		if($path) for($i = 0, $n = count($path); $i < $n; $i++) {
			if(!is_array($values)) {
				unset($values);
				break;
			}
			$values = $values[$path[$i]];
		}

		$values = (array) $values;
		$selection = $this->getData()->filter((array) $values['selected']);

		if(count($selection) < $this->getMinSelected()) {
			if($this->getMinSelected() > 1) {
				$this->addError(sprintf($GLOBALS['TL_LANG']['hofff_selectri']['err_min'],
					$this->label,
					$this->getMinSelected()
				));
			} else {
				$this->addError(sprintf($GLOBALS['TL_LANG']['ERR']['mandatory'],
					$this->label
				));
			}

		} elseif(count($selection) > $this->getMaxSelected()) {
			$this->addError(sprintf($GLOBALS['TL_LANG']['hofff_selectri']['err_max'],
				$this->label,
				$this->getMaxSelected()
			));
		}

		$selection = array_combine($selection, $selection);
		foreach($selection as $key => &$data) {
			$data = (array) $values['data'][$key];
			$data['_key'] = $key;
		}
		unset($data);

		$this->hasErrors() && $this->class = 'error';
		$this->setValue($selection);
		$this->blnSubmitInput = true;
	}

	/**
	 * @see \Contao\Widget::parse()
	 */
	public function parse($attrs = null) {
		System::loadLanguageFile('hofff_selectri');

		if(!is_array($attrs) || empty($attrs['noAjax'])) {
			$this->generateAjax();
		}

		$this->checkData();

		return parent::parse($attrs);
	}

	/**
	 * @see \Contao\Widget::generate()
	 */
	public function generate() {
		return $this->parse([ 'noAjax' => true ]);
	}

	/**
	 * @return void
	 */
	public function generateAjax() {
		if(Input::get('hofff_selectri_field') != $this->strId) {
			return;
		}

		while(ob_end_clean());

		$this->checkData();

		$action = Input::get('hofff_selectri_action');
		$method = 'generateAjax' . ucfirst($action);
		$response = method_exists($this, $method) ? $this->$method() : null;

		if($response === null) {
			header('HTTP/1.1 400 Bad Request');

		} else {
			header('Content-Type: application/json');
			echo json_encode($response);
		}

		exit;
	}

	/**
	 * @return array|null
	 */
	public function generateAjaxLevels() {
		if(!$this->getData()->isBrowsable()) {
			return null;
		}

		$key = Input::post('hofff_selectri_key');
		strlen($key) || $key = null;

		list($nodes, $start) = $this->getData()->browseFrom($key);
		$nodes = iterator_to_array($nodes);

		$response = [];
		$response['action'] = 'levels';
		$response['key'] = $key;
		$response['empty'] = !$nodes;
		$response['empty'] && $response['messages'][] = $GLOBALS['TL_LANG']['hofff_selectri']['no_options'];
		$this->renderLevels($response, $nodes, $start);

		return $response;
	}

	/**
	 * @return array|null
	 */
	public function generateAjaxPath() {
		if(!$this->getData()->isBrowsable()) {
			return null;
		}

		$key = Input::post('hofff_selectri_key');
		if(!strlen($key)) {
			return null;
		}

		$nodes = $this->getData()->browseTo($key);
		$nodes = iterator_to_array($nodes);

		$response = [];
		$response['action'] = 'path';
		$response['key'] = $key;
		$response['empty'] = !$nodes;
		$response['empty'] && $response['messages'][] = $GLOBALS['TL_LANG']['hofff_selectri']['no_options'];
		$this->renderLevels($response, $nodes, null);

		return $response;
	}

	/**
	 * @return array|null
	 */
	public function generateAjaxToggle() {
		if(!$this->getData()->isBrowsable()) {
			return null;
		}

		$key = Input::post('hofff_selectri_key');
		if(!strlen($key)) {
			return null;
		}

		$open = (bool) Input::post('hofff_selectri_open');

		$unfolded = $this->getUnfolded();
		if($open) {
			$unfolded[] = $key;
		} else {
			$unfolded = array_diff($unfolded, [ $key ]);
		}
		$this->setUnfolded($unfolded);

		$response = [];
		$response['action']	= 'toggle';
		$response['key']	= $key;
		$response['open']	= $open;

		return $response;
	}

	/**
	 * @return array|null
	 */
	public function generateAjaxSearch() {
		if(!$this->getData()->isSearchable()) {
			return null;
		}

		$search = Input::post('hofff_selectri_search');
		if(!strlen($search)) {
			return null;
		}

		$nodes = $this->getData()->search($search, $this->getSearchLimit());
		$nodes = iterator_to_array($nodes);
		$nodes = array_filter($nodes, function(Node $node) {
			return $node->isSelectable();
		});

		$response = [];
		$response['action'] = 'search';
		$response['search'] = $search;
		if($nodes) {
			$response['result'] = ContaoUtil::renderTemplate('hofff_selectri_node_list', [
				'widget'	=> $this,
				'nodes'		=> $nodes,
			]);

		} else {
			$response['messages'][] = sprintf($GLOBALS['TL_LANG']['hofff_selectri']['search_empty'], $search);
		}

		return $response;
	}

	/**
	 * @param array $response
	 * @param array<Node> $nodes
	 * @param string|null $key
	 * @return void
	 */
	protected function renderLevels(array &$response, array $nodes, $key) {
		if(!$nodes) {
			return;
		}

		$content = ContaoUtil::renderTemplate('hofff_selectri_node_list', [
			'widget'	=> $this,
			'nodes'		=> $nodes,
			'children'	=> true,
		]);

		if($key === null) {
			$response['first'] = $content;
		} else {
			isset($response['start']) || $response['start'] = $key;
			$response['levels'][$key] = $content;
		}

		foreach($nodes as $node) {
			$node->isOpen() && $this->renderLevels(
				$response,
				iterator_to_array($node->getChildrenIterator()),
				$node->getKey()
			);
		}
	}

	/**
	 * @return Data
	 */
	public function getData() {
		return $this->data;
	}

	/**
	 * @param Data $data
	 * @return void
	 */
	public function setData(Data $data) {
		$this->data = $data;
	}

	/**
	 * @param array $attrs
	 * @throws SelectriException
	 * @return void
	 */
	protected function createDataFromAttributes(array $attrs) {
		if(isset($attrs['dataFactory'])) {
			$factory = $attrs['dataFactory'];
		} elseif(isset($attrs['data'])) {
			$factory = $attrs['data'];
		} else {
			return;
		}

		is_callable($factory) && $factory = call_user_func($factory, $this, $attrs);

		if(is_scalar($factory) && is_a($factory, DataFactory::class, true)) {
			$factory = new $factory;
			$factory->setParameters($attrs);

		} elseif(!$factory instanceof DataFactory) {
			throw new SelectriException('invalid selectri data factory configuration');
		}

		$data = $factory->createData($this);
		$this->setData($data);
	}

	/**
	 * @throws SelectriException
	 * @return void
	 */
	protected function checkData() {
		$data = $this->getData();
		if(!$data) {
			throw new SelectriException('no selectri data set');
		}
		$data->validate();
	}

	/**
	 * @return array<string, array>
	 */
	public function getValue() {
		return $this->varValue;
	}

	/**
	 * @param array<string, array> $value
	 * @return void
	 */
	public function setValue($value) {
		$this->varValue = $value;
	}

	/**
	 * @return array<Node>
	 */
	public function getSelectedNodes() {
		$selection = $this->getData()->getNodes(array_keys($this->getValue()));
		return iterator_to_array($selection);
	}

	/**
	 * @return array<Node>
	 */
	public function getSuggestedNodes() {
		if($this->isDisableSuggestions() || !$this->getData()->hasSuggestions()) {
			return [];
		}
		if($this->isSuggestOnlyEmpty() && $this->varValue) {
			return [];
		}

		$suggestions = $this->getData()->suggest($this->getSuggestLimit());

		return iterator_to_array($suggestions);
	}

	/**
	 * @return boolean
	 */
	public function isOpen() {
		return $this->isBrowsable() && $this->mandatory && !$this->varValue;
	}

	/**
	 * @return string
	 */
	public function getInputName() {
		return $this->name . '[selected][]';
	}

	/**
	 * @return string
	 */
	public function getAdditionalInputBaseName() {
		return $this->name . '[data]';
	}

	/**
	 * @return string
	 */
	public function getHeight() {
		if(!strlen($this->height)) {
			return 'auto';
		}
		if(ctype_digit($this->height)) {
			return $this->height . 'px';
		}
		return $this->height;
	}

	/**
	 * @param string $height
	 * @return void
	 */
	public function setHeight($height) {
		$this->height = $height;
	}

	/**
	 * @return string
	 */
	public function getSort() {
		return $this->sort;
	}

	/**
	 * @param string $sort
	 * @throws SelectriException
	 * @return void
	 */
	public function setSort($sort) {
		if($sort === 'tree') {
			throw new SelectriException('tree sortable not implemented');
		} elseif($sort === true || $sort === 'list') {
			$this->sort = 'list';
		} else {
			$this->sort = 'preorder';
		}
	}

	/**
	 * @return integer
	 */
	public function getMinSelected() {
		return min($this->min, $this->getMaxSelected());
	}

	/**
	 * @param integer $limit
	 * @return void
	 */
	public function setMinSelected($min) {
		$this->min = max(0, intval($min));
	}

	/**
	 * @return integer
	 */
	public function getMaxSelected() {
		return $this->max;
	}

	/**
	 * @param integer $limit
	 * @return void
	 */
	public function setMaxSelected($max) {
		$this->max = max(1, intval($max));
	}

	/**
	 * @return array
	 */
	public function getJSOptions() {
		return $this->jsOptions;
	}

	/**
	 * @param array $jsOptions
	 * @return void
	 */
	public function setJSOptions($jsOptions) {
		$this->jsOptions = (array) $jsOptions;
	}

	/**
	 * @return integer
	 */
	public function getSearchLimit() {
		return $this->searchLimit;
	}

	/**
	 * @param integer $limit
	 * @return void
	 */
	public function setSearchLimit($limit) {
		$this->searchLimit = max(1, intval($limit));
	}

	/**
	 * @return integer
	 */
	public function getSuggestLimit() {
		return $this->suggestLimit;
	}

	/**
	 * @param integer $limit
	 * @return void
	 */
	public function setSuggestLimit($limit) {
		$this->suggestLimit = max(1, intval($limit));
	}

	/**
	 * @return string
	 */
	public function getSuggestionsLabel() {
		return strlen($this->suggestionsLabel)
			? $this->suggestionsLabel
			: $GLOBALS['TL_LANG']['hofff_selectri']['suggestions'];
	}

	/**
	 * @param string $label
	 * @return void
	 */
	public function setSuggestionsLabel($label) {
		$this->suggestionsLabel = (string) $label;
	}

	/**
	 * @return boolean
	 */
	public function isBrowsable() {
		return $this->data->isBrowsable() && !$this->isDisableBrowsing();
	}

	/**
	 * @return boolean
	 */
	public function isDisableBrowsing() {
		return $this->disableBrowsing;
	}

	/**
	 * @param boolean $disable
	 * @return void
	 */
	public function setDisableBrowsing($disable) {
		$this->disableBrowsing = (bool) $disable;
	}

	/**
	 * @return boolean
	 */
	public function isSearchable() {
		return $this->data->isSearchable() && !$this->isDisableSearching();
	}

	/**
	 * @return boolean
	 */
	public function isDisableSearching() {
		return $this->disableSearching;
	}

	/**
	 * @param boolean $disable
	 * @return void
	 */
	public function setDisableSearching($disable) {
		$this->disableSearching = (bool) $disable;
	}

	/**
	 * @return boolean
	 */
	public function isDisableSuggestions() {
		return $this->disableSuggestions;
	}

	/**
	 * @param boolean $disable
	 * @return void
	 */
	public function setDisableSuggestions($disable) {
		$this->disableSuggestions = (bool) $disable;
	}

	/**
	 * @return boolean
	 */
	public function isSuggestOnlyEmpty() {
		return $this->suggestOnlyEmpty;
	}

	/**
	 * @param boolean $only
	 * @return void
	 */
	public function setSuggestOnlyEmpty($only) {
		$this->suggestOnlyEmpty = (bool) $only;
	}

	/**
	 * @return boolean
	 */
	public function isContentToggleable() {
		return $this->contentToggleable;
	}

	/**
	 * @param boolean $toggleable
	 * @return void
	 */
	public function setContentToggleable($toggleable) {
		$this->contentToggleable = (bool) $toggleable;
	}

	/**
	 * @return boolean
	 */
	public function isDataContainerDriven() {
		return strlen($this->table) && strlen($this->field);
	}

	/**
	 * @return string|null
	 */
	public function getDataContainerTable() {
		return $this->table;
	}

	/**
	 * @return string|null
	 */
	public function getDataContainerField() {
		return $this->field;
	}

	/**
	 * @return array
	 */
	public function getFieldDCA() {
		if(!$this->isDataContainerDriven()) {
			return [];
		}

		return $GLOBALS['TL_DCA'][$this->getDataContainerTable()]['fields'][$this->getDataContainerField()];
	}

	/**
	 * @return array
	 */
	public function getUnfolded() {
		if(!$this->isDataContainerDriven()) {
			return [];
		}

		$unfolded = (array) $this->Session->get($this->getSessionKey());
		$unfolded = array_keys($unfolded);
		$unfolded = array_map('strval', $unfolded);

		return $unfolded;
	}

	/**
	 * @param array $unfolded
	 * @return void
	 */
	public function setUnfolded(array $unfolded) {
		if(!$this->isDataContainerDriven()) {
			return;
		}

		$unfolded = array_values($unfolded);
		$unfolded = array_map('strval', $unfolded);
		$unfolded = array_flip($unfolded);

		$this->Session->set($this->getSessionKey(), $unfolded);
	}

	/**
	 * @return string
	 */
	public function getSessionKey() {
		return sprintf('hofff_selectri$%s$%s',
			$this->getDataContainerTable(),
			$this->getDataContainerField()
		);
	}

}
