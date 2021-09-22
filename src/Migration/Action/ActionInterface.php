<?php
namespace Vendimia\Database\Migration\Action;

use Vendimia\Database\Driver\ConnectorInterface;
use Vendimia\Database\Migration\Schema;

interface ActionInterface
{
    /**
     * Perform the action
     */
    public function perform(ConnectorInterface $connection, Schema $schema);

}
