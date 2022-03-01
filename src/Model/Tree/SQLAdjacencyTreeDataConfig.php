<?php

declare(strict_types=1);

namespace Hofff\Contao\Selectri\Model\Tree;

use Hofff\Contao\Selectri\Util\SQLDataConfigTrait;

use function array_values;
use function strval;

class SQLAdjacencyTreeDataConfig
{
    use SQLDataConfigTrait;

    public const SELECTION_MODE_INNER = 'inner';

    public const SELECTION_MODE_LEAF = 'leaf';

    public const SELECTION_MODE_ALL = 'all';

    /** @var string */
    private $parentKeyColumn;

    /** @var mixed */
    private $rootValue;

    /** @var array<string> */
    private $roots;

    /** @var string */
    private $selectionMode;

    public function __construct()
    {
    }

    public function getParentKeyColumn(): string
    {
        return $this->parentKeyColumn;
    }

    /**
     * @param string $column
     */
    public function setParentKeyColumn($column): void
    {
        $this->parentKeyColumn = strval($column);
    }

    /**
     * @return mixed
     */
    public function getRootValue()
    {
        return $this->rootValue;
    }

    /**
     * @param mixed $value
     */
    public function setRootValue($value): void
    {
        $this->rootValue = $value;
    }

    /**
     * @return array<string>
     */
    public function getRoots(): array
    {
        return $this->roots ? $this->roots : [$this->getRootValue()];
    }

    /**
     * @param array<string>|null $roots
     */
    public function setRoots($roots): void
    {
        $this->roots = array_values((array) $roots);
    }

    public function getSelectionMode(): string
    {
        return $this->selectionMode;
    }

    /**
     * @param string $mode
     */
    public function setSelectionMode($mode): void
    {
        switch ($mode) {
            case self::SELECTION_MODE_LEAF:
                break;
            case self::SELECTION_MODE_INNER:
                break;
            default:
                $mode = self::SELECTION_MODE_ALL;
                break;
        }

        $this->selectionMode = $mode;
    }
}
