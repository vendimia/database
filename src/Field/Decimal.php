<?php
namespace Vendimia\Database\Field;

use Attribute;
use InvalidArgumentException;
use Vendimia\Database\FieldType;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Decimal extends FieldAbstract
{
    public function getFieldType(): FieldType
    {
        return FieldType::Decimal;
    }

    public function __construct(...$args)
    {
        parent::__construct(...$args);

        if (!$this->properties['length']) {
            throw new InvalidArgumentException("Field '{$this->name}' of type 'Decimal' requires a length (precision)");
        }

        if (!$this->properties['decimal']) {
            throw new InvalidArgumentException("Field '{$this->name}' of type 'Decimal' requires the decimal count (scale)");
        }
    }

    public function processPHPValue($value)
    {
        $value = parent::processPHPValue($value);

        if (!is_numeric($value) && !is_null($value)) {
            throw new InvalidArgumentException("Value for field '{$this->name}' must be numeric");
        }

        return is_null($value) ? null : floatval($value);
    }

    public function processDatabaseValue($value)
    {
        $value = parent::processDatabaseValue($value);

        // Si viene un objeto, o es null, lo retornamos tal cual.
        if (is_object($value) || is_null($value)) {
            return $value;
        }

        // Retornamos el valor como float
        return floatval($value);
    }
}
