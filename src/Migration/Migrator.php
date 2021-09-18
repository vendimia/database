<?php
namespace Vendimia\Database\Migration;

use Vendimia\Database\Driver\ConnectorInterface;
use ReflectionClass;
use ReflectionAttribute;

/**
 * Teh Migrator 😎
 */
class Migrator
{
    public function __construct(
        private ConnectorInterface $connection
    )
    {

    }

    /**
     * Reads a migration file, and executes the migration
     */
    public function execute($filename)
    {
        $class = require $filename;

        $migration = new $class;

        // Los métodos son las tablas
        $rc = new ReflectionClass($class);

        foreach ($rc->getMethods() as $rm) {
            // El método debe tener una Action
            $attrs = $rm->getAttributes(
                Action\ActionInterface::class,
                ReflectionAttribute::IS_INSTANCEOF
            );
            if (!$attrs) {
                continue;
            }
            $table_name = $rm->name;

            // Solo debe haber una acción.
            $action = $attrs[0]->newInstance();

            // Creamos un schema, y ejecutamos el método
            $schema = new Schema($table_name);
            $rm->invoke($migration, $schema);

            // Ejecutamos la acción
            $action->perform($this->connection, $schema);
        }

    }
}