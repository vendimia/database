<?php
namespace Vendimia\Database\Field;

use Attribute;
use InvalidArgumentException;
use Vendimia\Database\FieldType;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Enum extends FieldAbstract
{
    public function getFieldType(): FieldType
    {
        return FieldType::Enum;
    }

    public function __construct(...$args)
    {
        parent::__construct(...$args);

        // Si hay un argumento posicional, lo usamos
        if (isset($this->positional_arguments[0])) {
            $this->properties['valid_values'] = $this->positional_arguments[0];
        }

        if (!$this->properties['valid_values']) {
            throw new InvalidArgumentException("Field '{$this->name}' of type 'Enum' requires a 'valid_values' list");
        }
    }
}
