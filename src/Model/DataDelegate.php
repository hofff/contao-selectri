<?php

declare(strict_types=1);

namespace Hofff\Contao\Selectri\Model;

use Hofff\Contao\Selectri\Widget;
use Iterator;

abstract class DataDelegate implements Data
{
    /** @var Data */
    private $delegate;

    protected function __construct(?Data $delegate = null)
    {
        $this->delegate = $delegate;
    }

    public function getDelegate(): Data
    {
        return $this->delegate;
    }

    protected function setDelegate(Data $delegate): void
    {
        $this->delegate = $delegate;
    }

    public function getWidget(): Widget
    {
        return $this->delegate->getWidget();
    }

    public function validate(): void
    {
        $this->delegate->validate();
    }

    /** {@inheritDoc}*/
    public function getNodes(array $keys, bool $selectableOnly = true): Iterator
    {
        return $this->delegate->getNodes($keys, $selectableOnly);
    }

    /** {@inheritDoc}*/
    public function filter(array $keys): array
    {
        return $this->delegate->filter($keys);
    }

    public function isBrowsable(): bool
    {
        return $this->delegate->isBrowsable();
    }

    /** @return Iterator<Node> */
    public function browseFrom(?string $key = null): Iterator
    {
        return $this->delegate->browseFrom($key);
    }

    /** @return Iterator<Node> */
    public function browseTo(string $key): Iterator
    {
        return $this->delegate->browseTo($key);
    }

    public function isSearchable(): bool
    {
        return $this->delegate->isSearchable();
    }

    /** {@inheritDoc} */
    public function search(string $query, int $limit, int $offset = 0): Iterator
    {
        return $this->delegate->search($query, $limit, $offset);
    }

    public function hasSuggestions(): bool
    {
        return $this->delegate->hasSuggestions();
    }

    /** {@inheritDoc} */
    public function suggest(int $limit, int $offset = 0): Iterator
    {
        return $this->delegate->suggest($limit, $offset);
    }
}
