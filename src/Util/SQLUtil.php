<?php

declare(strict_types=1);

namespace Hofff\Contao\Selectri\Util;

use Contao\Database;

use function array_filter;
use function array_map;
use function array_unique;
use function array_values;
use function count;
use function ltrim;
use function rtrim;
use function str_repeat;

class SQLUtil
{
    /**
     * @param array<mixed> $columns
     *
     * @return list<string>
     */
    public static function getCleanedColumns(array $columns): array
    {
        return array_unique(array_values(array_filter(array_map('strval', $columns))));
    }

    public static function createLabelFormatter(Database $database, string $table, string $keyColumn): LabelFormatter
    {
        $fields = [];
        if ($database->fieldExists('name', $table)) {
            $fields[] = 'name';
        } elseif ($database->fieldExists('title', $table)) {
            $fields[] = 'title';
        }

        $fields[] = $keyColumn;

        $format = '';
        foreach ($fields as $field) {
            $format .= $field === $keyColumn ? ' (ID %s)' : ', %s';
        }

        $format = ltrim($format, ', ');

        return new LabelFormatter($format, $fields);
    }

    /**
     * @param list<mixed> $args
     */
    public static function generateWildcards(array $args): string
    {
        return rtrim(str_repeat('?,', count($args)), ',');
    }
}
