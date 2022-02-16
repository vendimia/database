<?php
namespace Vendimia\Database;

use ReflectionClass;
use ReflectionObject;
use ReflectionException;
use ReflectionAttribute;
use ReflectionProperty;
use InvalidArgumentException;
use Stringable;

abstract class Entity implements Stringable
{
    /** True if the query which generate this Entity returned no data */
    protected $is_empty = true;

    /** True if this Entity has just been created (i.e. not fetched from the db) */
    protected $is_new = true;

    /**
     * True if this entity has been loaded.
     *
     * Some entities needed to be manually loaded using self::load(), e.g. if
     * has been created by a ManyToOne field */
    protected $is_loaded = false;

    /** Pre-loaded data from the database */
    protected array $database_data = [];

    /**
     * Builds the default table name from the entity class name.
     */
    protected static function buildTableName()
    {
        return join('_', array_filter(explode('\\', strtolower(get_called_class())),
            fn($e) => $e != 'database'
        ));
    }

    /**
     * Helper method for removing multi-line asterisks and forward slashes.
     *
     * FIXME: This shouldn't be here.
     */
    public static function extractTextFromDocComment($doc_comment): string
    {
        $result = '';
        $lines = explode("\n", $doc_comment);

        // Si solo hay una línea, removemos los caracteres de ámbos lados
        if (count($lines) == 1) {
            return trim($doc_comment, '/* ');
        }

        foreach ($lines as $line) {
            $pline = trim($line);

            // Si la línea empieza con un '/**', lo eliminamos
            if (str_starts_with($pline, '/**')) {
                $pline = substr($pline, 3);
            }

            // Si la línea acaba con un */, lo eliminamos
            if (str_ends_with($pline, '*/')) {
                $pline = substr($pline, 1, -2);
            }

            // Si la línea empieza con un '*', lo eliminamos
            if (str_starts_with($pline, '*')) {
                $pline = substr($pline, 1);
            }

            // Líneas vacías añaden un nuevo párrafo.
            if ($pline == "") {
                $result .= "\n\n";
            } else {
                $result .= $pline;
            }
        }

        return trim($result, " \n");
    }

    /**
     * Returns this entity name, i.e. the last component in the FQCN
     */
    public static function getName()
    {
        $class = '\\' . strtolower(get_called_class());
        return substr($class, strrpos($class, '\\') + 1);
    }

    /**
     * Returns this entity database table name.
     */
    public static function getTableName()
    {
        return static::$table ?? static::buildTableName();
    }

    /**
     * Returns the primary key Field object
     */
    public static function getPrimaryKeyField(): Field\FieldAbstract
    {
        if (isset(static::$primary_key)) {
            return static::F(static::$primary_key);
        }

        return static::F('id');
    }

    /**
     * Creates a Query which will return a Entity of this class
     */
    public static function get(...$where)
    {
        return (new Query(static::class, $where))->get();
    }

    /**
     * Creates a Query which will return a non-loaded Entity of this class
     */
    public static function lazyGet(...$where)
    {
        return (new Query(static::class, $where))->get(lazy: true);
    }



    /**
     * Creates a Query which will return a EntitySet of this class
     */
    public static function find(...$where)
    {
        return (new Query(static::class, $where))->find();
    }

    /**
     * Alias to find()
     */
    public static function all(...$where)
    {
        return (new Query(static::class, $where))->find();
    }

    /**
     * Creates a Query, for obtaining data from the database.
     */
    public static function query(...$where)
    {
        return new Query(static::class, $where);
    }

    /**
     * Returns a Field object
     */
    public static function F($field): Field\FieldAbstract
    {
        $class = static::class;
        try {
            $ref_property = (new ReflectionClass($class))->getProperty($field);
        } catch (ReflectionException) {
            // Esto es _casi_ un hack: Si piden 'id', y no existe, creamos un field
            if ($field == 'id' && !isset(static::$primary_key)) {
                // Craemos un objeto
                return new Field\AutoIncrement(
                    name: 'id',
                    entity_class: static::class,
                    args: [
                        'auto_increment' => true,
                        'primary_key' => true,
                    ],
                );
            }
            throw new InvalidArgumentException("Field '{$field}' not found in entity {$class}");
        }

        // Sólo debería haber _un_ atributo Field
        $attr = $ref_property->getAttributes(
            Field\FieldAbstract::class,
            ReflectionAttribute::IS_INSTANCEOF
        )[0] ?? null;

        if (!$attr) {
            throw new InvalidArgumentException("Property '{$field}' in class '{$class}' is not a Field");
        }

        // $attr->newInstance() no acepta nuevos parámetros. Así que desdoblamos
        // la instanciación del atributo para inyectarle el nombre y la clase

        $class = $attr->getName();
        $args = $attr->getArguments();

        return new $class(
            name: $field,
            comment: static::extractTextFromDocComment($ref_property->getDocComment()),
            entity_class: static::class,
            args: $args
        );
    }

