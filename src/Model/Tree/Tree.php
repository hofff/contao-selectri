<?php

declare(strict_types=1);

namespace Hofff\Contao\Selectri\Model\Tree;

use function array_flip;
use function array_keys;
use function array_map;
use function count;

class Tree
{
    /**
     * A map of ordered identity maps mapping tree keys to their children lists
     *
     * @var array<string, array<string, string>>
     */
    public $children = [];

    /**
     * A map of tree keys mapping to their parent tree keys
     *
     * @var array<string, string>
     */
    public $parents = [];

    /**
     * A map of tree keys mapping to arbitrary data assoicated with this node
     *
     * @var array<string, array<string,mixed>>
     */
    public $nodes = [];

    /** @var string */
    private $rootValue;

    public function __construct(string $rootValue)
    {
        $this->rootValue = $rootValue;
    }

    public function getRootValue(): string
    {
        return $this->rootValue;
    }

    /**
     * Builds a parents map from this tree's children map
     *
     * @return array<string, string>
     */
    public function getParentsFromChildren(): array
    {
        $parents = [];

        foreach ($this->children as $parentKey => $children) {
            foreach (array_keys($children) as $key) {
                $parents[$key] = $parentKey;
            }
        }

        return $parents;
    }

    /**
     * Returns the given node IDs of the given tree in preorder,
     * optionally removing nested node IDs.
     *
     * Removes duplicates.
     *
     * @param list<string> $keys
     *
     * @return list<string>
     */
    public function getPreorder(array $keys, bool $unnest = false): array
    {
        if (! $keys) {
            return [];
        }

        $rootValue = $this->getRootValue();
        $keys      = array_flip(array_map('strval', $keys));
        $preorder  = [];

        if (isset($keys[$rootValue])) {
            $preorder[] = $rootValue;
            if ($unnest) {
                return $preorder;
            }
        }

        $this->getPreorderHelper($preorder, $keys, $unnest, $rootValue);

        return $preorder;
    }

    /**
     * @param list<string> $preorder
     * @param list<string> $keys
     */
    private function getPreorderHelper(array &$preorder, array $keys, bool $unnest, string $parentKey): void
    {
        if (! isset($this->children[$parentKey]) || ! count($this->children[$parentKey])) {
            return;
        }

        foreach (array_keys($this->children[$parentKey]) as $key) {
            if (isset($keys[$key])) {
                $preorder[] = $key;
                if ($unnest) {
                    continue;
                }
            }

            $this->getPreorderHelper($preorder, $keys, $unnest, $key);
        }
    }

    /**
     * Returns the descendants of each of the given node IDs of the given tree
     * in preorder, optionally adding the given node IDs themselves.
     * Duplicates are not removed, invalid and nested nodes are not removed. Use
     * getPreorder(..) with $unnest set to true before calling this method,
     * if this is the desired behavior.
     *
     * @param list<string> $keys
     *
     * @return list<string>
     */
    public function getDescendantsPreorder(array $keys, bool $andSelf = false): array
    {
        $preorder = [];

        foreach (array_map('strval', $keys) as $key) {
            $andSelf && $preorder[] = $key;
            $this->getDescendantsPreorderHelper($preorder, $key);
        }

        return $preorder;
    }

    /**
     * @param list<string> $preorder
     */
    private function getDescendantsPreorderHelper(array &$preorder, string $parentKey): void
    {
        if (! isset($this->children[$parentKey]) || ! count($this->children[$parentKey])) {
            return;
        }

        foreach (array_keys($this->children[$parentKey]) as $key) {
            $preorder[] = $key;
            $this->getDescendantsPreorderHelper($preorder, $key);
        }
    }
}
