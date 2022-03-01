<?php

declare(strict_types=1);

namespace Hofff\Contao\Selectri\Model\Flat;

use EmptyIterator;
use Hofff\Contao\Selectri\Model\Node;
use Iterator;

class SQLListNode implements Node
{
    /** @var SQLListData */
    protected $data;

    /** @var string */
    protected $key;

    /** @var array<string,mixed> */
    protected $node;

    /**
     * @param array<string,mixed> $node
     */
    public function __construct(SQLListData $data, array $node)
    {
        $this->data = $data;
        $this->key  = $node['_key'];
        $this->node = $node;
    }

    public function getKey(): string
    {
        return $this->key;
    }

    /** {@inheritDoc} */
    public function getData(): array
    {
        return $this->node;
    }

    public function getLabel(): string
    {
        $data     = $this->data;
        $config   = $data->getConfig();
        $callback = $config->getLabelCallback();

        return $callback($this, $data, $config);
    }

    public function getContent(): string
    {
        $data     = $this->data;
        $config   = $data->getConfig();
        $callback = $config->getContentCallback();

        return $callback ? $callback($this, $data, $config) : '';
    }

    public function getIcon(): string
    {
        $data     = $this->data;
        $config   = $data->getConfig();
        $callback = $config->getIconCallback();

        return $callback($this, $data, $config);
    }

    public function getAdditionalInputName(string $key): string
    {
        $name  = $this->data->getWidget()->getAdditionalInputBaseName();
        $name .= '[' . $this->getKey() . ']';
        $name .= '[' . $key . ']';

        return $name;
    }

    public function isSelectable(): bool
    {
        return $this->node['_isSelectable'] ?? false;
    }

    public function hasSelectableDescendants(): bool
    {
        return false;
    }

    public function isOpen(): bool
    {
        return false;
    }

    /** {@inheritDoc} */
    public function getChildrenIterator(): Iterator
    {
        return new EmptyIterator();
    }

    public function hasItems(): bool
    {
        return false;
    }

    /** {@inheritDoc} */
    public function getItemIterator(): Iterator
    {
        return new EmptyIterator();
    }

    public function hasPath(): bool
    {
        return false;
    }

    /** {@inheritDoc} */
    public function getPathIterator(): Iterator
    {
        return new EmptyIterator();
    }
}
