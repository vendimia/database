<?php
namespace Vendimia\Database\Migration\Action;

interface ActionInterface
{
    /**
     * Perform the action
     */
    public function perform(ConnectorInterface $connection, Schema $schema);

}