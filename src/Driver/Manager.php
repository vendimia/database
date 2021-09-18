<?php
namespace Vendimia\Database\Driver;

use Vendimia\Database\Migration\Schema;

/**
 * Database schema manager
 */
class Manager
{
    public function __construct(
        private ConnectorInterface $connector
    )
    {

    }

    /**
     * Creates a table
     */
    public function createTable(string $name, Schema $schema, array $options = [])
    {
        $name = $this->connector->escapeIdentifier($name);
        $sql = "CREATE TABLE $name (" . $schema->getFieldsForCreate() . ");";
        $this->connector->execute($sql);
    }
}