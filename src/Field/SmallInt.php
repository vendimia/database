<?php
namespace Vendimia\Database\Field;

use Attribute;
use InvalidArgumentException;
use Vendimia\Database\FieldType;

#[Attribute(Attribute::TARGET_PROPERTY)]
class SmallInt extends Integer
{
    public function getFieldType(): FieldType
    {
        return FieldType::SmallInt;
    }
}