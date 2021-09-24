<?php
namespace Vendimia\Database\Driver\Sqlite;

use Vendimia\Database\Entity;
use Vendimia\Database\FieldType;
use Vendimia\Database\Driver\Result;
use Vendimia\Database\Driver\ConnectorInterface;
use Vendimia\Database\Driver\ConnectorAbstract;
use Vendimia\Database\Migration\FieldDef;
use Vendimia\Database\DatabaseException;

use SQLite3;
use InvalidArgumentException;
use RuntimeException;

class Connector extends ConnectorAbstract implements ConnectorInterface
{
    public function __construct(...$args)
    {
        $this->db = new SQLite3(...$args);
        $this->db->enableExceptions(true);
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

    public function escape(mixed $value, string $quote_char = '\''): string|array
    {
        // Ya que los numeros /también/ son strings, procesamos is_numeric
        // primero
        if (is_array($value)) {
            return array_map(
                fn($value) => $this->escape($value, $quote_char),
                $value
            );
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

    public function escapeIdentifier(string|array $identifier): string|array
    {
        return $this->escape($identifier, '"');
    }

    /**
     * Executes a SQL query
     */
    public function execute(string $query): Result
    {
        try {
            $result = $this->db->query($query);
        } catch (Exception $e) {
            throw new DatabaseException($this->db->lastErrorMsg(), previous: $e);
        }

        if ($result === false) {
            throw new DatabaseException($this->db->lastErrorMsg());
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
}
