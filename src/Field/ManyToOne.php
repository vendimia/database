<?php
namespace Vendimia\Database\Field;

use Vendimia\Database\FieldType;
use Attribute;
use InvalidArgumentException;

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

    public function getFieldType(): FieldType
    {
        return FieldType::Integer;
    }

    public function __construct(...$args)
    {
        parent::__construct(...$args);

        if (!$this->properties['entity']) {
            throw new InvalidArgumentException(
                "{$this->entity_class}: Field '{$this->name}' of type 'ManyToOne' requires a target Entity");
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
    public function postProc(): void
    {
        $entity = $this->properties['entity'];
        $this->entity->{$this->name} = $entity::get(
            ...[$entity = $this->properties['fk_field'] => $this->entity->{$this->name}]
        );
    }
}
