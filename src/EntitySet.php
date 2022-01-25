<?php
namespace Vendimia\Database;

use Vendimia\Database\Driver\Result;
use InvalidArgumentException;
use Iterator;

class EntitySet implements Iterator
{
    // Sequencial index for the iterator
    private $iterator_index = 0;

    // Result from Query
    private $result;

    // Last entity fetched
    private Entity $last_entity;

    //
    private $is_loaded = false;


    public function __construct(
        private $target_class,

        /** Result object for fetching new records */
        //private ?Result $result = null,

        /** Query object for simple queries  */
        private ?Query $query = null,

        /** Constrains for relationships */
        private array $constrains = [],
    )
    {
    }

    /**
     * Creates a query from the constrains, or uses the received Query, and
     * executes it.
     */
    public function load(): self
    {
        if ($this->query) {
            $query = $this->query;
        } else {
            // Tenemos que cambiar la forma de los constrains, por que están
            // en un formato muy simple de field_name => value
            $nc = [];
            foreach ($this->constrains as $field_name => $value) {
                $nc[] = [
                    ($this->target_class)::F($field_name),
                    $value
                ];
            }

            $query = new Query($this->target_class, $nc);
        }

        $this->result = Setup::$connector->execute($query->getSQL());

        $this->is_loaded = true;

        return $this;
    }

    /**
     * Retrieves a record from the database, returns an Entity
     */
    public function fetch(): ?Entity
    {
        if (!$this->is_loaded) {
            $this->load();
        }
        $data = $this->result->fetch();

        if (!$data) {
            return null;
        }

        $entity = new ($this->target_class);
        $entity->fromDatabase($data)->load();

        return $entity;
    }

    /**
     * Returns the entire record set as an array.
     *
     * If $index_field is null, the array index will be the private key value.
     * Otherwise it will be that field's value.
     */
    public function asArray(
        Callable|string $value_field = null,
        Callable|string $index_field = null
    ): array
    {
        $return = [];
        while ($entity = $this->fetch()) {
            if ($value_field) {
                if (is_callable($value_field)) {
                    $value = $value_field($entity);
                } else {
                    $value = $entity->$value_field;
                }
            } else {
                $value = $entity->asArray();
            }
            if ($index_field) {
                if (is_callable($index_field)) {
                    $key = $index_field($entity);
                } else {
                    $key = $entity->$index_field;
                }
            } else {
                $key = $entity->pk();
            }

            $return[$key] = $value;
        }

        return $return;
    }
    /**
     * Appends an Entity to this set.
     */
    public function append(Entity $entity)
    {
        if($entity::class != $this->target_class) {
            throw new InvalidArgumentException("This EntitySet only accepts '{$this->target_class}' entities");
        }

        return $entity->update(...$this->constrains);
    }

    /**
     * Performs an update on all the entities in this set
     */
    public function update(...$payload)
    {
        if ($this->query) {
            $where = $this->query->getSimpleSQLWhereString();
        } else {
            $where = new SimpleWhere(
                $this->target_class,
                $this->constrains
            );
        }

        // Si no hay un query, lo convertimos a null
        if ($where == '') {
            $where = null;
        }

        $update = [];
        foreach ($payload as $field => $value) {
            $update[$this->target_class::F($field)->getFieldName()]
                = $value;
        }

        Setup::$connector->update(
            $this->target_class::getTableName(),
            $update,
            $where
        );
    }

    /**
     * Deletes all the entities in this set
     */
    public function delete()
    {
        if ($this->query) {
            $where = $this->query->getSimpleSQLWhereString();
        } else {
            $where = new SimpleWhere(
                $this->target_class,
                $this->constrains
            );
        }

        Setup::$connector->delete(
            $this->target_class::getTableName(),
            $where
        );
    }

    public function current(): mixed
    {
        return $this->last_entity;
    }

    public function key(): mixed
    {
        return $this->iterator_index;
    }

    public function next(): void
    {

    }

    public function rewind(): void
    {
        // Forzamos a reejecutar el query
        $this->result?->free();
        $this->load();
    }

    public function valid(): bool
    {
        $entity = $this->fetch();

        if (is_null($entity)) {
            return false;
        }

        $this->last_entity = $entity;
        $this->iterator_index++;
        return true;
    }
}
