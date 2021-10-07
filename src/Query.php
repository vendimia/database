<?php
namespace Vendimia\Database;

use Vendimia\Database\Driver\Result;
use InvalidArgumentException;
use RuntimeException;

/**
 * Build a SQL query using PHP methods.
 */
class Query
{
    use Where;

    /** Tables used in the query */

    private TableAliases $table_aliases;

    private array $fields = [];
    private array $joins = [];
    private ?string $limit = null;
    private string $order = '';

    public function __construct(
        private string $target_class,
        array $where = [])
    {

        $this->table_aliases = new TableAliases(Setup::$connector);
        $main_table = $this->table_aliases->addTableAlias($this->target_class::getTableName());
        $this->fields = [$main_table . '.*'];

        if ($where) {
            $this->where(...$where);
        }
    }

    /**
     * Adds an ORDER SQL clausule
     *
     * Each field prepended with a '-' will be ordered with DESC attribute
     */
    public function order(...$fields): self
    {
        $order = [];
        foreach ($fields as $f) {
            $desc = '';
            if (str_starts_with($f, '-')) {
                $field = substr($f, 1);
                $desc = ' DESC';
            } else {
                $field = $f;
            }

            $field = $this->table_aliases->getFullFieldName(
                $this->target_class::getTableName(),
                $field,
            );

            $order[] = $field . $desc;
        }
        $this->order = join(',', $order);
        return $this;
    }

    /**
     * Adds a LIMIT SQL clausule
     */
    public function limit(int $limit, int $offset = null):self
    {
        $this->limit = $limit;

        if ($offset) {
            $this->limit .= ' OFFSET ' . $offset;
        }

        return $this;
    }

    /**
     * Adds an INNER JOIN
     */
    public function join($entity, ...$on):self
    {
        if (!is_subclass_of($entity, Entity::class)) {
            throw new InvalidArgumentException("{$entity} is not an Entity");
        }

        if (!$on) {
            $fk = $entity::getName() . '_id';

            // Array en array, pues es una definición compleja
            $on = [[
                $entity::getPrimaryKeyField(),
                $fk,
            ]];
        }

        $this->joins[] = new Join($entity, $this->table_aliases, ...$on);

        return $this;
    }

    /**
     * Returns a Result object, with the database result information.
     */
    public function getResult(): Result
    {
        $sql = $this->getSQL();
        return Setup::$connector->execute($sql);
    }


    /**
     * Returns an EntitySet
     */
    public function find(): EntitySet
    {
        return new EntitySet($this->target_class, query: $this);
    }

    /**
     * Returns an Entity
     */
    public function get(): Entity
    {
        $result = $this->getResult();

        $data = $result->fetch();

        if (is_null($data)) {
            // Creamos una entidad vacía
            return (new $this->target_class)->setEmpty();
        }

        // No debe haber más de un resultado
        if (!is_null($result->fetch())) {
            throw new RuntimeException("Query returned more than one record");
        }

        // Procesamos la data
        return (new $this->target_class)->fromDatabase($data);

    }

    /**
     * Returns COUNT(*) from the query
     */
    public function count(): int
    {
        $this->fields = ['COUNT(*)'];

        $sql = $this->getSQL();
        $result = Setup::$connector->execute($sql);

        $data = $result->fetch();

        return intval($data['COUNT(*)']);
    }

    /**
     * Retuns MAX(field) from the query
     */
    public function max($field): int
    {
        $field = Setup::$connector->escapeIdentifier($field);

        $this->fields = ["MAX({$field}) as __MAX"];

        $sql = $this->getSQL();
        $result = Setup::$connector->execute($sql);

        $data = $result->fetch();

        return intval($data['__MAX']);
    }

    /**
     * Retuns MIN(field) from the query
     */
    public function min($field): int
    {
        $field = Setup::$connector->escapeIdentifier($field);

        $this->fields = ["MIN({$field}) as __MIN"];

        $sql = $this->getSQL();
        $result = Setup::$connector->execute($sql);

        $data = $result->fetch();

        return intval($data['__MIN']);
    }

    /**
     * Retuns AVG(field) from the query
     */
    public function AVG($field): int
    {
        $field = Setup::$connector->escapeIdentifier($field);

        $this->fields = ["AVG({$field}) as __AVG"];

        $sql = $this->getSQL();
        $result = Setup::$connector->execute($sql);

        $data = $result->fetch();

        return intval($data['__AVG']);
    }

    /**
     * Retuns SUM(field) from the query
     */
    public function SUM($field): int
    {
        $field = Setup::$connector->escapeIdentifier($field);

        $this->fields = ["SUM({$field}) as __SUM"];

        $sql = $this->getSQL();
        $result = Setup::$connector->execute($sql);

        $data = $result->fetch();

        return intval($data['__SUM']);
    }


    /**
     * Creates the actual SQL
     */
    public function getSQL(): string
    {
        $sql = [
            'SELECT',
            join (',', $this->fields),
            'FROM',
            $this->table_aliases->getSQLTableList(),
        ];

        foreach ($this->joins as $join)
        {
            $sql[] = $join->getSQL();
        }

        if ($where = $this->getSQLWhereString()) {
            $sql[] = 'WHERE ' . $where;
        }

        if ($this->order) {
            $sql[] = 'ORDER BY ' . $this->order;
        }
        if ($this->limit) {
            $sql[] = 'LIMIT ' . $this->limit;
        }

        return join (' ', $sql);
    }

}
