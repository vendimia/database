<?php
namespace Vendimia\Database;

use Vendimia\Database\Driver\ConnectorInterface;

/**
 * Table lookup table, for aliasing.
 */
class TableAliases
{
    private $table = [];
    private $group = [];
    private $index = 0;

    public function __construct(
        private ConnectorInterface $connector
    )
    {
    }

    /**
     * Adds and returns a new table alias.
     */
    public function addTableAlias($name, $join_group = false): string
    {
        if (key_exists($name, $this->table)) {
            return $this->table[$name];
        }

        $new_alias = 'T' . base_convert($this->index++, 10, 24);

        $this->table[$name] = $new_alias;

        $group = "";
        if ($join_group) {
            $group = $name;
        }

        $this->group[$group][$name] = $this->table[$name];

        return $new_alias;
    }

    public function getTableAlias($name): string
    {
        if (!key_exists($name, $this->table)) {
            return $this->addTableAlias($name);
        }

        return $this->table[$name];
    }

    /**
     * Returns the full escaped field name, including table alias name
     */
    public function getFullFieldName($table, $field): string
    {
        $table = $this->connector->escapeIdentifier($this->getTableAlias($table));
        $field = $this->connector->escapeIdentifier($field);

        return "{$table}.{$field}";
    }

    /**
     * Returns the table list with its aliases in SQL format
     */
    public function getSQLTableList($group = ''): string
    {
        $sql = [];
        foreach ($this->group[$group] as $name => $alias) {
            $sql[] = "{$name} AS {$alias}";
        }

        return join(',', $sql);
    }
}
