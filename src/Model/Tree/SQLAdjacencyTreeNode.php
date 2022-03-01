<?php

declare(strict_types=1);

namespace Hofff\Contao\Selectri\Model\Tree;

use ArrayIterator;
use EmptyIterator;
use Hofff\Contao\Selectri\Model\Node;
use Iterator;
use stdClass;

use function array_keys;
use function array_reverse;
use function call_user_func;
use function key;
use function reset;

class SQLAdjacencyTreeNode implements Node
{
    /** @var SQLAdjacencyTreeData */
    protected $data;

    /** @var stdClass */
    protected $tree;

    /** @var string */
    protected $key;

    /** @var array<string,mixed> */
    protected $node;

    public function __construct(SQLAdjacencyTreeData $data, Tree $tree, string $key)
    {
        $this->data = $data;
        $this->tree = $tree;
        $this->key  = $key;
        $this->node = &$this->tree->nodes[$this->key];
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

        return call_user_func($callback, $this, $data, $config);
    }

    public function getContent(): string
    {
        $data     = $this->data;
        $config   = $data->getConfig();
        $callback = $config->getContentCallback();

        return $callback ? call_user_func($callback, $this, $data, $config) : '';
    }

    public function getAdditionalInputName(string $key): string
    {
        $name  = $this->data->getWidget()->getAdditionalInputBaseName();
        $name .= '[' . $this->getKey() . ']';
        $name .= '[' . $key . ']';

        return $name;
    }

    public function getIcon(): string
    {
        $data     = $this->data;
        $config   = $data->getConfig();
        $callback = $config->getIconCallback();

        return call_user_func($callback, $this, $data, $config);
    }

    public function isSelectable(): bool
    {
        if (! $this->node['_isSelectable']) {
            return false;
        }

        switch ($this->data->getConfig()->getSelectionMode()) {
            case SQLAdjacencyTreeDataConfig::SELECTION_MODE_LEAF:
                return ! $this->node['_hasChildren'];

                break;

            case SQLAdjacencyTreeDataConfig::SELECTION_MODE_INNER:
                return ! ! $this->node['_hasChildren'];

                break;

            default:
                return true;

                break;
        }
    }

    public function hasSelectableDescendants(): bool
    {
        if ($this->data->getConfig()->getSelectionMode() === SQLAdjacencyTreeDataConfig::SELECTION_MODE_INNER) {
            return $this->node['_hasGrandChildren'] === 1;
        }

        return $this->node['_hasChildren'] === 1;
    }

    public function isOpen(): bool
    {
        $children = $this->tree->children[$this->key];
        if (! $children) {
            return false;
        }

        reset($children);

        return isset($this->tree->nodes[key($children)]);
    }

    /** {@inheritDoc} */
    public function getChildrenIterator(): Iterator
    {
        if (! $this->isOpen()) {
            return new EmptyIterator();
        }

        $children = [];
        foreach (array_keys($this->tree->children[$this->key]) as $key) {
            $children[] = new self($this->data, $this->tree, $key);
        }

        return new ArrayIterator($children);
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
        return isset($this->tree->nodes[$this->tree->parents[$this->key]]);
    }

    /** {@inheritDoc} */
    public function getPathIterator(): Iterator
    {
        $key  = $this->key;
        $path = [];
        while ($this->tree->nodes[$key = $this->tree->parents[$key]]) {
            $path[] = new self($this->data, $this->tree, $key);
        }

        return new ArrayIterator(array_reverse($path));
    }

    /** {@inheritDoc} */
    public function getPathKeys(): array
    {
        $pathKeys = [];
        $parents  = &$this->tree->parents;
        $key      = $this->key;
        while (isset($parents[$key])) {
            $pathKeys[] = $key = $parents[$key];
        }

        return $pathKeys;
    }
}
