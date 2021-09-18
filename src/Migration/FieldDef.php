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
    public ?int $length = null;
    public ?int $decimal = null;
    public bool $null = true;
    public $default = null;
}