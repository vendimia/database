<?php
namespace Vendimia\Database\Field;

use Attribute;
use InvalidArgumentException;
use Vendimia\Database\FieldType;

#[Attribute(Attribute::TARGET_PROPERTY)]
class MediumText extends FieldAbstract
{
    public static function getFieldType(): FieldType
    {
        return FieldType::MediumText;
    }

}
