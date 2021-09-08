<?php
namespace Vendimia\Database\Field;

use Attribute;
use InvalidArgumentException;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Integer extends FieldAbstract
{
    public function processPHPValue($value)
    {
        $value = parent::processPHPValue($value);

        if (!is_numeric($value) && !is_null($value)) {
            throw new InvalidArgumentException("Value for field '{$this->name}' must be an integer");
        }

        return is_null($value) ? null : intval($value);
    }
}
