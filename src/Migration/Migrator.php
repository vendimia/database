<?php

namespace Vendimia\Database\Migration;

use Vendimia\Database\Driver\ConnectorInterface;
use Vendimia\Helper\TextFromDocComment;
use Vendimia\Logger\Logger;
use ReflectionClass;
use ReflectionAttribute;

/**
 * Teh Migrator 😎
 */
class Migrator
{
    public function __construct(
        private ConnectorInterface $connection,
        private ?Logger $logger = null,
    )
    {

    }

    /**
     * Reads a migration file, and executes the migration
     */
    public function execute(string $filename): void
    {
        $class = require $filename;

        $migration = new $class;

        // Los métodos son las tablas
        $rc = new ReflectionClass($class);

        // Sacamos la descripción
        $description = explode("\n",
            (string)new TextFromDocComment($rc->getDocComment())
        )[0];

        $this->logger?->info($description);

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
            $action_name = strtolower(
                array_slice(explode('\\', $attrs[0]->getName()), -1)[0]
            );
            $action = $attrs[0]->newInstance();

            // Ejecutamos el hook 'pre'
            $pre_method = $table_name . '__pre_' . $action_name;
            if (method_exists($migration, $pre_method)) {
                $this->logger?->info("· {$table_name}: {$action_name} (pre-hook)");
                $migration->$pre_method();
            }

            // Creamos un schema, y ejecutamos el método
            $this->logger?->info("· {$table_name}: {$action_name}");
            $schema = new Schema($table_name);
            $rm->invoke($migration, $schema);

            // Ejecutamos la acción
            $action->perform($this->connection, $schema);

            // Y ejecutamos el hook 'post'
            $post_method = $table_name . '__post_' . $action_name;
            if (method_exists($migration, $post_method)) {
                $this->logger?->info("· {$table_name}: {$action_name} (post-hook)");
                $migration->$post_method();
            }
        }
    }
}
