<?php

namespace Vendimia\Database\Driver\Sqlite;

use Vendimia\Database\Entity;
use Vendimia\Database\FieldType;
use Vendimia\Database\Driver\Result;
use Vendimia\Database\Driver\ConnectorInterface;
use Vendimia\Database\Driver\ConnectorAbstract;
use Vendimia\Database\Migration\FieldDef;
use Vendimia\Database\Field\FieldInterface;
use Vendimia\Database\DatabaseException;

use InvalidArgumentException;
use Exception;
use RuntimeException;
use SQLite3;
use Stringable;
use Generator;

class Connector extends ConnectorAbstract implements ConnectorInterface
{
    public function __construct(...$args)
    {
        $this->database_args = $args;
        $this->connect();
    }

    public function connect(): void
    {
        // Si ya existe una conexión a la db, la desconectamos
        if ($this->db) {
            $this->disconnect();
        }

        $this->db = new SQLite3(...$this->database_args);
        $this->db->enableExceptions(true);
    }

    public function disconnect(): void
    {
        if ($this->db) {
            $this->db->close();
        }
        $this->db = null;

    }

    public function getName(): string
    {
        return 'sqlite';
    }

    public function getNativeType(FieldType $type): string
    {
        return match($type) {
            FieldType::AutoIncrement => 'INTEGER',

            FieldType::Boolean => 'INTEGER',
            FieldType::Byte => 'INTEGER',
            FieldType::SmallInt => 'INTEGER',
            FieldType::Integer => 'INTEGER',
            FieldType::BigInt => 'INTEGER',

            FieldType::Float => 'REAL',
            FieldType::Double => 'REAL',
            FieldType::Decimal => 'NUMERIC',

            FieldType::Char => 'TEXT',
            FieldType::FixChar => 'TEXT',
            FieldType::Text => 'TEXT',
            FieldType::MediumText => 'TEXT',
            FieldType::LongText => 'TEXT',
            FieldType::Blob => 'BLOB',

            FieldType::Date => 'TEXT',
            FieldType::Time => 'TEXT',
            FieldType::DateTime => 'TEXT',

            FieldType::JSON => 'TEXT',

            FieldType::ForeignKey => 'INTEGER',
            FieldType::Enum => 'TEXT',
        };
    }

    public function nativeEscapeString(string $value, bool $quoted = false): string
    {
        $quote_char = '';
        if ($quoted) {
            $quote_char = '\'';
        }

        return $quote_char . $this->db->escapeString($value) . $quote_char;
    }

    public function escapeIdentifier(string|array $identifier): string|array
    {
        return $this->escape($identifier, '"');
    }

    /**
     * SQlite has a different START TRANSACTION statement
     */
    public function startTransaction()
    {
        $this->execute('BEGIN TRANSACTION');
    }

    /**
     * Executes a SQL query
     */
    public function execute(string $query): Result
    {
        $this->last_sql = $query;
        try {
            $result = $this->db->query($query);
        } catch (Exception $e) {
            throw new DatabaseException(
                "Error executing query: " . $e->getMessage(),
                previous: $e,
                extra: [
                    'query' => $query,
                    'last_error_msg' => $this->db->lastErrorMsg(),
                ]
            );
        }

        if ($result === false) {
            throw new DatabaseException(
                "Error executing query: " . $this->db->lastErrorMsg(),
                extra: [
                    'query' => $query
                ]
            );
        }
        return new Result($this, $result);
    }

    public function resultCount($result): int|string
    {
        // sqlite3 no implementa esta función
        return 0;
    }

    public function fetch($result): ?array
    {
        $row = $result->fetchArray(SQLITE3_ASSOC);

        if ($row === false) {
            return null;
        }

        return $row;
    }

    /**
     * Frees the memory associated with a result
     */
    public function free($result): void
    {
        $result->finalize();
    }

    /**
     * Executes a SQL INSERT. Returns the primary key value.
     */
    public function insert(string $table, array $payload): int
    {
        $fields = [];
        $values = [];

        foreach ($payload as $field => $value) {
            $fields[] = $this->escapeIdentifier($field);
            $values[] = $this->escape($value);
        }

        $sql = 'INSERT INTO ' . $this->escapeIdentifier($table) . ' (';
        $sql .= join(', ', $fields) . ') VALUES (' . join(', ', $values) . ')';

        $this->execute($sql);

        return $this->db->lastInsertRowID();
    }

    /**
     * Executes a SQL UPDATE. Returns the number of records affected.
     */
    public function update(string $table, array $payload, ?string $where = null): int
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

        $result = $this->execute($sql);

        return $this->db->changes();
    }

    /**
     * Executes a SQL DELETE. Returns the number of records affected.
     */
    public function delete(
        string $table,
        string $where
    ): int
    {
        $sql = $this->prepareDelete($table, $where);

        $result = $this->execute($sql);

        return $this->db->changes();
    }

    /**
     * Enum is emulated via a TEXT field with a CHECK constrain. Requieres a 'values' property.
     */
    public function buildEnumFieldDefName(FieldDef $fielddef): array
    {
        if (!$fielddef->values) {
            throw new InvalidArgumentException("'Enum' field requires a 'values' property with valid values");
        }

        return [
            $this->escapeIdentifier($fielddef->name),
            $this->getNativeType($fielddef->type) . ' CHECK(' . $this->escapeIdentifier($fielddef->name) . ' IN (' . join(',', $this->escape($fielddef->values)) . '))',
        ];
    }

    /**
     * Builds a DROP INDEX statement
     */
    public function buildDropIndexDef(
        string $table_name,
        array $field_names,
    ): string
    {
        $def = [
            'DROP INDEX',
            $this->escapeIdentifier('idx_' . join('_', $field_names)),
        ];

        return join(' ', $def);
    }

    /**
     * SQLite doesn't support 'ALTER TABLE CHANGE', so this will emulate one.
     *
     * Changes are made in 4 steps:
     *
     * - First, creates a new temporal field with $fielddef.
     * - Second, copies all the values from the old file to this temp field.
     * - Thirth, drops the old column.
     * - And last, renames the temporal field to the old name
     */
    public function buildChangeFieldStatement(
        string $table_name,
        string $field_name,
        string $fielddef
    ): generator
    {
        // Nombre temporal que tendrá la nueva columna
        $tmp_field_name = $this->escapeIdentifier('__t_' . $field_name);

        // El fielddef empieza con el nombre del nuevo campo. Lo removemos
        // para crearlo usando el temporal
        $tmp_fielddef = substr($fielddef, strpos($fielddef, " ") + 1);

        // Escapamos todo
        $table_name = $this->escapeIdentifier($table_name);
        $field_name = $this->escapeIdentifier($field_name);

        // Primero, creamos una columna temporal
        yield join(' ', [
            'ALTER TABLE',
            $table_name,
            "ADD",
            $tmp_field_name,
            $fielddef,
        ]);

        // Luego, copiamos el valor de la columna
        yield join(' ', [
            'UPDATE',
            $table_name,
            'SET',
            $tmp_field_name . '=' . $field_name,
        ]);

        // Borramos la columna original
        yield join(' ', [
            'ALTER TABLE',
            $table_name,
            'DROP COLUMN',
            $field_name,
        ]);

        // Y renombramos la columna temporal
        yield join(' ', [
            'ALTER TABLE',
            $table_name,
            "RENAME COLUMN",
            $tmp_field_name,
            'TO',
            $field_name,
        ]);
    }
}
