<?php

class SelectriLabelFormatter {

	public static function create($format, array $fields, $htmlOutput = false) {
		$clazz = get_called_class();
		$formatter = new $clazz();
		$formatter->setFormat($format);
		$formatter->setFields($fields);
		$formatter->setHTMLOutput($htmlOutput);
		return $formatter;
	}

	private $format;

	private $fields;

	private $htmlOutput;

	public function __construct() {
	}

	public function getFormat() {
		return $this->format;
	}

	public function setFormat($format) {
		$this->format = $format;
		return $this;
	}

	public function getFields() {
		return $this->fields;
	}

	public function setFields(array $fields) {
		$this->fields = $fields;
		return $this;
	}

	public function isHTMLOutput() {
		return $this->htmlOutput;
	}

	public function setHTMLOutput($htmlOutput) {
		$this->htmlOutput = (bool) $htmlOutput;
	}

	public function getCallback() {
		return array($this, 'format');
	}

	public function format(array $data) {
		$fields = $this->getFields();
		foreach($fields as &$field) {
			$field = $data[$field];
		}
		$label = vsprintf($this->getFormat(), $fields);
		return $this->isHTMLOutput() ? $label : specialchars($label);
	}

}
