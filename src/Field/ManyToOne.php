<?php
namespace Vendimia\Database\Field;

use Vendimia\Database\FieldType;
use Vendimia\Database\Entity;

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
        'foreign_key' => 'id',
    ];

    public static function getFieldType(): FieldType
    {
        return FieldType::Integer;
    }

    public function __construct(...$args)
    {
        parent::__construct(...$args);

        // Si hay un argumento posicional, lo usamos
        if (isset($this->positional_arguments[0])) {
            $this->properties['entity'] = $this->positional_arguments[0];
        }

        if (!$this->properties['entity']) {
            throw new InvalidArgumentException(
                "{$this->entity_class}: Field '{$this->name}' of type 'ManyToOne' requires a target Entity");
        }
    }

    public function getFieldName(): string
    {
        return $this->properties['database_field'] ?? $this->name . '_id';
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
        // Si el valor es null o no ha sido inicializada, no hacemos nada
        if (is_null($value = $this->entity->{$this->name} ?? null)) {
            return;
        }

        // Si el valor ya tiene una entidad, no hacemos nada
        if ($value instanceof Entity) {
            return;
        }

        $entity = $this->properties['entity'];
        $this->entity->{$this->name} = $entity::lazyGet(
            ...[$this->properties['foreign_key'] => $value]
        );
    }
}
