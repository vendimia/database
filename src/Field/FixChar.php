<?php
namespace Vendimia\Database\Field;

use Attribute;
use InvalidArgumentException;
use Vendimia\Database\FieldType;

#[Attribute(Attribute::TARGET_PROPERTY)]
class FixChar extends FieldAbstract
{
    public function getFieldType(): FieldType
    {
        return FieldType::FixChar;
    }

    public function __construct(...$args)
    {
        parent::__construct(...$args);

        if (!$this->properties['length']) {
            throw new InvalidArgumentException("Field '{$this->name}' of type 'FixChar' requires a length");
        }
    }
}
