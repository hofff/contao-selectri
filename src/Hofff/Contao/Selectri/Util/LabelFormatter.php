<?php

namespace Hofff\Contao\Selectri\Util;

use Hofff\Contao\Selectri\Model\Node;

class LabelFormatter {

	/**
	 * @var string
	 */
	private $format;

	/**
	 * @var array<string>
	 */
	private $fields;

	/**
	 * @var boolean
	 */
	private $htmlOutput;

	/**
	 * @param string $format
	 * @param array $fields
	 * @param boolean $htmlOutput
	 */
	public function __construct($format = null, array $fields = null, $htmlOutput = false) {
		$this->setFormat($format);
		$this->setFields((array) $fields);
		$this->setHTMLOutput($htmlOutput);
	}

	/**
	 * @return string
	 */
	public function getFormat() {
		return $this->format;
	}

	/**
	 * @param string $format
	 * @return void
	 */
	public function setFormat($format) {
		$this->format = $format;
	}

	/**
	 * @return array<string>
	 */
	public function getFields() {
		return $this->fields;
	}

	/**
	 * @param array<string> $fields
	 * @return void
	 */
	public function setFields(array $fields) {
		$this->fields = $fields;
	}

	/**
	 * @return boolean
	 */
	public function isHTMLOutput() {
		return $this->htmlOutput;
	}

	/**
	 * @param boolean $htmlOutput
	 * @return void
	 */
	public function setHTMLOutput($htmlOutput) {
		$this->htmlOutput = (bool) $htmlOutput;
	}

	/**
	 * @return callable
	 */
	public function getCallback() {
		return array($this, 'format');
	}

	/**
	 * @param Node $node
	 * @return string
	 */
	public function format(Node $node) {
		$data = $node->getData();
		$fields = $this->getFields();
		foreach($fields as &$field) {
			$field = $data[$field];
		}
		$label = vsprintf($this->getFormat(), $fields);
		return $this->isHTMLOutput() ? $label : specialchars($label);
	}

}
