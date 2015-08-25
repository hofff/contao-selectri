<?php

namespace Hofff\Contao\Selectri\Util;

class SQLUtil {

	/**
	 * @param array<mixed> $columns
	 * @return array<string>
	 */
	public static function getCleanedColumns($columns) {
		return array_unique(array_values(array_filter(array_map('strval', (array) $columns))));
	}

	/**
	 * @param \Database $db
	 * @param string $table
	 * @param string $keyColumn
	 * @return LabelFormatter
	 */
	public static function createLabelFormatter(\Database $db, $table, $keyColumn) {
		$fields = array();
		if($db->fieldExists('name', $table)) {
			$fields[] = 'name';
		} elseif($db->fieldExists('title', $table)) {
			$fields[] = 'title';
		}
		$fields[] = $keyColumn;

		$format = '';
		foreach($fields as $field) {
			$format .= $field == $keyColumn ? ' (ID %s)' : ', %s';
		}
		$format = ltrim($format, ', ');

		return new LabelFormatter($format, $fields);
	}

	/**
	 * @param array $args
	 * @return string
	 */
	public static function generateWildcards(array $args) {
		return rtrim(str_repeat('?,', count($args)), ',');
	}

}
