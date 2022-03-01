<?php

declare(strict_types=1);

namespace Hofff\Contao\Selectri\Model;

use EmptyIterator;
use Hofff\Contao\Selectri\Widget;
use Iterator;

abstract class AbstractData implements Data
{
    /** @var Widget */
    private $widget;

    public function __construct(Widget $widget)
    {
        $this->widget = $widget;
    }

    public function getWidget(): Widget
    {
        return $this->widget;
    }

    public function isBrowsable(): bool
    {
        return false;
    }

    /** {@inheritDoc} */
    public function browseFrom(?string $key = null): Iterator
    {
        return new EmptyIterator();
    }

    /** {@inheritDoc} */
    public function browseTo(string $key): Iterator
    {
        return new EmptyIterator();
    }

    public function isSearchable(): bool
    {
        return false;
    }

    /** {@inheritDoc} */
    public function search(string $query, int $limit, int $offset = 0): Iterator
    {
        return new EmptyIterator();
    }

    public function hasSuggestions(): bool
    {
        return false;
    }

    /** {@inheritDoc} */
    public function suggest(int $limit, int $offset = 0): Iterator
    {
        return new EmptyIterator();
    }
}
