<?php
namespace Vendimia\Database\Field;

use Attribute;
use BackedEnum;
use InvalidArgumentException;
use Vendimia\Database\FieldType;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Enum extends FieldAbstract
{
    public static function getFieldType(): FieldType
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

        // Sólo permitirmos arrays o BackedEnums
        if (!is_array($this->properties['valid_values'])
            && !$this->properties['valid_values'] instanceof BackedEnum) {

            throw new InvalidArgumentException("valid_values must be an array or a Backed Enum, not " . gettype($this->properties['valid_values']));

        }
    }

    /**
     * if valid_values has an backed Enum, returns the Enum element
     */
    public function processDatabaseValue($value)
    {
        if ($this->properties['valid_values'] instanceof BackedEnum) {
            return $this->properties['valid_values']::from($value);
        }

        return parent::processDatabaseValue($value);
    }
}
