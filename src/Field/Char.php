<?php
namespace Vendimia\Database\Field;

use Attribute;
use InvalidArgumentException;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Char extends FieldAbstract
{
    public function __construct(...$args)
    {
        parent::__construct(...$args);

        if (!$this->properties['length']) {
            throw new InvalidArgumentException("Field '{$this->name}' of type 'Char' requires a length");
        }
    }
}
