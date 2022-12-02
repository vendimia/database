<?php

namespace Vendimia\Database\Driver\Mysql;

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
use MySQLi;
use Stringable;

class Connector extends ConnectorAbstract implements ConnectorInterface
{
    public function __construct(...$args)
    {
        $this->db = new MySQLi;

        // Necesitamos MYSQLI_CLIENT_FOUND_ROWS
        $args['flags'] ??= 0;
        $args['flags'] |= MYSQLI_CLIENT_FOUND_ROWS;

        $result = $this->db->real_connect(...$args);

        if ($result === false) {
            throw new RuntimeException($this->link->error);
        }
    }

    public function getName(): string
    {
        return 'mysql';
    }

    public function getNativeType(FieldType $type): string
    {
        return match($type) {
            FieldType::AutoIncrement => 'INTEGER',

            FieldType::Boolean => 'TINYINT',
            FieldType::Byte => 'TINYINT',
            FieldType::SmallInt => 'SMALLINT',
            FieldType::Integer => 'INTEGER',
            FieldType::BigInt => 'BIGINT',

            FieldType::Float => 'FLOAT',
            FieldType::Double => 'DOUBLE',
            FieldType::Decimal => 'DECIMAL',

            FieldType::Char => 'VARCHAR',
            FieldType::FixChar => 'CHAR',
            FieldType::Text => 'TEXT',
            FieldType::MediumText => 'MEDIUMTEXT',
            FieldType::LongText => 'LONGTEXT',
            FieldType::Blob => 'BLOB',

            FieldType::Date => 'DATE',
            FieldType::Time => 'TIME',
            FieldType::DateTime => 'DATETIME',

            FieldType::JSON => 'JSON',
            FieldType::Enum => 'ENUM',

            FieldType::ForeignKey => 'INTEGER',
        };
    }

    public function nativeEscapeString(string $value, bool $quoted = false): string
    {
        $quote_char = '';
        if ($quoted) {
            $quote_char = '\'';
        }

        return $quote_char . $this->db->real_escape_string($value) . $quote_char;
    }

    public function escapeIdentifier(string|array $identifier): string|array
    {
        return $this->escape($identifier, '`');
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
            throw new DatabaseException($this->db->error, previous: $e);
        }

        if ($result === false) {
            throw new DatabaseException($this->db->error);
        }
        return new Result($this, $result);
    }

    public function fetch($result): ?array
    {
        $row = $result->fetch_assoc();

        if (is_null($row)) {
            return null;
        }

        return $row;
    }

    /**
     * Frees the memory associated with a result
     */
    public function free($result): void
    {
        $result->free_result();
    }

    /**
     * Executes a SQL INSERT. Returns the primary key value.
     */
    public function insert(string $table, array $payload): int
    {
        $sql = $this->prepareInsert($table, $payload);

        $this->execute($sql);

        return $this->db->insert_id;

    }


    /**
     * Executes a SQL UPDATE. Returns the number of records affected.
     */
    public function update(
        string $table,
        array $payload,
        string $where = null
    ): int
    {
        $sql = $this->prepareUpdate($table, $payload, $where);

        $result = $this->execute($sql);

        return $this->db->affected_rows;
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

        return $this->db->affected_rows;
    }


    /**
     * Varchar requieres a length
     */
    public function buildCharFieldDefName(FieldDef $fielddef): array
    {
        if (is_null($fielddef->length)) {
            throw new InvalidArgumentException("'Char' field requires a length");
        }

        return [
            $this->escapeIdentifier($fielddef->name),
            $this->getNativeType($fielddef->type) . "({$fielddef->length})"
        ];
    }

    /**
     * FixChar requieres a length
     */
    public function buildFixCharFieldDefName(FieldDef $fielddef): array
    {
        if (is_null($fielddef->length)) {
            throw new InvalidArgumentException("'FixChar' field requires a length");
        }

        return [
            $this->escapeIdentifier($fielddef->name),
            $this->getNativeType($fielddef->type) . "({$fielddef->length})"
        ];
    }

    /**
     * Enum requieres a 'values' property
     */
    public function buildEnumFieldDefName(FieldDef $fielddef): array
    {
        if (!$fielddef->values) {
            throw new InvalidArgumentException("'Enum' field requires a 'values' property with valid values");
        }

        return [
            $this->escapeIdentifier($fielddef->name),
            $this->getNativeType($fielddef->type) . '(' . join(',', $this->escape($fielddef->values)) . ')',
        ];
    }

    /**
     * Decimal has a special format, in any driver
     */
    public function buildDecimalFieldDefName(FieldDef $fielddef): array
    {
        if (!$fielddef->length || !$fielddef->decimal) {
            throw new InvalidArgumentException("'Decimal' field requires a 'length' and a 'decimal' properties");
        }

        return [
            $this->escapeIdentifier($fielddef->name),
            $this->getNativeType($fielddef->type) . "({$fielddef->length},{$fielddef->decimal})",
        ];

    }

    /**
     * AutoIncrement is a INTEGER AUTO_INCREMENT
     */
    public function buildAutoIncrementFieldDefName(FieldDef $fielddef)
    {
        return [
            $this->escapeIdentifier($fielddef->name),
            "INTEGER AUTO_INCREMENT"
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
            'ON',
            $this->escapeIdentifier($table_name),
        ];

        return join(' ', $def);
    }
}
