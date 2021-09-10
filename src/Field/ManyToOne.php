<?php
namespace Vendimia\Database\Field;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class ManyToOne extends FieldAbstract
{
    protected $extra_properties = [
        // Target Entity
        'entity' => null,

        // Field in target entity linked to this Entity primary key.
        // Default $entity->getName()
        'fk_field' => 'id',

    ];

    public function __construct(...$args)
    {
        parent::__construct(...$args);

        // La clase objetivo es el 1er parámetro, que está en 'length'.
        // Cambiamos el nombre del campo
        $this->properties['entity'] ??= $this->properties['length'] ?? null;
        if (!$this->properties['entity']) {
            throw new InvalidArgumentException("Field '{$this->name}' of type 'OneToMany' requires a target Entity");
        }
    }

    public function getFieldName(): string
    {
        return $this->name . '_id';
    }

    public function requirePostProc(): bool
    {
        return true;
    }

    /**
     * Uses the pre-fetched field as ID value to create an entity
     */
    public function postProc()
    {
        $entity = $this->properties['entity'];
        $this->entity->{$this->name} = $entity::get(
            ...[$entity = $this->properties['fk_field'] => $this->entity->{$this->name}]
        );
    }
}