    /**
     * Return all the fields in this entity
     */
    public static function getFieldList()
    {
        $fields = [];

        $ref_properties = (new ReflectionClass(static::class))->getProperties(
            ReflectionProperty::IS_PUBLIC
        );

        foreach ($ref_properties as $rp) {
            $attr = $rp->getAttributes(
                Field\FieldAbstract::class,
                ReflectionAttribute::IS_INSTANCEOF
            )[0] ?? null;

            if (!$attr) {
                continue;
            }

            $class = $attr->getName();

            $fields[$rp->name] = new $class(
                name: $rp->name,
                comment: static::extractTextFromDocComment($rp->getDocComment()),
                entity_class: static::class,
                args: $attr->getArguments(),
            );
        }

        // Si no hay una llave primaria definida, creamos una llamada 'id'
        if (!isset(static::$primary_key)) {
            $fields = ['id' => new Field\AutoIncrement(
                name: 'id',
                entity_class: static::class,
                args: [
                    'auto_increment' =>  true,
                    'primary_key' => true,
                ]
            )] + $fields;
        }


        return $fields;
    }

    /**
     * Creates a new entity in memory, like the statement `new Entity(...$data)`
     */
    public static function new(...$data): self
    {
        return new static(...$data);
    }


    /**
     * Creates a new entity, and saves it in the database
     */
    public static function create(...$data): self
    {
        $entity = new static(...$data);
        $entity->save();

        return $entity;
    }

    /**
     *
     */
    public function __construct(...$data)
    {
        foreach ($data as $field => $value) {
            $this->$field = $value;
        }
        if ($data) {
            $this->is_loaded = true;
        }
    }

    /**
     * Returns the primary key value
     */
    public function pk()
    {
        return $this->{$this::getPrimaryKeyField()->getName()} ?? null;
    }

    /**
     * Preloads the database data
     */
    public function fromDatabase($data): self
    {
        $this->database_data = $data;
        return $this;
    }

    /**
     * Sets the fields value from database data.
     */
    public function load(): self
    {
        // No cargamos si ya está cargado
        if ($this->is_loaded) {
            return $this;
        }

        $data = $this->database_data;

        $fields = [];
        $post_proc_fields = [];

        // Reordenamos los campos usando el nombre del campo _en la base de
        // datos_ como índice.
        foreach ($this->getFieldList() as $name => $field) {
            $fields[$field->getFieldName()] = $field;
            $field->setEntity($this);

            // Si este field requiere post-proceso, lo guardamos en otro listado
            if ($field->requirePostProc()) {
                $post_proc_fields[$field->getName()] = $field;
            }

        }

        foreach ($data as $key => $value) {
            if (!key_exists($key, $fields)) {
                // FIXME: Ignoramos los campos que no existen?
                continue;
            }
            $this->{$fields[$key]->getName()}
                = $fields[$key]->processDatabaseValue($value);
        }

        foreach ($post_proc_fields as $field){
            $field->postProc();
        }

        // Si hay datos, no está vacío
        if ($data) {
            $this->is_empty = false;
        } else {
            $this->is_empty = true;
        }

        // Ya no es nuevo
        $this->is_new = false;

        // Ya está cargado
        $this->is_loaded = true;

        return $this;
    }

    public function isNew(): bool
    {
        return $this->is_new;
    }

    public function notNew(): bool
    {
        return !$this->is_new;
    }

    public function setEmpty(): self
    {
        $this->is_empty = true;

        return $this;
    }

    public function isEmpty()
    {
        return $this->is_empty;
    }
    public function notEmpty()
    {
        return !$this->is_empty;
    }

