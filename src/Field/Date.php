<?php
namespace Vendimia\Database\Field;

use Attribute;
use InvalidArgumentException;
use Vendimia\Database\FieldType;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Date extends FieldAbstract
{
    public function getFieldType(): FieldType
    {
        return FieldType::Date;
    }

    public function processPHPValue($value)
    {
        $value = parent::processPHPValue($value);

        $ok = false;
        if (is_string($value) || is_null($value)) {
            $ok = true;
        } elseif (is_object($value)) {
            // Sólo permitimos dos tipos de objetos
            if ($value instanceof \DateTime ||
                $value instanceof \Vendimia\DateTime\DateTime
            ) {
                $value = $value->format('Y-m-d');
                $ok = true;
            }
        }

        if (!$ok) {
            $type = gettype($value);
            if ($type == 'object')  {
                $type = 'object:' . $value::class;
            }
            throw new InvalidArgumentException("Value for field '{$this->name}' must be a date string or a DateTime (PHP or Vendimia) object, got '{$type}' instead");
        }

        return $value;
    }

    public function processDatabaseValue($value)
    {
        $result = parent::processDatabaseValue($value);

        // Si $this->properties['class_value'] tiene valor, el resultado ya
        // es un objeto.
        if (!is_object($result)) {
            if (class_exists(\Vendimia\DateTime\Date::class)) {
                return new \Vendimia\DateTime\Date($result);
            }
        }
    }
}
