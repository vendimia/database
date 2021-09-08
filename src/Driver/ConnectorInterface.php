<?php
namespace Vendimia\Database\Driver;

interface ConnectorInterface
{
    public function __construct($args);

    /**
     * Converts and escape a PHP value, adding quotes if necessary.
     */
    public function escape(mixed $value, string $quote_char): string|array;

    /**
     * Escapes an identifier, like a table name
     */
    public function escapeIdentifier(string $identifier): string;

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

}
