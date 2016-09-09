<?php

namespace Hofff\Contao\Selectri\Util;

class SearchUtil {

	/**
	 * @param string $query
	 * @return array<string>
	 */
	public static function parseKeywords($query) {
		if(defined('PREG_BAD_UTF8_OFFSET')) {
			return preg_split('/[^\pL\pN]+/iu', $query, null, PREG_SPLIT_NO_EMPTY);
// 			return preg_split('/[^\pL\pN]+(?:[\pL\pN][^\pL\pN]+)?/iu', $search, null, PREG_SPLIT_NO_EMPTY);
		} else {
			return preg_split('/[^\w]+/i', $query, null, PREG_SPLIT_NO_EMPTY);
// 			return preg_split('/(?:^|[^\w]+)(?:[\w](?:$|[^\w]+))*/i', $search, null, PREG_SPLIT_NO_EMPTY);
		}
	}

}
