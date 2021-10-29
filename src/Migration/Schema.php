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
    // Fields to be added (or created)
    private $add_fields = [];

    // Fields to be changed. Original field is the key
    private $change_fields = [];

    // Indexes to be created
    private $create_indexes = [];

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
        mixed $default = null,
        bool $rename_from = null,
        bool $after = null,
        $action = 'add',
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

        $array_name = "{$action}_fields";

        // Si estamos renombrando, usamos el nombre antiguo
        if ($rename_from) {
            $name = $rename_from;
        }

        // Convertimos FieldType en el nombre del tipo de la base de datos
        $this->{$array_name}[$name] = Setup::$connector->buildFieldDef($fielddef);
    }

    /**
     * Creates an index for each field name
     */
    public function index(...$field_names)
    {
        // Convertimos FieldType en el nombre del tipo de la base de datos
        $this->indexes[] = Setup::$connector->buildIndexDef(
            $this->table_name,
            $field_names
        );
    }

    /**
     * Alias of self::index()
     */
    public function addIndex(...$field_names)
    {
        return $this->index(...$field_names);
    }

    /**
     * Creates an unique index
     */
    public function uniqueIndex(
        ...$field_names,
    )
    {
        // Convertimos FieldType en el nombre del tipo de la base de datos
        $this->indexes[] = Setup::$connector->buildIndexDef(
            $this->table_name,
            $field_names,
            unique: true
        );
    }

    /**
     * Alias of self::uniqueIndex()
     */
    public function addUniqueIndex(...$field_names)
    {
        return $this->index(...$field_names);
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
     * Changes the definition of a field
     */
    public function changeField(...$args) {
        $args['action'] = 'change';
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
        $return = $this->add_fields;

        if ($this->primary_keys) {
            // La definición de PRIMARY KEY es la misma en sqlite, mysql y pqsql
            $return[] = 'PRIMARY KEY(' .
                join(',', Setup::$connector->escapeIdentifier($this->primary_keys))
            . ')';
        }
        return join(',', $return);
    }

    /**
     * Retuns the 'add' fields list, used for update
     */
    public function getAddFields(): array
    {
        return $this->add_fields;
    }

    /**
     * Retuns the 'change' fields list, used for changing
     */
    public function getChangeFields(): array
    {
        return $this->change_fields;
    }

    /**
     * Returns the indexes to be created
     */
    public function getCreateIndexes()
    {
        return $this->create_indexes;
    }

}
