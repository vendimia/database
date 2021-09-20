<?php
namespace Vendimia\Database\Field;

use Attribute;
use InvalidArgumentException;
use Vendimia\Database\FieldType;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Enum extends FieldAbstract
{
    public function getFieldType(): FieldType
    {
        return FieldType::Enum;
    }

    public function __construct(...$args)
    {
        parent::__construct(...$args);

        if (!$this->properties['valid_values']) {
            throw new InvalidArgumentException("Field '{$this->name}' of type 'Enum' requires a 'valid_value' list");
        }
    }
}
