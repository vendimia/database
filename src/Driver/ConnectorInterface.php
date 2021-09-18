<?php
namespace Vendimia\Database\Driver;

use Vendimia\Database\Migration\FieldDef;
use Vendimia\Database\FieldType;

interface ConnectorInterface
{
    /**
     * Returns the driver's name.
     */
    public function getName(): string;

    /**
     * Returns this driver's field name for a Vendima field type
     */
    public function getNativeType(FieldType $type): string;

    /**
     * Converts and escape one o more PHP values, adding quotes if necessary.
     */
    public function escape(mixed $value, string $quote_char): string|array;

    /**
     * Escapes one or more identifiers, like a table name
     */
    public function escapeIdentifier(string|array $identifier): string|array;

    /**
     * Executes a SQL query
     */
    public function execute(string $query): Result;

    /**
     * Fetchs a column from a query result, or null if there are no more.
     */
    public function fetch($result): ?array;

    /**
     * Executes a SQL INSERT. Returns the primary key value.
     */
    public function insert(string $table, array $payload): int;

    /**
     * Executes a SQL UPDATE. Returns the number of records affected.
     */
    public function update(string $table, array $payload, string $where = null): int;

    /**
     * Builds a database field definition
     */
    public function buildFieldDef(FieldDef $fielddef): string;

    /**
     * Builds a CREATE INDEX SQL statement
     */
    public function buildIndexDef(
        string $table_name,
        array $field_names,
        bool $unique = false,
    ): string;

}
