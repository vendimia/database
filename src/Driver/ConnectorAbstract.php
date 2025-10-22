<?php

namespace Vendimia\Database\Driver;

use Vendimia\Database\ConstrainAction;
use Vendimia\Database\FieldType;
use Vendimia\Database\Entity;
use Vendimia\Database\DatabaseReadyValue;
use Vendimia\Database\Field\FieldInterface;
use Vendimia\Database\Migration\FieldDef;

use InvalidArgumentException;
use Stringable;
use Generator;

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

    /**
     * Prepares an SQL query expanding variables
     *
     * A variable is defined by a string inside brackets. Its value must be in the
     * $args variadic.
     *
     * A variable name can be sufixed with this modifiers, separed by a colon
     *
     * * `i`: The variable is an identifier, it should be escaped differently
     * * `s`: The variable has the same value as the name.
     */
    public function prepare(string $query, ...$args): string
    {
        $regexp = '/\{(.+?)\}/';

        $count = preg_match_all($regexp, $query, $matches, PREG_SET_ORDER);

        if (!$count) {
            // No hay matches. O hubo un error. Retornamos el query intacto
            return $query;
        }

        $replace = [];

        foreach ($matches as $match) {
            $variable = $match[1];

            // Método para escapar el valor, camiado por el modificador 'i'
            $escape_method = $this->escape(...);

            $self_value = false;

            $colon_pos = strpos($variable, ':');
            if ($colon_pos !== false) {
                $modifiers = substr($variable, $colon_pos + 1);
                $variable = substr($variable, 0, $colon_pos);

                for ($i = 0; $i < strlen($modifiers); $i++) {
                    switch($modifiers[$i]) {
                        case 'i':
                            // Identificador
                            $escape_method = $this->escapeIdentifier(...);
                            break;
                        case 's':
                            // Valor es el mismo nombre, escapado.
                            $self_value = true;
                            break;
                    }
                }
            }

            if ($self_value) {
                $value = $escape_method($variable);
            } else {
                if (!key_exists($variable, $args)) {
                    throw new InvalidArgumentException("Missing argument for variable '{$variable}'");
                }
                $value = $escape_method($args[$variable]);
            }

            $replace[$match[0]] = $value;
        }

        return strtr($query, $replace);
    }

    /**
     * Converts and escapes a value from a number of types to a database-ready
     * value
     */
    public function escape(
        mixed $value,
        string $quote_char = '\'',
        bool $force_quote_numbers = false
    ): string|array
    {
        if (is_object($value)) {
            if ($value instanceof DatabaseReadyValue) {
                return $value->getValue();
            }
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
        } elseif (is_null($value)) {
            return 'NULL';
        } elseif (is_bool($value)) {
            return $value ? '1' : '0';
        } elseif (is_array($value)) {
            return array_map(
                fn($value) => $this->escape($value, $quote_char, $force_quote_numbers),
                $value
            );
        } elseif ($value != '' && preg_match('/^[-+]?\d+(\.\d+)?$/', $value) === 1) {
            // Ya que los numeros /también/ son strings, procesamos los números
            // primero

            if ($force_quote_numbers) {
                return $quote_char
                . $value
                . $quote_char;
            }

            // Los números no requieren quotes
            return $value;
        } elseif (is_string($value)) {
            return $quote_char
                . $this->nativeEscapeString((string)$value)
                . $quote_char;
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
        ?string $where = null
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
            // Si $default es un array, debe ser para un campo JSON.
            $default = $this->escape($fielddef->default);
            if (is_array($fielddef->default)) {
                $default = json_encode($fielddef->default);
            }
            $def[] = 'DEFAULT ' . $default;
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

    public function buildForeignKeyDef(
        string $table_name,
        string $field_name,
        string $foreign_table_name,
        array $foreign_field_names,
        ConstrainAction $on_update,
        ConstrainAction $on_delete,
    ): string
    {
        $def = [
            'FOREIGN KEY',
            '(' . $this->escapeIdentifier($field_name) . ')',
            'REFERENCES',
            $this->escapeIdentifier($foreign_table_name),
            '(' . join(',', $this->escapeIdentifier($foreign_field_names)) . ')',
            'ON UPDATE', $on_update->value,
            'ON DELETE', $on_delete->value,
        ];
        return join(' ', $def);
    }

    /**
     * Since SQLite doesn't support 'ALTER TABLE CHANGE', the SQL creation has
     * been moved here.
     */
    public function buildChangeFieldStatement(
        string $table_name,
        string $field_name,
        string $fielddef
    ): Generator
    {
        yield join(' ', [
            'ALTER TABLE',
            $this->escapeIdentifier($table_name),
            "CHANGE",
            $this->escapeIdentifier($field_name),
            $fielddef,
        ]);
    }

}
