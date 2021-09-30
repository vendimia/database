<?php
namespace Vendimia\Database\Field;

use Attribute;
use InvalidArgumentException;
use Vendimia\Database\EntitySet;
use Vendimia\Database\FieldType;

#[Attribute(Attribute::TARGET_PROPERTY)]
class OneToMany extends FieldAbstract
{
    protected $extra_properties = [
        // Target Entity
        'entity' => null,

        // Field in target entity linked to this Entity local key
        // Default $target_entity->getName()
        'foreign_key' => null,

        // Field in this entity for linking. Defaults to the primary key.
        'local_key' => null,
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

        $this->properties['foreign_key'] ??= ($this->entity_class)::getName();
        $this->properties['local_key'] ??= ($this->entity_class)::getPrimaryKeyField()->getName();
    }

    public function getFieldType(): ?FieldType
    {
        return null;
    }

    public function getFieldName(): ?string
    {
        return null;
    }

    public function requirePostProc(): bool
    {
        return true;
    }

    /**
     * Creates an EntitySet in the entity property
     */
    public function postProc(): void
    {
        $target_entity = $this->properties['entity'];
        $this->entity->{$this->name} = new EntitySet(
            $target_entity,
            constrains: [
                $this->properties['foreign_key'] =>
                    $this->entity->{$this->properties['local_key']}
            ]
        );
    }
}
