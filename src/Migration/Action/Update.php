<?php
namespace Vendimia\Database\Migration\Action;

use Vendimia\Database\Driver\ConnectorInterface;
use Vendimia\Database\Migration\Schema;
use Attribute;

/**
 * Updates table schema and indexes
 */
#[Attribute]
class Update implements ActionInterface
{
    public function perform(
        ConnectorInterface $connection,
        Schema $schema
    )
    {
        // Empezamos actualizando la tabla
        foreach ($schema->getFields() as $fielddef) {
            $alter_table = 'ALTER TABLE ' .
                $connection->escapeIdentifier($schema->getTableName()) .
                " ADD {$fielddef}"
            ;

            $connection->execute($alter_table);
        }
    }
}
