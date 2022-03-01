<?php

declare(strict_types=1);

namespace Hofff\Contao\Selectri\Model\Tree;

use Contao\Database;
use Hofff\Contao\Selectri\Exception\SelectriException;
use Hofff\Contao\Selectri\Model\Data;
use Hofff\Contao\Selectri\Model\DataFactory;
use Hofff\Contao\Selectri\Model\Node;
use Hofff\Contao\Selectri\Util\Icons;
use Hofff\Contao\Selectri\Util\LabelFormatter;
use Hofff\Contao\Selectri\Util\SQLUtil;
use Hofff\Contao\Selectri\Widget;

use function is_array;
use function is_object;
use function strlen;

class SQLAdjacencyTreeDataFactory implements DataFactory
{
    /** @var Database */
    private $database;

    /** @var SQLAdjacencyTreeDataConfig */
    private $cfg;

    public function __construct()
    {
        $this->database = Database::getInstance();
        $this->cfg      = new SQLAdjacencyTreeDataConfig();

        $this->cfg->setKeyColumn('id');
        $this->cfg->setParentKeyColumn('pid');
        $this->cfg->setRootValue(0);
        $this->cfg->setSelectionMode('all');
    }

    public function __clone()
    {
        $this->cfg = clone $this->cfg;
    }

    /** {@inheritDoc} */
    public function setParameters(array $params): void
    {
        isset($params['treeTable']) && $this->getConfig()->setTable($params['treeTable']);
        isset($params['mode']) && $this->getConfig()->setSelectionMode($params['mode']);
    }

    public function createData(?Widget $widget = null): Data
    {
        if (! $widget) {
            throw new SelectriException('Selectri widget is required to create a SQLAdjacencyTreeData');
        }

        $cfg = clone $this->getConfig();
        $this->prepareConfig($cfg);

        return new SQLAdjacencyTreeData($widget, $this->getDatabase(), $cfg);
    }

    public function getDatabase(): Database
    {
        return $this->database;
    }

    public function getConfig(): SQLAdjacencyTreeDataConfig
    {
        return $this->cfg;
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
    )
     */
    protected function prepareConfig(SQLAdjacencyTreeDataConfig $cfg): void
    {
        $database = $this->getDatabase();

        if (! $cfg->getOrderByExpr() && $database->fieldExists('sorting', $cfg->getTable())) {
            $cfg->setOrderByExpr('sorting');
        }

        if (! $cfg->getLabelCallback()) {
            $formatter = SQLUtil::createLabelFormatter($database, $cfg->getTable(), $cfg->getKeyColumn());
            $cfg->setLabelCallback($formatter->getCallback());
        }

        $callback = $cfg->getLabelCallback();
        if (is_array($callback) && is_object($callback[0]) && $callback[0] instanceof LabelFormatter) {
            $fields = $callback[0]->getFields();
            $cfg->addColumns($fields);

            if (! strlen($cfg->getOrderByExpr())) {
                $cfg->setOrderByExpr($fields[0]);
            }
        }

        if ($cfg->getIconCallback()) {
            return;
        }

        [$callback, $columns] = Icons::getTableIconCallback($cfg->getTable());
        if ($callback) {
            $cfg->setIconCallback($callback);
            $cfg->addColumns($columns);
        } else {
            $cfg->setIconCallback(static function (Node $node, Data $data, SQLAdjacencyTreeDataConfig $cfg) {
                return Icons::getIconPath(Icons::getTableIcon($cfg->getTable()));
            });
        }
    }
}
