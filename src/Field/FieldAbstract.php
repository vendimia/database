<?php
namespace Vendimia\Database\Field;

use Vendimia\Database\Entity;
use InvalidArgumentException;

abstract class FieldAbstract
{
    protected ?Entity $entity = null;
    protected $value;

    protected $properties = [
        // Required length for some values.
        'length' => null,

        // Decimal places for the float, required for some values
        'decimals' => null,

        // Value can be null?
        'null' => false,

        // This field should be indexed?
        'index' => false,

        // Class to be used as value return.
        'class_value' => null,

        // Valid possible values for this field. Used when creating Enums
        'valid_values' => [],
    ];

    public function __construct(
        protected $name,
        protected $entity_class,
        array $args
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
            $this->properties['length'] = array_shift($args);
        }
        // Si sigue insistiendo un índice 0, son los decimales
        if (isset($args[0])) {
            $this->properties['decimals'] = array_shift($args);
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
        return $this->name;
    }

    /**
     * Returns this field name
     */
    public function getName(): string
    {
        return $this->name;
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
        if (is_null($value) && key_exists('default', $this->properties)) {
            $value = $this->properties['default'];
        }

        if (!$this->properties['null'] && is_null($value)) {
            throw new InvalidArgumentException("Value for field '{$this->name}' cannot be null");
        }

        return $value;
    }

    /**
     * Converts a database value to PHP value.
     */
    public function processDatabaseValue($value)
    {
        if ($class = $this->properties['class_value']) {
            return new $class($value);
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

    public function postProc()
    {

    }
}
