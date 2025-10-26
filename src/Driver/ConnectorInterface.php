<?php

namespace Vendimia\Database\Driver;

use Vendimia\Database\Migration\FieldDef;
use Vendimia\Database\FieldType;

interface ConnectorInterface
{

    /**
     * Performs the connection to the database.
     */
    public function connect(): void;

    /**
     * Forces a disconnection from the database. Must set self::$db to null.
     */
    public function disconnect(): void;

    /**
     * Returns the driver's name.
     */
    public function getName(): string;

    /**
     * Returns this driver's field name for a Vendima field type
     */
    public function getNativeType(FieldType $type): string;

    /**
     * Escapes and/or convert several PHP values to valid database values
     */
    public function escape(mixed $value, string $quote_char): string|array;

    /**
     * Escapes one or more identifiers, like a table name
     */
    public function escapeIdentifier(string|array $identifier): string|array;

    /**
     * Wrapper over the native connector escape method
     */
    public function nativeEscapeString(string $value, bool $quoted = false): string;

    /**
     * Executes a SQL query
     */
    public function execute(string $query): Result;

    /**
     * Returns the row count from a result when possible.
     *
     * Some drivers returns a string when the count is greater than PHP_INT_MAX
     */
    public function resultCount($result): int|string;

    /**
     * Fetchs a column from a query result, or null if there are no more.
     */
    public function fetch($result): ?array;

    /**
     * Frees the memory associated with a result
     */
    public function free($result): void;

    /**
     * Executes a SQL INSERT. Returns the primary key value.
     */
    public function insert(string $table, array $payload): int;

    /**
     * Executes a SQL UPDATE. Returns the number of records affected.
     */
    public function update(string $table, array $payload, ?string $where = null): int;

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
