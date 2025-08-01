<?php
namespace Vendimia\Database\Field;

use Vendimia\Database\FieldType;
use Vendimia\Database\Entity;

interface FieldInterface
{
    /**
     * Returns the field type, used for SQL field definition.
     */
    public static function getFieldType(): ?FieldType;

    /**
     * Returns this database field name. Null disables this Field to have one
     * in the database.
     */
    public function getFieldName(): ?string;

    /**
     * Returns this field name
     */
    public function getName(): string;

    /**
     * Sets this field entity owner
     */
    public function setEntity(Entity $entity): self;

    /**
     * Returns an array with [table, field_name]
     */
    public function getFullFieldName(): array;

    /**
     * Process and validates a PHP value, and returns a database-aware value.
     */
    public function processPHPValue($value);

    /**
     * Converts a database value to PHP value.
     */
    public function processDatabaseValue($value);

    /**
     * Returns true if this field requires execution of self::postProc() after
     * the field value has been set.
     */
    public function requirePostProc(): bool;

    /**
     * Post process executed over this field value.
     */
    public function postProc(): void;

}
