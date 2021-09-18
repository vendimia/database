<?php
namespace Vendimia\Database\Field;

use Attribute;
use InvalidArgumentException;
use Vendimia\Database\FieldType;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Boolean extends FieldAbstract
{
    public function getFieldType(): FieldType
    {
        return FieldType::BOOLEAN;
    }

    public function processPHPValue($value)
    {
        $value = parent::processPHPValue($value);

        return is_null($value) ? null : boolval($value);
    }

}
