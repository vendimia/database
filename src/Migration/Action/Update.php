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
        // Empezamos añadiendo nuevos campos
        foreach ($schema->getAddFields() as $fielddef) {
            $alter_table = 'ALTER TABLE ' .
                $connection->escapeIdentifier($schema->getTableName()) .
                " ADD {$fielddef}"
            ;

            $connection->execute($alter_table);
        }

        // Ahora los cambios que requieren ser cambiados
        foreach ($schema->getChangeFields() as $field_name => $fielddef) {

            // Si existe $fielddef::rename_from, lo usamos
            if ($fielddef->rename_from ?? false) {
                $field_name = $fielddef->rename_from;
            }

            $alter_table = join(' ', [
                'ALTER TABLE',
                $connection->escapeIdentifier($schema->getTableName()),
                "CHANGE",
                $connection->escapeIdentifier($field_name),
                $fielddef,
            ]);

            $connection->execute($alter_table);
        }

        // Ahora los campos que borramos
        foreach ($schema->getDropFields() as $field_name) {
            $alter_table = join(' ', [
                'ALTER TABLE',
                $connection->escapeIdentifier($schema->getTableName()),
                "DROP",
                $connection->escapeIdentifier($field_name),
            ]);
            $connection->execute($alter_table);
        }
    }
}
