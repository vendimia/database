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
    public function __construct(
        private $if_not_exists = false,
        private $drop_first = false,
    )
    {

    }

    public function perform(
        ConnectorInterface $connection,
        Schema $schema
    )
    {
        if ($this->drop_first) {
            $connection->execute('DROP TABLE IF EXISTS ' .
                $connection->escapeIdentifier($schema->getTableName())
            );
        }

        // Empezamos por la tabla
        $create = 'CREATE TABLE ' . ($this->if_not_exists ? 'IF NOT EXISTS ' : '') .
            $connection->escapeIdentifier($schema->getTableName()) .
            " (" . $schema->getFieldsForCreate() . ')';

        $connection->execute($create);

        // Ahora sus índices.
        foreach ($schema->getCreateIndexes() as $create_index) {
            $connection->execute($create_index);
        }
    }
}
