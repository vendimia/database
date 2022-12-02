<?php

namespace Vendimia\Database\Field;

use Vendimia\Database\{FieldType, Setup, DatabaseReadyValue};

use Attribute;
use InvalidArgumentException;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Text extends FieldAbstract
{
    public function getFieldType(): FieldType
    {
        return FieldType::Text;
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
