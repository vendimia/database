<?php
namespace Vendimia\Database\Driver;

use Vendimia\Database\FieldType;
use Vendimia\Database\Entity;
use Vendimia\Database\Field\FieldInterface;
use Vendimia\Database\Migration\FieldDef;
use InvalidArgumentException;
use Stringable;

/**
 * Common methods for ConnectorInterface implementation
 */
abstract class ConnectorAbstract
{
    protected $db;

    /** Last SQL command executed */
    protected $last_sql;

    /**
     * Returns the last SQL command executed
     */
    public function getLastSQL()
    {
        return $this->last_sql;
    }

    public function escape(mixed $value, string $quote_char = '\''): string|array
    {
        if (is_object($value)) {
            if ($value instanceof Entity) {
                if ($value->isEmpty()) {
                    return 'NULL';
                }
                return $value->pk();
            }
            if ($value instanceof FieldInterface) {
                return $this->escapeIdentifier($value->getFieldName());
            }

            // TODO: Aun no existe DatabaseValue
            if ($value instanceof DatabaseValue) {
                return $value->getDatabaseValue($this);
            }

            if ($value instanceof Stringable) {
                return $quote_char
                . $this->nativeEscapeString((string)$value)
                . $quote_char;
            }
        } elseif (is_array($value)) {
            return array_map(
                fn($value) => $this->escape($value, $quote_char),
                $value
            );
        } elseif (is_numeric($value)) {
            // Ya que los numeros /también/ son strings, procesamos is_numeric
            // primero

            // Los números no requieren quotes
            return $value;
        } elseif (is_string($value)) {
            return $quote_char
                . $this->nativeEscapeString((string)$value)
                . $quote_char;
        } elseif (is_null($value)) {
            return 'NULL';
        } elseif (is_bool($value)) {
            return $value ? '1' : '0';
        }

        $object_type = gettype($value);

        if ($object_type == 'object') {
            $object_type .= ":" . $value::class;
        }

        throw new InvalidArgumentException("Can't escape a value of type '{$object_type}'.");
    }


    /**
     * Prepares a SQL INSERT statement
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
     * Prepares a SQL UPDATE statement
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
     * Prepares a SQL DELETE statement
     */
    public function prepareDelete(
        string $table,
        string $where
    )
    {
        $sql = "DELETE FROM " . $this->escapeIdentifier($table) . ' WHERE '
            . $where
        ;

        return $sql;
    }

    /**
     * Starts a transaction
     */
    public function startTransaction()
    {
        $this->execute('START TRANSACTION');
    }

    /**
     * Persists the operations executed inside the transaction
     */
    public function commitTransaction()
    {
        $this->execute('COMMIT');
    }

    /**
     * Cancels all the operations executed inside the transaction
     */
    public function rollbackTransaction()
    {
        $this->execute('ROLLBACK');
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

        $index_name = join('_', ['idx', $table_name, ...$field_names]);

        $def = [
            ...$def,
            'INDEX',
            $this->escapeIdentifier($index_name),
            'ON',
            $this->escapeIdentifier($table_name),
            '(' . join(',', $this->escapeIdentifier($field_names)) . ')'
        ];

        return join(' ', $def);
    }
}
