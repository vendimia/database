<?php
namespace Vendimia\Database\Field;

use Attribute;
use Vendimia\Database\FieldType;

#[Attribute(Attribute::TARGET_PROPERTY)]
class BigInt extends Integer
{
    public function getFieldType(): FieldType
    {
        return FieldType::BigInt;
    }
}