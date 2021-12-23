<?php
namespace Vendimia\Database\Field;

use Attribute;
use InvalidArgumentException;
use Vendimia\Database\FieldType;

#[Attribute(Attribute::TARGET_PROPERTY)]
class DateTime extends FieldAbstract
{
    public function getFieldType(): FieldType
    {
        return FieldType::DateTime;
    }

    public function processPHPValue($value)
    {
        $value = parent::processPHPValue($value);

        $ok = false;
        if (is_string($value) || is_null($value)) {
            $ok = true;
        } elseif (is_object($value)) {
            // Sólo permitimos dos tipos de objetos
            if ($value instanceof \Vendimia\DateTime\DateTime) {
                if ($value->isNull()) {
                    $value = null;
                } else {
                    $value = $value->format('Y-m-d H:i:s');
                }
                $ok = true;
            } elseif ($value instanceof \DateTime) {
                $value = $value->format('Y-m-d H:i:s');
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
        $value = parent::processDatabaseValue($value);

        // Si viene un objeto, o es null, lo retornamos tal cual.
        if (is_object($value) || is_null($value)) {
            return $value;
        }

        // Si existe Vendima\DateTime\DateTime, retornamos una instancia de él
        if (class_exists(\Vendimia\DateTime\DateTime::class)) {
            return new \Vendimia\DateTime\DateTime($value);
        }

        return $value;
    }
}
