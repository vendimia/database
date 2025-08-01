<?php

namespace Vendimia\Database\Field;

use Vendimia\Database\{FieldType, Setup, DatabaseReadyValue};

use Attribute;
use InvalidArgumentException;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Char extends FieldAbstract
{
    public static function getFieldType(): FieldType
    {
        return FieldType::Char;
    }

    public function __construct(...$args)
    {
        parent::__construct(...$args);

        // Si hay un argumento posicional, lo usamos
        if (isset($this->positional_arguments[0])) {
            $this->properties['length'] = $this->positional_arguments[0];
        }

        if (!$this->properties['length']) {
            throw new InvalidArgumentException("Field '{$this->name}' of type 'Char' requires a length");
        }
    }

    /**
     * Pre-quote and escape the value
     */
    public function processPHPValue($value)
    {

        $value = parent::processPHPValue($value);

        if (!is_null($value)) {
            return new DatabaseReadyValue(
                Setup::getConnector()->nativeEscapeString($value, quoted: true)
            );
        }
    }
}
