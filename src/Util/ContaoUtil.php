<?php

namespace Hofff\Contao\Selectri\Util;

use Contao\BackendTemplate;
use Contao\FrontendTemplate;
use Contao\Template;

class ContaoUtil {

	/**
	 * @param string $tpl
	 * @param array $data
	 * @return string
	 */
	public static function renderTemplate($tpl, array $data = null) {
		$class = TL_MODE == 'FE' ? FrontendTemplate::class : BackendTemplate::class;
		/* @var $tpl Template */
		$tpl = new $class($tpl);
		$data && $tpl->setData($data);
		return $tpl->parse();
	}

}
