<?php
namespace Vendimia\Database\Driver\Mysql;

use Vendimia\Database\Entity;
use Vendimia\Database\FieldType;
use Vendimia\Database\Driver\Result;
use Vendimia\Database\Driver\ConnectorInterface;
use Vendimia\Database\Driver\ConnectorAbstract;

use InvalidArgumentException;
use RuntimeException;
use MySQLi;

class Connector extends ConnectorAbstract implements ConnectorInterface
{
    const FIELDS = [
        FieldType::Bool => 'integer',
        FieldType::Byte => 'tinyint',
        FieldType::SmallInt => 'smallint',
        FieldType::Integer => 'int',
        FieldType::BigInt => 'bigint',

        FieldType::Float => 'float',
        FieldType::Double => 'double',
        FieldType::Decimal => 'decimal',

        FieldType::Char => 'varchar',
        FieldType::FixChar => 'char',
        FieldType::Text => 'text',
        FieldType::Blob => 'blob',

        FieldType::Date => 'date',
        FieldType::Time => 'time',
        FieldType::DateTime => 'datetime',

        FieldType::JSON => 'json',

        FieldType::ForeignKey => 'integer',
    ];

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

    public function escape(mixed $value, string $quote_char = '\''): string|array
    {
        // Ya que los numeros /también/ son strings, procesamos is_numeric
        // primero
        if (is_array($value)) {
            return array_map([$this, 'escape'], $value);
        } elseif (is_numeric($value)) {
            // Los números no requieren quotes
            return $value;
        } elseif (is_string($value)) {
            return $quote_char
                . $this->db->real_escape_string($value)
                . $quote_char;
        } elseif (is_null($value)) {
            return 'NULL';
        } elseif (is_bool($value)) {
            return $value ? '1' : '0';
        } elseif (is_object($value)) {
            if ($value instanceof Entity) {
                return $value->pk();
            }
            if ($value instanceof DatabaseValue) {
                return $value->getDatabaseValue($this);
            }
        }

        throw new InvalidArgumentException('Can\'t escape a value of type "' . gettype($value) . '".');
    }

    public function escapeIdentifier(string $identifier): string
    {
        return $this->escape($identifier, '`');
    }

    /**
     * Executes a SQL query
     */
    public function execute(string $query): Result
    {
        $result = $this->db->query($query);
        if ($result === false) {
            throw new RuntimeException($this->db->error);
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
}
