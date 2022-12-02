<?php

namespace Vendimia\Database;

use Vendimia\Database\Driver\ConnectorInterface;

/**
 * Helper class to hold database and table configuration
 *
 * Because some classes must be used statically (like the entities), this
 * class will hold the common information between them in static methods and
 * properties.
 */
class Setup
{
    public static $connector;
    public static $table_aliases;

    public static function init(ConnectorInterface $connector)
    {
        static::$connector = $connector;
    }

    public static function getConnector(): ConnectorInterface
    {
        return self::$connector;
    }
}
