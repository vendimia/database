<?php
namespace Vendimia\Database\Driver;

/**
 * Common methods for ConnectorInterface implementation
 */
abstract class ConnectorAbstract
{
    /**
     * Prepares an SQL INSERT statement
     */
    protected function prepareInsert(string $table, array $payload): string
    {
        $fields = [];
        $values = [];

        foreach ($payload as $field => $value) {
            $fields[] = $this->escapeIdentifier($field);
            $values[] = $this->escape($value);
        }

        $sql = 'INSERT INTO ' . $this->escapeIdentifier($table) . ' (';
        $sql .= join(', ', $fields) . ') VALUES (' . join(', ', $values) . ')';

        return $sql;
    }

    /**
     * Prepares an SQL UPDATE statement
     */
    protected function prepareUpdate(
        string $table,
        array $payload,
        string $where = null
    ): string
    {
        $values = [];
        foreach ($payload as $field => $value) {
            $values[] = $this->escapeIdentifier($field) . '=' .
                $this->escape($value);
        }

        $sql = 'UPDATE ' . $this->escapeIdentifier($table) . ' SET ' .
            join (', ', $values);

        if (!is_null($where)) {
            $sql .= ' WHERE ' . $where;
        }

        return $sql;
    }
}
