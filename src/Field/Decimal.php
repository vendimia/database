<?php
namespace Vendimia\Database\Field;

use Vendimia\Database\{
    FieldType,
    DatabaseReadyValue
};
use Attribute;
use DomainException;


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

        // Si hay dos argumentos posicionales, los usamos
        if (isset($this->positional_arguments[0])) {
            $this->properties['length'] = $this->positional_arguments[0];
        }
        if (isset($this->positional_arguments[1])) {
            $this->properties['decimal'] = $this->positional_arguments[1];
        }

        if (!$this->properties['length']) {
            throw new DomainException("Field '{$this->name}' of type 'Decimal' requires a length (precision)");
        }

        if (!$this->properties['decimal']) {
            throw new DomainException("Field '{$this->name}' of type 'Decimal' requires the decimal count (scale)");
        }
    }

    public function processPHPValue($value)
    {
        $value = parent::processPHPValue($value);

        if (!is_numeric($value) && !is_null($value)) {
            throw new DomainException("Value for field '{$this->name}' must be numeric");
        }

        // Retornamos el valor como float, y evitamos que sea luego automáticamente
        // escapado (para permitir cosas como 'e')
        return is_null($value) ? null : new DatabaseReadyValue(
            floatval($value)
        );
    }

    public function processDatabaseValue($value)
    {
        $value = parent::processDatabaseValue($value);

        // Si viene un objeto, o es null, lo retornamos tal cual.
        if (is_object($value) || is_null($value)) {
            return $value;
        }

        return floatval($value);
    }
}
