<?php
namespace Vendimia\Database\Field;

use Attribute;
use InvalidArgumentException;
use Vendimia\Database\FieldType;

#[Attribute(Attribute::TARGET_PROPERTY)]
class ForeignKey extends FieldAbstract
{
    public static function getFieldType(): FieldType
    {
        return FieldType::ForeignKey;
    }

    protected $extra_properties = [
        // Target Entity
        'entity' => null,

        // This entity can be null
        'null' => true,
    ];

    public function __construct(...$args)
    {
        parent::__construct(...$args);

        // La clase objetivo es el 1er parámetro, que está en 'length'.
        // Cambiamos el nombre del campo
        $this->properties['entity'] ??= $this->properties['length'] ?? null;
        if (!$this->properties['entity']) {
            throw new InvalidArgumentException("Field '{$this->name}' of type 'ForeignKey' requires a target Entity");
        }
    }

    public function getFieldName(): string
    {
        return $this->name . '_id';
    }

    public function processPHPValue($value)
    {
        $value = parent::processPHPValue($value);

        $valid_object = is_object($value) && get_class($value) == $this->properties['entity'];

        if (!is_numeric($value) && !is_null($value) && !$valid_object) {
            throw new InvalidArgumentException("Value for field '{$this->name}' must be an Entity of class '{$this->properties['entity']}, or an integer'");
        }

        if ($valid_object) {
            return $value->pk();
        } else {
            return intval($value);
        }

    }
}
