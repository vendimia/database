<?php
namespace Vendimia\Database\Field;

use Vendimia\Database\Entity;
use InvalidArgumentException;

abstract class FieldAbstract implements FieldInterface
{
    protected ?Entity $entity = null;
    protected $value;

    protected $properties = [
        // Required length for some values.
        'length' => null,

        // Decimal places for the float, required for some values
        'decimal' => null,

        // Value can be null?
        'null' => false,

        // This field should be indexed?
        'index' => false,

        // Class to be used as value return.
        'class_value' => null,

        // Valid possible values for this field. Used when creating Enums
        'valid_values' => [],

        // Database field for this entity. Default is this entity name.
        'database_field' => null,

        /** Methods in this entity for processing values from and to the database, in the form of [from_method, to_method] */
        'value_processing_methods' => null,
    ];

    public function __construct(
        protected $name,
        protected $entity_class,
        array $args,
        protected $comment = '',
    )
    {

        // Mezclamos las propiedades particulares de cada Field
        if (isset($this->extra_properties)) {
            $this->properties = array_merge(
                $this->properties,
                $this->extra_properties
            );
        }

        // Índices 0 y 1 tienen significado especial

        if (isset($args[0])) {
            // El índice 0 puede tener la longitud del campo, o la clase destino
            // de ciertas relaciones foráneas
            $property = array_shift($args);
            if (is_numeric($property)) {
                $this->properties['length'] = intval($property);
            } else {
                $this->properties['entity'] = $property;
            }
        }

        // Si sigue insistiendo un índice 0, son los decimales
        if (isset($args[0])) {
            $this->properties['decimal'] = intval(array_shift($args));
        }

        $this->properties = array_merge(
            $this->properties,
            $args,
        );
    }

    /**
     * Returns this database field name. Null disables this Field to have one
     * in the database.
     */
    public function getFieldName(): ?string
    {
        return $this->properties['database_field'] ?? $this->name;
    }

    /**
     * Returns this field name
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Returns the DocBlock of the property
     */
    public function getComment(): string
    {
        return $this->comment;
    }

    /**
     * Returns whether this field has a property setted
     */
    public function hasProperty(string $property): bool
    {
        return key_exists($property, $this->properties);
    }


    /**
     * Returns a property, returns $default if property doesn't exists.
     */
    public function getProperty($property, $default = null)
    {
        return $this->properties[$property] ?? $default;
    }

    /**
     * Sets this field entity owner
     */
    public function setEntity(Entity $entity): self
    {
        $this->entity = $entity;
        $this->entity_class = $entity::class;

        return $this;
    }

    public function getEntity(): Entity
    {
        return $this->entity;
    }

    /**
     * Returns an array with [table, field_name]
     */
    public function getFullFieldName(): array
    {
        return [
            ($this->entity_class)::getTableName(),
            $this->getFieldName(),
        ];
    }

    /**
     * Process and validates a PHP value, should returns a database-aware value.
     */
    public function processPHPValue($value)
    {
        if ($method = $this->properties['value_processing_methods'][1] ?? null) {
            if (method_exists($this->entity, $method)) {
                $value = $this->entity->$method($value);
            }
        }

        if (!$this->properties['null'] && is_null($value)) {
            throw new InvalidArgumentException("Value for {$this->entity_class}::{$this->name} cannot be null");
        }

        return $value;
    }

    /**
     * Converts a database value to PHP value.
     */
    public function processDatabaseValue($value)
    {
        if ($method = $this->properties['value_processing_methods'][0] ?? null) {
            if (method_exists($this->entity, $method)) {
                return $this->entity->$method($value);
            }
        }

        return $value;
    }

    /**
     * Returns true if this fields requires execution of self::postProc() after
     * the fields has been filled.
     */
    public function requirePostProc(): bool
    {
        return false;
    }

    public function postProc(): void
    {

    }
}
