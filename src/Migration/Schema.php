<?php

namespace Vendimia\Database\Migration;

use Vendimia\Database\{
    Setup,
    FieldType,
    ConstrainAction,
    Migration\FieldDef
};

/**
 * Table schema modifier
 */
class Schema
{
    /** Fields to be added (or created) */
    private $add_fields = [];

    /** Fields to be changed. Original field is the key */
    private $change_fields = [];

    /** Fields to be eliminated. */
    private $drop_fields = [];

    /** Indexes to be created */
    private $create_indexes = [];

    /** Indexes to be dropped */
    private $drop_indexes = [];

    private $primary_keys = [];

    /** */
    private $foreign_keys = [];

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
        ?bool $after = null,
        ?string $rename_from = null,

        /** Array prefix where to save this field definition. Possible values: 'add', 'change' */
        string $action = 'add',

        /** For ForeignKey $type, [table name, column name, [column name[, ...]] */
        ?array $target = null,

        /** For ForeignKey, ON UPDATE constrain action */
        ?ConstrainAction $on_update = ConstrainAction::CASCADE,

        /** For ForeignKey, ON DELETE constrain action */
        ?ConstrainAction $on_delete = ConstrainAction::CASCADE,
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

        // Si hay un target, este campo debe requerir un constrain
        if ($target) {
            $this->foreign_keys[] = Setup::$connector->buildForeignKeyDef(
                table_name: $this->table_name,
                field_name: $name,
                foreign_table_name: $target[0],
                foreign_field_names: array_slice($target, 1),
                on_update: $on_update,
                on_delete: $on_delete,
            );
        }
    }

    /**
     * Creates an index for each field name
     */
    public function index(...$field_names)
    {
        // Convertimos FieldType en el nombre del tipo de la base de datos
        $this->create_indexes[] = Setup::$connector->buildIndexDef(
            $this->table_name,
            $field_names
        );
    }

    /**
     * Drops an index from a field
     */
    public function dropIndex(...$field_names)
    {
        // Convertimos FieldType en el nombre del tipo de la base de datos
        $this->create_indexes[] = Setup::$connector->buildDropIndexDef(
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
        $this->create_indexes[] = Setup::$connector->buildIndexDef(
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
        return $this->uniqueIndex(...$field_names);
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
     * Changes the definition of a field
     */
    public function renameField($rename_from, ...$args) {
        $args['rename_from'] = $rename_from;
        $args['action'] = 'change';
        $this->field(...$args);
    }


    public function dropField($field)
    {
        $this->drop_fields[] = $field;
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

        // Añadimos los FOREIGN KEYS
        foreach ($this->foreign_keys as $statment) {
            $return[] = $statment;
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
     * Retuns the 'drop' fields list, used for removing fields
     */
    public function getDropFields(): array
    {
        return $this->drop_fields;
    }

    /**
     * Returns the indexes to be created
     */
    public function getCreateIndexes()
    {
        return $this->create_indexes;
    }

    /**
     * Returns the indexes to be dropped
     */
    public function getDropIndexes()
    {
        return $this->drop_indexes;
    }

    public function getForeignKeyDefs(): array
    {
        return $this->foreign_keys;
    }
}