    public function isLoaded()
    {
        return $this->is_loaded;
    }
    public function notLoaded()
    {
        return !$this->is_loaded;
    }

    /**
     * Save the entity to the database
     */
    public function save($disable_hooks = false, $fields = [])
    {
        $extra_fields = null;
        // Antes de grabar, ejecutamos el callback self::$pre_save, si existe
        if (!$disable_hooks && isset(static::$pre_save)) {
            $method = static::$pre_save;
            $extra_fields = $this->$method();

            if ($extra_fields && $fields) {
                $fields = [...$fields, ...$extra_fields];
            }
        }

        $payload = [];
        $post_proc_fields = [];

        $pk_field = static::getPrimaryKeyField()->getFieldName();

        $field_list = $this->getFieldList();

        if ($fields) {
            $field_list = array_intersect_key($field_list, array_flip($fields));
        }

        foreach ($field_list as $field) {
            $field->setEntity($this);

            // Si este field requiere post-proceso, lo guardamos en otro listado
            if ($field->requirePostProc()) {
                $post_proc_fields[$field->getName()] = $field;
            }

            // Ignoramos los Fields que no tengan una propiedad en el objeto.
            if (!property_exists($this, $field->getName())) {
                continue;
            }
            $value = $this->{$field->getName()};

            // Fallamos si value es un objeto,y no está cargado
            if (($value instanceof Entity) && $value?->isLoaded() === false) {
                //throw new InvalidArgumentException("Entity in field '{$field->getName()}' needs to be loaded before saving this entity");
                continue;
            }

            // Solo guardamos el valor si el Field tiene un nombre de campo en la db
            if ($field_name = $field->getFieldName()) {
                $payload[$field_name] = $field->processPHPValue($value);
            }
        }

        $pk_value = $this->pk();

        $update_ok = false;
        if ($pk_value) {
            // Probamos actualizar.
            $update_ok = Setup::$connector->update(
                static::getTableName(),
                $payload,
                "{$pk_field} = " . $pk_value
            );
        }

        // Si no actualizó, grabamos como nuevo
        if (!$update_ok) {
            $pk_value = Setup::$connector->insert(
                static::getTableName(),
                $payload,
            );
        }

        $this->$pk_field = $pk_value;

        foreach ($post_proc_fields as $field){
            $field->postProc();
        }

        // Este registro ya no está vacío.
        $this->is_empty = false;

        // Este registro ya no es nuevo
        $this->is_new = false;

        // Este registro ya está cargado
        $this->is_loaded = true;

        // Ejecutamos el callback self::$post_save, si existe
        if (!$disable_hooks && isset(static::$post_save)) {
            $method = static::$post_save;
            $this->$method();
        }
    }

    /**
     * Updates and saves an entity
     */
    public function update(...$payload)
    {
        foreach ($payload as $key => $value) {
            $this->$key = $value;
        }

        // Si el entity es nuevo, grabamos todos los campos
        if ($this->isNew()) {
            return $this->save();
        }

        return $this->save(fields: array_keys($payload));
    }

    /**
     * Deletes an entity from the database
     */
    public function delete()
    {
        $pk_field = static::getPrimaryKeyField()->getFieldName();
        Setup::$connector->delete(
            static::getTableName(),
            "{$pk_field} = " . $this->pk()
        );
    }

    /**
     * Returns this entity as an array
     */
    public function asArray($native_values = false): array
    {
        // Usamos ReflectionObject para obtener incluso las propiedades
        // creadas en runtime
        $ref_properties = (new ReflectionObject($this))->getProperties(
            ReflectionProperty::IS_PUBLIC
        );

        // Filtramos las propiedades estáticas
        $ref_properties = array_filter($ref_properties,
            fn($p) => !$p->isStatic()
        );

        $result = [];

        foreach ($ref_properties as $rp) {
            $value = $this->{$rp->name};
            if ($native_values) {
                if ($value instanceof Stringable) {
                    $value = (string)$value;
                } elseif ($value instanceof EntitySet) {
                    // Los EntitySet no tienen representación
                    continue;
                }
            }
            $result[$rp->name] = $value;
        }

        return $result;
    }

    public function __toString()
    {
        return (string)$this->pk();
    }

    public function __debugInfo()
    {
        return $this->asArray();
    }
}
