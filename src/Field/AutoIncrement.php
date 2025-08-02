<?php
namespace Vendimia\Database\Field;

use Attribute;
use InvalidArgumentException;
use Vendimia\Database\FieldType;

#[Attribute(Attribute::TARGET_PROPERTY)]
class AutoIncrement extends Integer
{
    public static function getFieldType(): FieldType
    {
        return FieldType::AutoIncrement;
    }
}
