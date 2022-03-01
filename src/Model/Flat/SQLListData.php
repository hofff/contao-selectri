<?php

declare(strict_types=1);

namespace Hofff\Contao\Selectri\Model\Flat;

use ArrayIterator;
use Contao\Database;
use EmptyIterator;
use Hofff\Contao\Selectri\Exception\SelectriException;
use Hofff\Contao\Selectri\Model\AbstractData;
use Hofff\Contao\Selectri\Model\Node;
use Hofff\Contao\Selectri\Util\SearchUtil;
use Hofff\Contao\Selectri\Util\SQLUtil;
use Hofff\Contao\Selectri\Widget;
use Iterator;
use Throwable;

use function array_fill;
use function array_flip;
use function array_intersect_key;
use function array_keys;
use function array_merge;
use function array_replace;
use function array_unique;
use function array_values;
use function count;
use function implode;
use function in_array;
use function iterator_to_array;
use function sprintf;
use function strlen;
use function vsprintf;

class SQLListData extends AbstractData
{
    /** @var Database */
    protected $database;

    /** @var SQLListDataConfig */
    protected $cfg;

    public function __construct(Widget $widget, Database $database, SQLListDataConfig $cfg)
    {
        parent::__construct($widget);

        $this->database = $database;
        $this->cfg      = $cfg;
    }

    public function getConfig(): SQLListDataConfig
    {
        return $this->cfg;
    }

    public function validate(): void
    {
        if (! strlen($this->cfg->getTable())) {
            throw new SelectriException('invalid config: no table given');
        }

        try {
            $sql = $this->buildNodeQuery();
            $sql = sprintf($sql, '1');
            $this->database->prepare($sql)->limit(1)->execute();
        } catch (Throwable $e) {
            throw new SelectriException('invalid table configuration: ' . $e->getMessage());
        }
    }

    /** {@inheritDoc}*/
    public function getNodes(array $keys, bool $selectableOnly = true): Iterator
    {
        if (! $keys) {
            return new EmptyIterator();
        }

        $sql       = $this->buildNodeQuery();
        $condition = sprintf(
            '%s IN (%s) %s',
            $this->cfg->getKeyColumn(),
            SQLUtil::generateWildcards($keys),
            $this->cfg->getSelectableExpr('AND')
        );
        $sql       = sprintf($sql, $condition);
        $result    = $this->database->prepare($sql)->execute(array_values($keys));

        $nodes = [];
        while ($result->next()) {
            $nodes[(string) $result->_key] = $this->createNode($result->row());
        }

        // maintain key order
        $nodes = array_replace(array_intersect_key(array_flip($keys), $nodes), $nodes);

        return new ArrayIterator($nodes);
    }

    /** {@inheritDoc}*/
    public function filter(array $keys): array
    {
        return array_keys(iterator_to_array($this->getNodes($keys), true));
    }

    public function isBrowsable(): bool
    {
        return true;
    }

    /** @return Iterator<Node> */
    public function browseFrom(?string $key = null): Iterator
    {
        $sql    = $this->buildNodeQuery();
        $sql    = sprintf($sql, 1);
        $result = $this->database->prepare($sql)->execute();

        $nodes = [];
        while ($result->next()) {
            $nodes[] = $this->createNode($result->row());
        }

        return new ArrayIterator([new ArrayIterator($nodes), 0]);
    }

    /** @return Iterator<Node> */
    public function browseTo(string $key): Iterator
    {
        return new EmptyIterator();
    }

    public function isSearchable(): bool
    {
        return true;
    }

    /** @return Iterator<Node> */
    public function search(string $query, int $limit, int $offset = 0): Iterator
    {
        $keywords = SearchUtil::parseKeywords($query);
        if (! $keywords) {
            return new EmptyIterator();
        }

        $sql       = $this->buildNodeQuery();
        $columnCnt = 0;
        $expr      = $this->buildSearchExpr(count($keywords), $columnCnt);
        $sql       = sprintf($sql, $expr);

        $params = [];
        foreach ($keywords as $word) {
            $params[] = array_fill(0, $columnCnt, $word);
        }

        $params = array_merge(...$params);
        $keys   = $this->database->prepare($sql)->limit($limit, $offset)->execute($params)->fetchEach('_key');

        return $this->getNodes($keys);
    }

    /**
     * @param array<string,mixed> $node
     */
    protected function createNode(array $node): SQLListNode
    {
        return new SQLListNode($this, $node);
    }

    protected function buildSelectExpr(): string
    {
        $columns   = $this->cfg->getColumns();
        $columns[] = $this->cfg->getKeyColumn();

        return implode(', ', array_unique($columns));
    }

    protected function buildSelectableExpr(): string
    {
        return $this->cfg->getSelectableExpr() ? $this->cfg->getSelectableExpr() : '1';
    }

    protected function buildNodeQuery(): string
    {
        $query = <<<EOT
SELECT		%s AS _key,
			(%s) AS _isSelectable,
			%s

FROM		%s

WHERE		(%%s)
%s
%s
EOT;

        $params = [];
        // select
        $params[] = $this->cfg->getKeyColumn();
        $params[] = $this->buildSelectableExpr();
        $params[] = $this->buildSelectExpr();
        // from
        $params[] = $this->cfg->getTable();
        // where
        $params[] = $this->cfg->getConditionExpr('AND');
        // order
        $params[] = $this->cfg->getOrderByExpr('ORDER BY');

        $query = vsprintf($query, $params);

        return $query;
    }

    protected function buildSearchExpr(int $keywordCnt, int &$columnCnt): string
    {
        $columns                                     = $this->cfg->getSearchColumns();
        $keyColumn                                   = $this->cfg->getKeyColumn();
        in_array($keyColumn, $columns) || $columns[] = $keyColumn;

        $condition = [];
        foreach ($columns as $column) {
            $condition[] = $column . ' LIKE CONCAT(\'%\', CONCAT(?, \'%\'))';
        }

        $condition = implode(' OR ', $condition);

        $columnCnt = count($columns);

        return '(' . implode(') AND (', array_fill(0, $keywordCnt, $condition)) . ')';
    }
}
