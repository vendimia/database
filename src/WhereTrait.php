<?php
namespace Vendimia\Database;

use DomainException;

/**
 * Methods for building a WHERE section, also used in JOINs
 *
 * Requires $this->table_aliases and $this->target_class
 */
trait WhereTrait
{
    /**
     * WHERE data
     *
     * Each element has the format [BOOLEAN-OPERATOR,QUERY,SIMPLE-QUERY]
     *
     * SIMPLE-QUERY is the query without table information, used when deleting
     * entity sets
     */
    public $where_parts = [];

    /** Boolean operator for next 'where' */
    public $next_boolean_opeartor = 'AND';

    /**
     * Builds a simple comparison element
     */
    public function buildElement($left, $right, $simple = false)
    {
        if ($left instanceof Field\FieldAbstract) {
            $left = $this->table_aliases->getFullFieldName(
                ...$left->getFullFieldName(),
                simple: $simple,
            );
        } else {
            $left = $this->table_aliases->getFullFieldName(
                //$this->target_class::getTableName(),
                ...$this->target_class::F($left)->getFullFieldName(),
                simple: $simple,
            );
        }

        if ($right instanceof Field\FieldAbstract) {
            $right = '=' . $this->table_aliases->getFullFieldName(
                ...$right->getFullFieldName()
            );
        } elseif ($right instanceOf Comparison) {
            $right = $right->getValue(Setup::$connector);
        } elseif (is_array($right)) {
            // Es un IN
            $right = ' IN (' . join(', ', Setup::$connector->escape($right)) . ')';
        } else {
            // Por defecto, siempre es un igual
            $right = '=' . Setup::$connector->escape($right);
        }
        return "{$left}{$right}";
    }

    /**
     * Adds a WHERE from an associative array
     */
    private function addFromArray($args)
    {
        $where = [];
        $simple_where = [];

        foreach ($args as $field => $value) {

            /*if ($where) {
                $where .= " AND ";
            }*/

            $where[] = $this->buildElement($field, $value);
            $simple_where[] = $this->buildElement($field, $value, simple: true);
        }

        $this->where_parts[] = [
            $this->next_boolean_opeartor,
            join(' AND ', $where),
            join(' AND ', $simple_where),
        ];
    }

    /**
     * Adds a WHERE from a list of [field, value] arrays
     */
    private function addFromArrayList($arrays)
    {
        $where = [];
        $simple_where = [];

        foreach ($arrays as $element) {
            $where[] = $this->buildElement($element[0], $element[1]);
            $simple_where[] = $this->buildElement(
                $element[0],
                $element[1],
                simple: true
            );
        }

        $this->where_parts[] = [
            $this->next_boolean_opeartor,
            join(' AND ', $where),
            join(' AND ', $simple_where),
        ];

    }

    /**
     * Adds a WHERE to this query
     *
     * - if $args is an associative array, each [field => value] will be joined
     *   with an AND
     * - if $args is not an associative array:
     *    - Array list (must be _only_ arrays in the list):
     *      - Same as [field, value]
     *    - Else
     *      - Match PK with an IN with all elements
     */
    public function where(...$args): self
    {
        // Si es vacío, no procesamos
        if (!$args) {
            return $this;
        }

        $is_associative = $args !== array_values($args);

        if ($is_associative) {
            $this->addFromArray($args);
        } else {
            // Sólo verificamos el 1er elemento
            if (is_array($args[0])) {
                $this->addFromArrayList($args);
            } else {
                if (count($args) == 1) {
                    // Lo igualamos al PK. Doble array por ser array de arrays.
                    $this->addFromArrayList([[
                        ($this->target_class)::getPrimaryKeyField(),
                        $args[0]
                    ]]);
                } else {
                    // Los igualamos al PK con un IN. Doble array por ser array de arrays.
                    $this->addFromArrayList([[
                        ($this->target_class)::getPrimaryKeyField(),
                        $args
                    ]]);
                }
            }
        }

        return $this;
    }

    /**
     * Adds an OR WHERE
     */
    public function or(...$args): self
    {
        $this->next_boolean_opeartor = 'OR';
        $this->where(...$args);

        return $this;
    }

    /**
     * Adds an AND WHERE
     */
    public function and(...$args): self
    {
        $this->next_boolean_opeartor = 'AND';
        $this->where(...$args);

        return $this;
    }

    /**
     * Adds a raw SQL WHERE, replace {variables} with escaped $args
     */
    public function rawWhere($where, ...$args): self
    {
        $replace = [];
        foreach ($args as $variable => $value) {
            $replace['{' . $variable . '}'] = Setup::$connector->escape($value);
        }

        $this->where_parts[] = [
            $this->next_boolean_opeartor,
            strtr($where, $replace),
        ];

        return $this;
    }

    /**
     * Returns the SQL WHERE statement
     */
    public function getSQLWhereString(): string
    {
        $sql = [];

        foreach ($this->where_parts as $part) {
            if ($sql) {
                $sql[] = $part[0];
            }
            $sql[] = "({$part[1]})";
        }

        return join(' ', $sql);
    }

    /**
     * Returns the SQL WHERE without table information
     *
     * @throws DomainException if there is more than one table in this query.
     */
    public function getSimpleSQLWhereString(): string
    {
        if ($this->table_aliases->getTableCount() > 1) {
            throw new DomainException("This operation can't be performed, multiple tables are involved.");
        }
        $sql = [];

        foreach ($this->where_parts as $part) {
            if ($sql) {
                $sql[] = $part[0];
            }
            $sql[] = "({$part[2]})";
        }

        return join(' ', $sql);

    }

}
