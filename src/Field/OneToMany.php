<?php
namespace Vendimia\Database\Field;

use Attribute;
use InvalidArgumentException;
use Vendimia\Database\EntitySet;

#[Attribute(Attribute::TARGET_PROPERTY)]
class OneToMany extends FieldAbstract
{
    protected $extra_properties = [
        // Target Entity
        'entity' => null,

        // Field in target entity linked to this Entity primary key.
        // Default $target_entity->getName()
        'foreign_key' => null,

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
    public function postProc()
    {
        $this->entity->{$this->name} = new EntitySet(
            $this->properties['entity'],
            constrains: [
                /*[
                    ($this->properties['entity'])::F($this->properties['foreign_key']),
                    $this->entity
                ]*/
                $this->properties['foreign_key'] => $this->entity->pk()
            ]
        );
    }
}
