<?php

namespace Vendimia\Database\Field;

use Vendimia\Database\{FieldType, Setup, DatabaseReadyValue};

use Attribute;
use InvalidArgumentException;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Char extends FieldAbstract
{
    public function getFieldType(): FieldType
    {
        return FieldType::Char;
    }

    public function __construct(...$args)
    {
        parent::__construct(...$args);

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
