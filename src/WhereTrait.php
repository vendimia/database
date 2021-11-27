<?php
namespace Vendimia\Database;

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
     * A sucession of arrays what will be AND-joined and enclosed in parenthesis.
     * Each array is in itself an array of complete query string
     */
    public $where_parts = [];

    /** Boolean joint for next 'where' */
    public $where_joint = 'AND';

    /**
     * Builds a simple comparison element
     */
    public function buildElement($left, $right)
    {
        if ($left instanceof Field\FieldAbstract) {
            $left = $this->table_aliases->getFullFieldName(
                ...$left->getFullFieldName()
            );
        } else {
            $left = $this->table_aliases->getFullFieldName(
                //$this->target_class::getTableName(),
                ...$this->target_class::F($left)->getFullFieldName(),
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
        $where = '';

        foreach ($args as $field => $value) {

            if ($where) {
                $where .= " AND ";
            }

            $where .= $this->buildElement($field, $value);
        }

        $this->where_parts[] = [
            $this->where_joint,
            $where,
        ];
    }

    /**
     * Adds a WHERE from a list of [field, value] arrays
     */
    private function addFromArrayList($arrays)
    {
        $where = '';

        foreach ($arrays as $element) {

            if ($where) {
                $where .= " AND ";
            }

            $where .= $this->buildElement($element[0], $element[1]);
        }

        $this->where_parts[] = [
            $this->where_joint,
            $where,
        ];

    }

    /**
     * Adds a WHERE to this query
     *
     * - if $args is an associative array, each [field => value] will be joined
     *   with an AND
     * - if $args is not an associative array:
     *    - Array list:
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
        $this->where_joint = 'OR';
        $this->where(...$args);

        return $this;
    }

    /**
     * Adds an AND WHERE
     */
    public function and(...$args): self
    {
        $this->where_joint = 'AND';
        $this->where(...$args);

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
}
