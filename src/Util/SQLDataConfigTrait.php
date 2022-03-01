<?php

declare(strict_types=1);

namespace Hofff\Contao\Selectri\Util;

use function array_merge;
use function strlen;
use function strval;

trait SQLDataConfigTrait
{
    /** @var string */
    private $table;

    /** @var string */
    private $keyColumn;

    /** @var array<string> */
    private $columns = [];

    /** @var array<string> */
    private $searchColumns = [];

    /** @var string */
    private $conditionExpr;

    /** @var string */
    private $selectableExpr;

    /** @var string */
    private $orderByExpr;

    /** @var callable|null */
    private $labelCallback;

    /** @var callable */
    private $iconCallback;

    /** @var callable|null */
    private $contentCallback;

    public function getTable(): string
    {
        return $this->table;
    }

    public function setTable(string $table): void
    {
        $this->table = $table;
    }

    public function getKeyColumn(): string
    {
        return $this->keyColumn;
    }

    public function setKeyColumn(string $column): void
    {
        $this->keyColumn = $column;
    }

    /**
     * @return array<string>
     */
    public function getColumns(): array
    {
        return $this->columns;
    }

    /**
     * @param array<string>|null $columns
     */
    public function setColumns(array $columns): void
    {
        $this->columns = SQLUtil::getCleanedColumns($columns);
    }

    /**
     * @param array<string>|string $columns
     */
    public function addColumns(array $columns): void
    {
        $this->setColumns(array_merge($this->getColumns(), (array) $columns));
    }

    /**
     * @return array<string>
     */
    public function getSearchColumns(): array
    {
        return $this->searchColumns;
    }

    /**
     * @param array<string>|null $columns
     */
    public function setSearchColumns(array $columns): void
    {
        $this->searchColumns = SQLUtil::getCleanedColumns($columns);
    }

    /**
     * @param array<string>|string $columns
     */
    public function addSearchColumns(array $columns): void
    {
        $this->setSearchColumns(array_merge($this->getSearchColumns(), (array) $columns));
    }

    public function getConditionExpr(?string $clause = null): string
    {
        $expr   = $this->conditionExpr;
        $clause = (string) $clause;

        if ($expr !== '' && $clause !== '') {
            $expr = $clause . ' (' . $expr . ')';
        }

        return $expr;
    }

    /**
     * @param string $expr
     */
    public function setConditionExpr($expr): void
    {
        $this->conditionExpr = $expr;
    }

    /**
     * @param string $clause
     */
    public function getSelectableExpr($clause = null): string
    {
        $expr                                     = strval($this->selectableExpr);
        $clause                                   = strval($clause);
        strlen($expr) && strlen($clause) && $expr = $clause . ' (' . $expr . ')';

        return $expr;
    }

    /**
     * @param string $expr
     */
    public function setSelectableExpr($expr): void
    {
        $this->selectableExpr = $expr;
    }

    /**
     * @param string $clause
     */
    public function getOrderByExpr($clause = null): string
    {
        $expr                                     = (string) $this->orderByExpr;
        $clause                                   = (string) $clause;
        strlen($expr) && strlen($clause) && $expr = $clause . ' ' . $expr;

        return $expr;
    }

    /**
     * @param string $expr
     */
    public function setOrderByExpr($expr): void
    {
        $this->orderByExpr = strval($expr);
    }

    public function getLabelCallback(): callable
    {
        return $this->labelCallback;
    }

    /**
     * @param callable $callback
     */
    public function setLabelCallback($callback): void
    {
        $this->labelCallback = $callback;
    }

    public function getIconCallback(): callable
    {
        return $this->iconCallback;
    }

    /**
     * @param callable $callback
     */
    public function setIconCallback($callback): void
    {
        $this->iconCallback = $callback;
    }

    public function getContentCallback(): ?callable
    {
        return $this->contentCallback;
    }

    /**
     * @param callable|null $callback
     */
    public function setContentCallback($callback): void
    {
        $this->contentCallback = $callback;
    }
}
