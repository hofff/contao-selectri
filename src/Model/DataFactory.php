<?php

declare(strict_types=1);

namespace Hofff\Contao\Selectri\Model;

use Hofff\Contao\Selectri\Widget;

interface DataFactory
{
    /**
     * @param array<string,mixed> $params Configuration parameters (usally the eval array of
     *      the DCA field the widget using this factory)
     */
    public function setParameters(array $params): void;

    /**
     * @param Widget $widget The widget the created data instance will belong to
     *
     * @return Data A new data instance
     */
    public function createData(?Widget $widget = null): Data;
}
