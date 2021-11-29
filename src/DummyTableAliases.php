<?php
namespace Vendimia\Database;

/**
 * TableAliases-like class, just for compatibility with single table WHERE,
 * like in DELETE
 */
class DummyTableAliases
{
    /**
     * Return just the escape database field name
     */
    public function getFullFieldName($table, $field, $simple): string
    {
        return Setup::$connector->escapeIdentifier($field);
    }
}
