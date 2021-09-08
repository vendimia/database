<?php
namespace Vendimia\Database;

/**
 * Stores a INNER JOIN element
 */
class Join
{
    use Where;

    private $table;

    public function __construct(
        private $target_class,
        private TableAliases $table_aliases,
        ...$on)
    {
        $this->table_aliases->addTableAlias(
            $this->table = $this->target_class::getTableName(),
            join_group: true,
        );
        $this->where(...$on);
    }

    /**
     * Returns the SQL JOIN declaration
     */
    public function getSQL()
    {
        $table = $this->table_aliases->getSQLTableList($this->table);
        return 'INNER JOIN ' . $table . ' ON ' . $this->getSQLWhereString();
    }
}
