<?php

declare(strict_types=1);

namespace Hofff\Contao\Selectri\Util;

use Contao\BackendTemplate;
use Contao\FrontendTemplate;
use Contao\Template;

use function assert;

class ContaoUtil
{
    /**
     * @param string                   $tpl
     * @param array<string,mixed>|null $data
     */
    public static function renderTemplate($tpl, ?array $data = null): string
    {
        $class    = TL_MODE === 'FE' ? FrontendTemplate::class : BackendTemplate::class;
        $template = new $class($tpl);
        assert($template instanceof Template);
        $data && $template->setData($data);

        return $template->parse();
    }
}
