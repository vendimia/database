<?php
namespace Vendimia\Database\Field;

use Attribute;
use InvalidArgumentException;
use Vendimia\Database\FieldType;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Time extends FieldAbstract
{
    public function getFieldType(): FieldType
    {
        return FieldType::Time;
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
            throw new InvalidArgumentException("Value for field '{$this->name}' must be an string, or a DateTime (PHP or Vendimia) object");
        }

        return $value;
    }

    public function processDatabaseValue($value)
    {
        $result = parent::processDatabaseValue($value);

        // Si $this->properties['class_value'] tiene valor, el resultado ya
        // es un objeto.
        if (!is_object($result)) {
            if (class_exists(\Vendimia\DateTime\Time::class)) {
                return new \Vendimia\DateTime\Time($result);
            }
        }
    }
}
