<?php

declare(strict_types=1);

namespace Hofff\Contao\Selectri\Model;

use Iterator;

interface Node
{
    public function getKey(): string;

    /**
     * @return array<string,mixed>
     */
    public function getData(): array;

    public function getLabel(): string;

    public function getContent(): string;

    public function getAdditionalInputName(string $key): string;

    public function getIcon(): string;

    public function isSelectable(): bool;

    public function hasPath(): bool;

    /**
     * @return Iterator<Node>
     */
    public function getPathIterator(): Iterator;

    public function hasItems(): bool;

    /**
     * @return Iterator<Node>
     */
    public function getItemIterator(): Iterator;

    public function hasSelectableDescendants(): bool;

    public function isOpen(): bool;

    /**
     * @return Iterator<Node>
     */
    public function getChildrenIterator(): Iterator;
}
