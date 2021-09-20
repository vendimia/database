<?php
namespace Vendimia\Database\Driver\Mysql;

use Vendimia\Database\Entity;
use Vendimia\Database\FieldType;
use Vendimia\Database\Driver\Result;
use Vendimia\Database\Driver\ConnectorInterface;
use Vendimia\Database\Driver\ConnectorAbstract;
use Vendimia\Database\Migration\FieldDef;
use Vendimia\Database\DatabaseException;

use InvalidArgumentException;
use Exception;
use RuntimeException;
use MySQLi;

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

            FieldType::Bool => 'TINYINT',
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
            FieldType::Blob => 'BLOB',

            FieldType::Date => 'DATE',
            FieldType::Time => 'TIME',
            FieldType::DateTime => 'DATETIME',

            FieldType::JSON => 'JSON',
            FieldType::Enum => 'ENUM',
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

    public function escapeIdentifier(string|array $identifier): string|array
    {
        return $this->escape($identifier, '`');
    }

    /**
     * Executes a SQL query
     */
    public function execute(string $query): Result
    {
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
     * AutoIncrement is a INTEGER AUTO_INCREMENT
     */
    public function buildAutoIncrementFieldDefName(
        string $name,
        FieldType $type,
        ?int $length = null,
        ?int $decimal = null,
        bool $null = true,
        $default = null,
    )
    {
        return [
            $this->escapeIdentifier($fielddef->name),
            "INTEGER AUTO_INCREMENT"
        ];
    }
}
