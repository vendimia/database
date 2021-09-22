<?php
namespace Vendimia\Database\Driver;

use Vendimia\Database\FieldType;
use Vendimia\Database\Migration\FieldDef;
use InvalidArgumentException;

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

    /**
     * Builds a database field definition
     */
    public function buildFieldDef(FieldDef $fielddef): string
    {

        // Si este connector tiene un método build{$type}FieldDefName, lo usamos en vez
        // de este genérico
        $method = "build{$fielddef->type->name}FieldDefName";

        if (method_exists($this, $method)) {
            $def = $this->$method($fielddef);
        } else {
            // Nombre
            $def = [
                $this->escapeIdentifier($fielddef->name),
                $this->getNativeType($fielddef->type)
            ];
        }

        if ($fielddef->null) {
            $def[] = 'NULL';
        } else {
            $def[] = 'NOT NULL';
        }
        if (!is_null($fielddef->default)) {
            $def[] = 'DEFAULT ' . $this->escape($fielddef->default);
        }

        return join(' ', $def);
    }

    /**
     * Builds a CREATE INDEX statement
     */
    public function buildIndexDef(
        string $table_name,
        array $field_names,
        bool $unique = false,
    ): string
    {
        $def[] = "CREATE";

        if ($unique) {
            $def[] = "UNIQUE";
        }

        $def = [
            ...$def,
            'INDEX',
            $this->escapeIdentifier('idx_' . join('_', $field_names)),
            'ON',
            $this->escapeIdentifier($table_name),
            '(' . join(',', $this->escapeIdentifier($field_names)) . ')'
        ];

        return join(' ', $def);
    }
}
