<?php
namespace Vendimia\Database\Field;

use Attribute;
use InvalidArgumentException;
use Vendimia\Database\FieldType;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Integer extends FieldAbstract
{
    public static function getFieldType(): FieldType
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
        $value = parent::processDatabaseValue($value);

        // Si viene un objeto, o es null, lo retornamos tal cual.
        if (is_object($value) || is_null($value)) {
            return $value;
        }

        // Retornamos el valor como integer
        return intval($value);
    }
}
