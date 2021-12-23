<?php
namespace Vendimia\Database\Field;

use Attribute;
use InvalidArgumentException;
use Vendimia\Database\FieldType;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Json extends FieldAbstract
{
    public function getFieldType(): FieldType
    {
        return FieldType::JSON;
    }

    public function __construct(...$args)
    {
        parent::__construct(...$args);
    }

    public function processPHPValue($value)
    {
        $value = parent::processPHPValue($value);

        if (!is_array($value) && !is_null($value)) {
            throw new InvalidArgumentException("Value for field '{$this->name}' must be an array");
        }

        return json_encode($value);
    }

    public function processDatabaseValue($value)
    {
        $result = parent::processDatabaseValue($value);

        // Si viene un objeto, o es null, lo retornamos tal cual.
        if (is_object($value) || is_null($value)) {
            return $value;
        }

        return json_decode($result);
    }

}
