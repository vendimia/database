<?php
namespace Vendimia\Database;

/**
 * Simple-table WHERE, used in UPDATE and DELETE
 */
class SimpleWhere
{
    use WhereTrait;

    private DummyTableAliases $table_aliases;

    public function __construct(
        private $target_class,
        $constrains
    )
    {
        $this->table_aliases = new DummyTableAliases;
        $this->where(...$constrains);
    }

    public function __toString()
    {
        return $this->getSQLWhereString();
    }
}