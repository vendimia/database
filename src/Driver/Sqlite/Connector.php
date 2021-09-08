<?php
namespace Vendimia\Database\Driver\Sqlite;

use Vendimia\Database\Entity;
use Vendimia\Database\FieldType;
use Vendimia\Database\Driver\Result;
use Vendimia\Database\Driver\ConnectorInterface;
use Vendimia\Database\Driver\ConnectorAbstract;

use SQLite3;
use InvalidArgumentException;
use RuntimeException;

class Connector implements ConnectorInterface
{
    const FIELDS = [
        FieldType::Bool => 'integer',
        FieldType::Byte => 'integer',
        FieldType::SmallInt => 'integer',
        FieldType::Integer => 'integer',
        FieldType::BigInt => 'integer',

        FieldType::Float => 'real',
        FieldType::Double => 'real',
        FieldType::Decimal => 'numeric',

        FieldType::Char => 'text',
        FieldType::FixChar => 'text',
        FieldType::Text => 'text',
        FieldType::Blob => 'blob',

        FieldType::Date => 'text',
        FieldType::Time => 'text',
        FieldType::DateTime => 'text',

        FieldType::JSON => 'text',

        FieldType::ForeignKey => 'integer',
    ];

    public function __construct(...$args)
    {
        $this->db = new SQLite3(...$args);
        $this->db->enableExceptions(true);
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
                . $this->db->escapeString($value)
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
        return $this->escape($identifier, '"');
    }

    /**
     * Executes a SQL query
     */
    public function execute(string $query): Result
    {
        $result = $this->db->query($query);
        if ($result === false) {
            throw new RuntimeException($this->db->lastErrorMsg());
        }
        return new Result($this, $result);
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
    public function update(string $table, array $payload, string $where = null): int
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

        // Al parecer, PHP no soporta sqlite_changes()
        return 1;
    }

}
