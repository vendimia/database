<?php
namespace Vendimia\Database;

use DomainException;
use Closure;

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
     * QUERY can be a list with more queries, which will be parenthesized.
     *
     * SIMPLE-QUERY is the query without table information, used when deleting
     * entity sets
     */
    public $where_parts = [];

    /** Boolean operator for next 'where' */
    public $next_boolean_operator = 'AND';

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
            $this->next_boolean_operator,
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
            $this->next_boolean_operator,
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

        if (array_is_list($args)) {
            // Si es una lista, entonces puede ser:
            // - Una lista de arrays, cada uno con una consulta de formato
            //   [campo, valor]. En una lista de arrays sólo puede haber
            //   consultas en este formato.
            // - Un único valor (count(args) == 1). Esto crea una consulta
            //   comparándolo directamente con el pk.
            // - Varios valores. Esto conpara el pk usando un IN

            // Sólo verificamos el 1er elemento para determinar que es una lista
            // de arrays.
            if (is_array($args[0])) {
                $this->addFromArrayList($args);
            } else {
                if (count($args) == 1) {
                    // Lo igualamos al PK. Creamos una lista de arrays con un
                    // único término
                    $this->addFromArrayList([
                        [($this->target_class)::getPrimaryKeyField(),$args[0]]
                    ]);
                } else {
                    // Los igualamos al PK con un IN. Creamos una lista de
                    // arrays con un único término
                    $this->addFromArrayList([
                        [($this->target_class)::getPrimaryKeyField(),$args]
                    ]);
                }
            }
        } else {
            // No es una lista, es un array asociativo con [campo => valor]
            $this->addFromArray($args);
        }/**/

        return $this;
    }

    /**
     * Adds an OR WHERE
     */
    public function or(...$args): self
    {
        $this->next_boolean_operator = 'OR';
        $this->where(...$args);

        return $this;
    }

    /**
     * Adds an AND WHERE
     */
    public function and(...$args): self
    {
        $this->next_boolean_operator = 'AND';
        $this->where(...$args);

        return $this;
    }

    /**
     * Adds a grouped WHERE inside a parenthesis
     */
    public function group(Closure $closure): self
    {
        // Cambiamos el lugar donde guardar temporalmente las parte del WHERE

        // Guardamos las partes WHERE y el $next_boolean_operator
        $stashed_where_parts = $this->where_parts;
        $stashed_next_boolean_operator = $this->next_boolean_operator;

        // Y las regresamos a cero
        $this->where_parts = [];
        $this->next_boolean_operator = 'AND';

        // Ejecutamos el closure
        $closure($this);

        // Guardamos las partes WHERE añadidas en el closure
        $grouped_where_parts = $this->where_parts;

        // Regresamos las partes WHERE guardadas
        $this->where_parts = $stashed_where_parts;
        $this->next_boolean_operator = $stashed_next_boolean_operator;

        // Y le añadimos las que hemos obtenido del closure
        if ($grouped_where_parts) {
            $this->where_parts[] = [
                $this->next_boolean_operator,
                $grouped_where_parts,
            ];
        }


        return $this;
    }

    /**
     * Adds a raw SQL WHERE, parsing variables with the connector
     */
    public function rawWhere($where, ...$args): self
    {
        $this->where_parts[] = [
            $this->next_boolean_operator,
            Setup::$connector->prepare($where, ...$args),
        ];

        return $this;
    }

    /**
     * Returns the SQL WHERE statement
     */
    public function getSQLWhereString(?array $parts = null): string
    {
        $sql = [];

        if (is_null($parts)) {
            $parts = $this->where_parts;
        }

        // 1 = Full table-prefixed fields
        // 2 = Simple field name
        $part_field_index = 1;

        foreach ($parts as $part) {
            if ($sql) {
                $sql[] = $part[0];
            }

            // Si $part[1] es un array, hacemos esto recursivamente
            if (is_array($part[1])) {
                $sql[] = '(' . $this->getSQLWhereString($part[$part_field_index]) . ')';
            } else {
                $sql[] = $part[$part_field_index];

            }
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
            $sql[] = $part[2];
        }

        return join(' ', $sql);

    }

}
