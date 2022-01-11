<?php
namespace Vendimia\Database;

use Vendimia\Database\Driver\ConnectorInterface;
use BadMethodCallException;

/**
 * Class to create more elaborated SQL comparisons.
 */
class Comparison
{
    private ConnectorInterface $connector;

    /**
     *
     */
    public function __construct(
        private $function,
        private $params,
        private $not
    ) {
        $this->connector = Setup::$connector;
    }

    /** Some simple comparison aliases */
    private $comparisons = [
        'ne' => '!=',
        'lt' => '<',
        'less' => '<',
        'lte' => '<=',
        'lessequal' => '<=',
        'gt' => '>',
        'greater' => '>',
        'gte' => '>=',
        'greaterequal' => '>=',
        'like' => ' LIKE '
    ];

    /**
     * Alias to LIKE 'data%'
     */
    private function comparisonStartsWith($params) {
        $sql = ' ';
        if ($this->not) {
            $sql .= 'NOT ';
        }
        $sql .= 'LIKE ' . $this->connector->escape($params[0] . '%');

        return $sql;
    }

    /**
     * Alias to LIKE '%data'
     */
    private function comparisonEndsWith($params) {
        $sql = ' ';
        if ($this->not) {
            $sql .= 'NOT ';
        }
        $sql .= 'LIKE ' . $this->connector->escape('%' . $params[0]);

        return $sql;
    }

    /**
     * Alias to LIKE '%data%'
     */
    private function comparisonContains($params) {
        $sql = ' ';
        if ($this->not) {
            $sql .= 'NOT ';
        }
        $sql .= 'LIKE ' . $this->connector->escape('%' . $params[0] . '%');

        return $sql;
    }

    /**
     * Used for comparing with booleans or null
     */
    private function comparisonIs($params)
    {

        $sql = ' IS ';
        if ($this->not) {
            $sql .= 'NOT ';
        }
        $sql .= $this->connector->escape($params[0]);

        return $sql;
    }

    /**
     * Used for comparing against NULL
     */
    private function comparisonNull()
    {
        $sql = ' IS ';
        if ($this->not) {
            $sql .= 'NOT ';
        }
        $sql .= 'NULL';

        return $sql;
    }

    /**
     * Alias of comparisonNull()
     */
    private function comparisonIsNull()
    {
        return $this->comparisonNull();
    }


    /**
     * Use the IN SQL keyword
     */
    private function comparisonIn($params)
    {
        $sql = ' ';
        if ($this->not) {
            $sql .= 'NOT ';
        }
        $sql .= 'IN (' . join(', ', $this->connector->escape($params)) . ')';

        return $sql;
    }

    /**
     * Use the BETWEEN SQL keyword
     */
    private function comparisonBetween($params)
    {
        $sql = '';
        if ($this->not) {
            $sql = ' NOT';
        }

        $sql .= ' BETWEEN ' . $this->connector->escape($params[0]);
        $sql .= ' AND '. $this->connector->escape($params[1]);

        return $sql;
    }


    /**
     * Magic static method for create instances of this object
     */
    public static function __callStatic($method, $args) {
        // Estamos negando?
        $not = false;

        $method = strtolower($method);
        if (substr($method,0, 3) == 'not') {
            $not = true;
            $method = substr($method, 3);

            if ($method == '') {
                $not = false;
                $method = 'ne';
            }
        }

        return new self($method, $args, $not);
    }

    /**
     * Returns the escaped value
     */
     public function getValue()
    {
        if (key_exists($this->function, $this->comparisons)) {
            return $this->comparisons[$this->function] .
                $this->connector->escape($this->params[0]);
        } else {
            $method = 'comparison' . $this->function;
            if(method_exists($this, $method)) {
                return $this->$method($this->params);
            } else {
                throw new BadMethodCallException("'{$this->function}' is not a valid comparison method.");
            }
        }
    }
}
