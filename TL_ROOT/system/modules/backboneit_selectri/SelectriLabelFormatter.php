<?php

class SelectriLabelFormatter {

	public static function create($format, array $fields) {
		$clazz = get_called_class();
		$formatter = new $clazz();
		$formatter->setFormat($format);
		$formatter->setFields($fields);
		return $formatter;
	}

	private $format;

	private $fields;

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

	public function getCallback() {
		return array($this, 'format');
	}

	public function format(array $data) {
		$fields = $this->getFields();
		foreach($fields as &$field) {
			$field = $data[$field];
		}
		return vsprintf($this->getFormat(), $fields);
	}

}
