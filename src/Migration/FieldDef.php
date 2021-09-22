<?php
namespace Vendimia\Database\Migration;

use Vendimia\Database\FieldType;
use Vendimia\DataContainer\DataContainer;

/**
 * Database field definition parameters
 */
class FieldDef extends DataContainer
{
    public string $name;
    public FieldType $type;

    /** Lenght for Char, FixChar and Decimal (precision) fields*/
    public ?int $length = null;

    /** Decimal digits (scale), for Decimal field */
    public ?int $decimal = null;

    /** Value list, for Enum field */
    public ?array $values = null;

    public bool $null = false;
    public $default = null;
}
