<?php
namespace Vendimia\Database\Field;

use Attribute;
use InvalidArgumentException;

#[Attribute(Attribute::TARGET_PROPERTY)]
class DateTime extends FieldAbstract
{
    public function processPHPValue($value)
    {
        $value = parent::processPHPValue($value);

        if (is_string($value)) {
            $ok = true;
        } elseif (is_object($value)) {
            // Sólo permitimos dos tipos de objetos
            if ($value instanceof \DateTime ||
                $value instanceof \Vendimia\DateTime\DateTime
            ) {
                $value = $value->format('Y-m-d H:i:s');
                $ok = true;
            }
        }

        if (!$ok) {
            throw new InvalidArgumentException("Value for field '{$this->name}' must be an string, or a DateTime (PHP or Vendimia) object");
        }

        return $value;
    }
}
