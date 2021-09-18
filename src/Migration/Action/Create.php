<?php
namespace Vendimia\Database\Migration\Action;

use Vendimia\Database\Driver\ConnectorInterface;
use Vendimia\Database\Migration\Schema;
use Attribute;


/**
 * Creates a new table and indexes
 */
#[Attribute]
class Create implements ActionInterface
{

    public function perform(
        ConnectorInterface $connection,
        Schema $schema
    )
    {
        // Empezamos por la tabla
        $create = 'CREATE TABLE ' .
            $connection->escapeIdentifier($schema->getTableName()) .
            " (" . $schema->getFieldsForCreate() . ')';

        $connection->execute($create);

        // Ahora sus índices.
        foreach ($schema->getIndexes() as $create_index) {
            $connection->execute($create_index);
        }
    }
}