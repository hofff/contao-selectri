<?php

declare(strict_types=1);

namespace Hofff\Contao\Selectri\Model\Tree;

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

use function array_diff;
use function array_fill;
use function array_flip;
use function array_intersect;
use function array_keys;
use function array_map;
use function array_merge;
use function array_slice;
use function array_unique;
use function call_user_func_array;
use function count;
use function implode;
use function in_array;
use function sprintf;
use function strlen;
use function strval;
use function vsprintf;

use const PHP_EOL;
use const PHP_INT_MAX;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class SQLAdjacencyTreeData extends AbstractData
{
    /** @var Database */
    protected $database;

    /** @var SQLAdjacencyTreeDataConfig */
    protected $cfg;

    /** @var string */
    private $nodeQuery;

    public function __construct(Widget $widget, Database $database, SQLAdjacencyTreeDataConfig $cfg)
    {
        parent::__construct($widget);

        $this->database = $database;
        $this->cfg      = $cfg;
    }

    public function getConfig(): SQLAdjacencyTreeDataConfig
    {
        return $this->cfg;
    }

    public function validate(): void
    {
        if (! strlen($this->cfg->getTable())) {
            throw new SelectriException('invalid config: no table given');
        }

        try {
            $this->fetchTreeNodes([$this->cfg->getRootValue()], true, true, 1);
        } catch (Throwable $e) {
            throw new SelectriException('invalid tree table configuration: ' . $e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @{@inheritDoc}
     */
    public function getNodes(array $keys, bool $selectableOnly = true): Iterator
    {
        $children = null;
        $keys     = $this->filterKeys($keys, $children);
        if (! $keys) {
            return new EmptyIterator();
        }

        $tree           = new Tree($this->cfg->getRootValue());
        $tree->children = $children;
        $tree->parents  = $tree->getParentsFromChildren();
        $tree->nodes    = $this->fetchTreeNodes(array_keys($tree->parents));

        $nodes = [];
        foreach ($keys as $key) {
            $nodes[] = $this->createNode($tree, $key);
        }

        return new ArrayIterator($nodes);
    }

    /** {@inheritDoc}*/
    public function filter(array $keys): array
    {
        return $this->filterKeys($keys);
    }

    public function isBrowsable(): bool
    {
        return true;
    }

    /**
     * @return Iterator<Node>
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function browseFrom(?string $key = null): Iterator
    {
        $roots = $this->cfg->getRoots();
        if (! $roots) {
            throw new SelectriException('No roots configured');
        }

        $startKey = $key;
        $tree     = new Tree($this->cfg->getRootValue());

        $rootValue = $tree->getRootValue();
        $endpoints = $roots;

        // clean start
        if ($startKey === null) {
            $rootStart = true;
        } else {
            $startKey                             = strval($startKey);
            $startKey === $rootValue ? $rootStart = true : $endpoints[] = $startKey;
        }

        $tree->children = $this->fetchAncestorOrSelfTree($endpoints);

        // prepare roots
        if ($rootStart) {
            $roots = $tree->getPreorder($roots, true);
            if (! $roots) {
                return new EmptyIterator();
            }

            if ($roots[0] === $rootValue) { // implies count($roots) == 1 because unnested
                $tree->nodes = $this->fetchTreeNodes([$rootValue], true, true);
            } else {
                $tree->nodes = $this->fetchTreeNodes($roots);
            }
        } else {
            // filter start
            if (! in_array($startKey, $tree->getDescendantsPreorder($roots, true))) {
                return null;
            }

            $this->fetchLevels($tree, [$startKey]);
        }

        $first = array_keys((array) $tree->nodes);
        if (! $first) {
            return null;
        }

        $tree->parents = $tree->getParentsFromChildren();
        $this->addUnfolded($tree, $first);

        $first = $this->createFirstLevelNodes($tree, $first);

        return [new ArrayIterator($first), $rootStart ? null : $startKey];
    }

    /**
     * @return Iterator<Node>
     */
    public function browseTo(string $key): Iterator
    {
        $roots = $this->cfg->getRoots();
        if (! $roots) {
            throw new SelectriException('No roots configured');
        }

        $toKey = $key;

        $endpoints   = $roots;
        $endpoints[] = $toKey;

        $tree           = new Tree($this->cfg->getRootValue());
        $tree->children = $this->fetchAncestorOrSelfTree($endpoints);
        $tree->parents  = $tree->getParentsFromChildren();

        // prepare roots
        $roots     = $tree->getPreorder($roots, true);
        $rootValue = $tree->getRootValue();
        if ($roots[0] === $rootValue) { // implies count($roots) == 1 because unnested
            $tree->nodes = $this->fetchTreeNodes([$rootValue], true, true);
        } else {
            $tree->nodes = $this->fetchTreeNodes($roots);
        }

        $first = array_keys($tree->nodes);

        // fetch levels along the path
        if (! isset($tree->nodes[$toKey])) {
            $node       = $this->createNode($tree, $toKey);
            $pathKeys   = $node->getPathKeys();
            $rootInPath = false;
            foreach ($pathKeys as $i => $key) {
                if (isset($tree->nodes[$key])) {
                    $rootInPath = true;
                    $pathKeys   = array_slice($pathKeys, 0, $i + 1);
                    break;
                }
            }

            if (! $rootInPath) {
                throw new SelectriException(sprintf('Node "%s" not reachable from configured roots', $toKey));
            }

            $this->fetchLevels($tree, $pathKeys);
        }

        $first = $this->createFirstLevelNodes($tree, $first);

        return new ArrayIterator($first);
    }

    public function isSearchable(): bool
    {
        return true;
    }

    /** {@inheritDoc} */
    public function search(string $query, int $limit, int $offset = 0): Iterator
    {
        $keywords = SearchUtil::parseKeywords($query);
        if (! $keywords) {
            return new EmptyIterator();
        }

        $sql       = $this->buildSearchQuery();
        $columnCnt = 0;
        $expr      = $this->buildSearchExpr(count($keywords), $columnCnt);
        $sql       = sprintf($sql, $expr);

        $params = [];
        foreach ($keywords as $word) {
            $params[] = array_fill(0, $columnCnt, $word);
        }

        $params = call_user_func_array('array_merge', $params);
        $keys   = $this->database->prepare($sql)->limit($limit, $offset)->execute($params)->fetchEach('_key');

        return $this->getNodes($keys);
    }

    protected function createNode(Tree $tree, string $key): SQLAdjacencyTreeNode
    {
        return new SQLAdjacencyTreeNode($this, $tree, $key);
    }

    /**
     * @param array<string>                        $keys
     * @param array<string, array<string, string>> $children
     *
     * @return array<string>
     */
    protected function filterKeys(array $keys, ?array &$children = null): array
    {
        $nodes = [];
        foreach ($this->fetchTreeNodes($keys) as $key => $node) {
            if (! $node['_isSelectable']) {
                continue;
            }

            $nodes[] = $key;
        }

        if (! $nodes) {
            return [];
        }

        $keys = array_intersect($keys, $nodes);

        $roots          = $this->cfg->getRoots();
        $tree           = new Tree($this->cfg->getRootValue());
        $tree->children = $this->fetchAncestorOrSelfTree(array_merge($roots, $keys));
        $descendants    = $tree->getDescendantsPreorder($roots, true);
        $keys           = array_intersect($keys, $descendants);

        $children = $tree->children;

        return $keys;
    }

    protected function buildNodeSelectExpr(): string
    {
        $columns   = $this->cfg->getColumns();
        $columns[] = $this->cfg->getKeyColumn();
        $columns[] = $this->cfg->getParentKeyColumn();

        return implode(', ', array_unique($columns));
    }

    protected function buildSelectableExpr(): string
    {
        return $this->cfg->getSelectableExpr() ? $this->cfg->getSelectableExpr() : '1';
    }

    protected function buildTreeNodeQuery(): string
    {
        if ($this->nodeQuery) {
            return $this->nodeQuery;
        }

        $query = <<<EOT
SELECT
	%s AS _key,
	%s AS _parentKey,
	descendants._hasChildren AS _hasChildren,
	descendants._hasGrandChildren AS _hasGrandChildren,
	(%s) AS _isSelectable,
	%s
FROM
	%s
	AS tree
JOIN
	(
		SELECT
			%s AS _key,
			COUNT(child1._key) != 0 AS _hasChildren,
			COUNT(grandchild1._key) != 0 AS _hasGrandChildren
		FROM
			%s
			AS tree1
		LEFT JOIN
			(SELECT %s AS _key, %s AS _parentKey FROM %s %s)
			AS child1
			ON child1._parentKey = tree1.%s
		LEFT JOIN
			(SELECT %s AS _key, %s AS _parentKey FROM %s %s)
			AS grandchild1
			ON grandchild1._parentKey = child1._key
		WHERE
			%%s IN (%%s)
			%s
		GROUP BY
			%s
		%s
	)
	AS descendants
	ON descendants._key = tree.%s
EOT;

        $params = [];
        // select
        $params[] = $this->cfg->getKeyColumn();
        $params[] = $this->cfg->getParentKeyColumn();
        $params[] = $this->buildSelectableExpr();
        $params[] = $this->buildNodeSelectExpr();
        // from
        $params[] = $this->cfg->getTable();

        // select
        $params[] = $this->cfg->getKeyColumn();
        // from
        $params[] = $this->cfg->getTable();
        // child join
        $params[] = $this->cfg->getKeyColumn();
        $params[] = $this->cfg->getParentKeyColumn();
        $params[] = $this->cfg->getTable();
        $params[] = $this->cfg->getConditionExpr('WHERE');
        $params[] = $this->cfg->getKeyColumn();
        // grandchild join
        $params[] = $this->cfg->getKeyColumn();
        $params[] = $this->cfg->getParentKeyColumn();
        $params[] = $this->cfg->getTable();
        $params[] = $this->cfg->getConditionExpr('WHERE');
        // where
        $params[] = $this->cfg->getConditionExpr('AND');
        // group by
        $params[] = $this->cfg->getKeyColumn();
        // having
        $params[] = $this->cfg->getSelectionMode() === SQLAdjacencyTreeDataConfig::SELECTION_MODE_INNER
            ? 'HAVING _hasChildren'
            : '';

        // join
        $params[] = $this->cfg->getKeyColumn();

        $query = vsprintf($query, $params);

        return $this->nodeQuery = $query;
    }

    /**
     * @param list<string> $keys
     *
     * @return array<string, array<string,mixed>>
     */
    protected function fetchTreeNodes(
        ?array $keys = null,
        bool $children = false,
        bool $order = false,
        int $limit = PHP_INT_MAX
    ): array {
        if (! $keys) {
            return [];
        }

        $refColumn        = $children ? $this->cfg->getParentKeyColumn() : $this->cfg->getKeyColumn();
        $query            = sprintf($this->buildTreeNodeQuery(), $refColumn, SQLUtil::generateWildcards($keys));
        $order && $query .= PHP_EOL . $this->cfg->getOrderByExpr('ORDER BY');

        $result = $this->database->prepare($query)->limit($limit)->execute($keys);

        $nodes = [];
        while ($result->next()) {
            $nodes[strval($result->_key)] = $result->row();
        }

        return $nodes;
    }

    /**
     * @param list<string> $parentKeys
     */
    protected function fetchLevels(Tree $tree, array $parentKeys): void
    {
        if (! $parentKeys) {
            return;
        }

        $nodes = $this->fetchTreeNodes($parentKeys, true, true);

        // add fetched node data and remove existing children arrays for fetched nodes (to maintain order)
        foreach ($nodes as $key => $node) {
            $tree->nodes[$key] = $node;
            unset($tree->children[strval($node['_parentKey'])]);
        }

        // insert nodes into tree
        foreach ($nodes as $key => $node) {
            $parentKey                        = strval($node['_parentKey']);
            $tree->children[$parentKey][$key] = true;
            $tree->parents[$key]              = $parentKey;
        }
    }

    /**
     * @param list<string> $roots
     */
    protected function addUnfolded(Tree $tree, array $roots): void
    {
        $unfolded = $this->getWidget()->getUnfolded();
        if (! $unfolded) {
            return;
        }

        $nodes    = $this->fetchTreeNodes($unfolded);
        $unfolded = array_keys($nodes);

        // clean out inexistant values to avoid longterm leaking...
        $this->getWidget()->setUnfolded($unfolded);

        if (! $nodes) {
            return;
        }

        $unfoldedTree = new Tree($this->cfg->getRootValue());
        foreach ($nodes as $key => $node) {
            $unfoldedTree->children[(string) $node['_parentKey']][$key] = true;
        }

        $unfolded = $unfoldedTree->getDescendantsPreorder(array_intersect($roots, $unfolded), true);
        $this->fetchLevels($tree, $unfolded);
    }

    /**
     * @param array<string> $keys
     *
     * @return array<SQLAdjacencyTreeNode>
     */
    protected function createFirstLevelNodes(Tree $tree, array $keys): array
    {
        $first = [];
        foreach ($keys as $key) {
            $first[] = $this->createNode($tree, $key);
        }

        // fetch path nodes of first level
        $pathKeys = [];
        foreach ($first as $node) {
            foreach ($node->getPathKeys() as $key) {
                $pathKeys[$key] = $key;
            }
        }

        unset($pathKeys[$tree->getRootValue()]);

        foreach ($this->fetchTreeNodes($pathKeys) as $key => $node) {
            $tree->nodes[$key] = $node;
        }

        return $first;
    }

    protected function buildSearchQuery(): string
    {
        $query = <<<EOT
SELECT		%s AS _key
FROM		%s AS tree

LEFT JOIN	( SELECT %s AS _key, %s AS _parentKey FROM %s %s
			) AS child ON child._parentKey = tree.%s

WHERE		(%%s)
%s
%s
GROUP BY	%s
EOT;

        $params = [];
        // select
        $params[] = $this->cfg->getKeyColumn();
        // from
        $params[] = $this->cfg->getTable();
        // child join
        $params[] = $this->cfg->getKeyColumn();
        $params[] = $this->cfg->getParentKeyColumn();
        $params[] = $this->cfg->getTable();
        $params[] = $this->cfg->getConditionExpr('WHERE');
        $params[] = $this->cfg->getKeyColumn();
        // where
        $params[] = $this->cfg->getConditionExpr('AND');
        $params[] = $this->cfg->getSelectableExpr('AND');
        // group by
        $params[] = $this->cfg->getKeyColumn();

        $query = vsprintf($query, $params);

        // having
        if ($this->cfg->getSelectionMode() === SQLAdjacencyTreeDataConfig::SELECTION_MODE_INNER) {
            $query .= PHP_EOL . 'HAVING COUNT(child._id) > 0';
        }

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

    /**
     * @param list<string> $keys
     *
     * @return array<string, array<string, string>>
     */
    protected function fetchAncestorOrSelfTree(array $keys): array
    {
        if (! $keys) {
            return [];
        }

        $root        = strval($this->cfg->getRootValue());
        $qids        = array_map('strval', $keys);
        $qids        = array_diff($qids, [$root]);
        $keys        = array_flip($qids);
        $keys[$root] = true;

        $query = sprintf(
            'SELECT %s AS pid FROM %s WHERE %s IN (%%s)',
            $this->cfg->getParentKeyColumn(),
            $this->cfg->getTable(),
            $this->cfg->getKeyColumn()
        );
        while ($qids) {
            $nodes = $this->database->prepare(sprintf($query, SQLUtil::generateWildcards($qids)))->execute($qids);
            $qids  = [];
            while ($nodes->next()) {
                $nodeId                          = (string) $nodes->pid;
                isset($keys[$nodeId]) || $qids[] = $nodeId;
                $keys[$nodeId]                   = true;
            }
        }

        $query = sprintf(
            'SELECT	%s AS id, %s AS pid
			FROM	%s
			WHERE	%s IN (%s)
			%s
			%s',
            $this->cfg->getKeyColumn(),
            $this->cfg->getParentKeyColumn(),
            $this->cfg->getTable(),
            $this->cfg->getKeyColumn(),
            SQLUtil::generateWildcards($keys),
            $this->cfg->getConditionExpr('AND'),
            $this->cfg->getOrderByExpr('ORDER BY')
        );
        $nodes = $this->database->prepare($query)->execute(array_keys($keys));

        $children        = [];
        $children[$root] = [];
        while ($nodes->next()) {
            $nodeId                                  = (string) $nodes->id;
            $children[(string) $nodes->pid][$nodeId] = true;
            $children[$nodeId]                       = (array) $children[$nodeId];
        }

        return $children;
    }
}
