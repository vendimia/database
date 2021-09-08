<?php
namespace Vendimia\Database\Field;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class ManyToOne extends FieldAbstract
{
    public function getFieldName(): string
    {
        return $this->name . '_id';
    }
}
