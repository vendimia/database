<?php
namespace Vendimia\Database\Field;

use Attribute;
use InvalidArgumentException;
use Vendimia\Database\FieldType;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Integer extends FieldAbstract
{
    public function getFieldType(): FieldType
    {
        return FieldType::Integer;
    }

    public function processPHPValue($value)
    {
        $value = parent::processPHPValue($value);

        if (!is_numeric($value) && !is_null($value)) {
            throw new InvalidArgumentException("Value for field '{$this->name}' must be an integer");
        }

        return is_null($value) ? null : intval($value);
    }

    public function processDatabaseValue($value)
    {
        $result = parent::processDatabaseValue($value);

        if (!is_object($result)) {
            return intval($value);
        }
    }
}
