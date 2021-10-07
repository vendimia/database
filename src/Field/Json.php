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

        // Si $this->properties['class_value'] tiene valor, el resultado ya
        // es un objeto.
        if (!is_object($result)) {
            return json_decode($result);
        }
    }

}
