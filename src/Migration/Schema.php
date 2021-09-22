<?php
namespace Vendimia\Database\Migration;

use Vendimia\Database\Setup;
use Vendimia\Database\FieldType;
use Vendimia\Database\Migration\FieldDef;

/**
 * Table schema modifier
 */
class Schema
{
    private $fields = [];
    private $indexes = [];
    private $primary_keys = [];

    public function __construct(
        private string $table_name
    )
    {

    }

    /**
     * Adds a field definition
     */
    public function field(
        string $name,
        FieldType $type,
        ?int $length = null,
        ?int $decimal = null,
        ?array $values = null,
        bool $null = false,
        $default = null,
    )
    {
        $fielddef = new FieldDef(
            name: $name,
            type: $type,
            length: $length,
            decimal: $decimal,
            values: $values,
            null: $null,
            default: $default,
        );

        // Convertimos FieldType en el nombre del tipo de la base de datos
        $this->fields[] = Setup::$connector->buildFieldDef($fielddef);
    }

    /**
     * Creates an index
     */
    public function index(
        ...$field_names,

    )
    {
        // Convertimos FieldType en el nombre del tipo de la base de datos
        $this->indexes[] = Setup::$connector->buildIndexDef($this->table_name, $field_names);
    }

    /**
     * Creates an unique index
     */
    public function uniqueIndex(
        ...$field_names,

    )
    {
        // Convertimos FieldType en el nombre del tipo de la base de datos
        $this->indexes[] = Setup::$connector->buildIndexDef($this->table_name, $field_names, true);
    }

    /**
     * Creates a primary key using $fields
     */
    public function primaryKey(...$fields)
    {
        $this->primary_keys = $fields;
    }

    /**
     * Alias of self::field()
     */
    public function addField(...$args)
    {
        $this->field(...$args);
    }

    /**
     * Returns this schema table name
     */
    public function getTableName(): string
    {
        return $this->table_name;
    }


    /**
     * Returns $this::$fields joined by commas, used in CREATE TABLE
     */
    public function getFieldsForCreate(): string
    {
        $return = $this->fields;

        if ($this->primary_keys) {
            // La definición de PRIMARY KEY es la misma en sqlite, mysql y pqsql
            $return[] = 'PRIMARY KEY(' .
                join(',', Setup::$connector->escapeIdentifier($this->primary_keys))
            . ')';
        }
        return join(',', $return);
    }

    /**
     * Retuns the fields list, used for update
     */
    public function getFields(): array
    {
        return $this->fields;
    }

    /**
     * Returns $this->$indexes
     */
    public function getIndexes()
    {
        return $this->indexes;
    }

}
