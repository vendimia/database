<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

use Vendimia\Database\Migration\Schema;
use Vendimia\Database\Setup;
use Vendimia\Database\FieldType;
use Vendimia\Database\ConstrainAction;

final class SchemaTest extends TestCase
{
    public function testCreateSchemaWithAField()
    {
        $schema = new Schema('test_table');
        $schema->field('id', FieldType::Integer);

        if (Setup::$connector->getName() == 'sqlite') {
            $this->assertEquals(
                '"id" INTEGER NOT NULL',
                $schema->getFieldsForCreate(),
            );
        } elseif (Setup::$connector->getName() == 'mysql') {
            $this->assertEquals(
                '`id` INTEGER NOT NULL',
                $schema->getFieldsForCreate(),
            );
        }
    }

    public function testCreateSchemaComplex()
    {
        $schema = new Schema('test_table');
        $schema->field('id', FieldType::Integer);
        $schema->field('name', FieldType::Char, length: 20, default: 'Oliver');

        if (Setup::$connector->getName() == 'sqlite') {
            $this->assertEquals(
                '"id" INTEGER NOT NULL,"name" TEXT NOT NULL DEFAULT \'Oliver\'',
                $schema->getFieldsForCreate(),
            );
        } elseif (Setup::$connector->getName() == 'mysql') {
            $this->assertEquals(
                '`id` INTEGER NOT NULL,`name` VARCHAR(20) NOT NULL DEFAULT \'Oliver\'',
                $schema->getFieldsForCreate(),
            );
        }
    }

    public function testCreateSchemaWithPrimaryKey()
    {
        $schema = new Schema('test_table');
        $schema->field('id', FieldType::Integer);
        $schema->field('name', FieldType::Char, length: 20, default: 'Oliver');
        $schema->primaryKey('id');

        $expected = match (Setup::$connector->getName()) {
            'sqlite' =>
                '"id" INTEGER NOT NULL,"name" TEXT NOT NULL DEFAULT \'Oliver\',PRIMARY KEY("id")',
            'mysql' =>
                '`id` INTEGER NOT NULL,`name` VARCHAR(20) NOT NULL DEFAULT \'Oliver\',PRIMARY KEY(`id`)',
        };

        $this->assertEquals(
            $expected,
            $schema->getFieldsForCreate(),
        );
    }

    public function testCreateSchemaWithForeignKeyCascade()
    {
        $schema = new Schema('test_table');
        $schema->field('id', FieldType::Integer);
        $schema->field('othertest_id', FieldType::ForeignKey, target: ['othertest', 'id']);
        $schema->primaryKey('id');

        $expected = match (Setup::$connector->getName()) {
            'sqlite' => [
                'FOREIGN KEY ("othertest_id") REFERENCES "othertest" ("id") ON UPDATE CASCADE ON DELETE CASCADE',
            ],
            'mysql' => [
                'FOREIGN KEY (`othertest_id`) REFERENCES `othertest` (`id`) ON UPDATE CASCADE ON DELETE CASCADE',
            ],
        };

        $this->assertEquals(
            $expected,
            $schema->getForeignKeyDefs(),
        );
    }

    public function testCreateSchemaWithForeignKeySetNull()
    {
        $schema = new Schema('test_table');
        $schema->field('id', FieldType::Integer);
        $schema->field('othertest_id', FieldType::ForeignKey, target: ['othertest', 'id'], on_delete: ConstrainAction::NULL);
        $schema->primaryKey('id');

        $expected = match (Setup::$connector->getName()) {
            'sqlite' => [
                'FOREIGN KEY ("othertest_id") REFERENCES "othertest" ("id") ON UPDATE CASCADE ON DELETE SET NULL',
            ],
            'mysql' => [
                'FOREIGN KEY (`othertest_id`) REFERENCES `othertest` (`id`) ON UPDATE CASCADE ON DELETE SET NULL',
            ],
        };

        $this->assertEquals(
            $expected,
            $schema->getForeignKeyDefs(),
        );
    }


    public function testCreateSchemaWithIndexes()
    {
        $schema = new Schema('test_table');
        $schema->index('code');
        $schema->uniqueIndex('age');

        $expected = match (Setup::$connector->getName()) {
            'sqlite' => [
                'CREATE INDEX "idx_test_table_code" ON "test_table" ("code")',
                'CREATE UNIQUE INDEX "idx_test_table_age" ON "test_table" ("age")'
            ],
            'mysql' => [
                'CREATE INDEX `idx_test_table_code` ON `test_table` (`code`)',
                'CREATE UNIQUE INDEX `idx_test_table_age` ON `test_table` (`age`)'
            ],
        };

        $this->assertEquals(
            $expected,
            $schema->getCreateIndexes(),
        );
    }

    public function testCreateEnum()
    {
        $schema = new Schema('test_table');
        $schema->field('enum', FieldType::Enum, values: ['value-1','value-2','value-3']);

        $expected = match (Setup::$connector->getName()) {
            'sqlite' => "\"enum\" TEXT CHECK(\"enum\" IN ('value-1','value-2','value-3')) NOT NULL",
            'mysql' => "`enum` ENUM('value-1','value-2','value-3') NOT NULL",
        };

        $this->assertEquals(
            $expected,
            $schema->getFieldsForCreate(),
        );

    }

    public function testCreateDecimal()
    {
        $schema = new Schema('test_table');
        $schema->field('decimal', FieldType::Decimal, length: 8, decimal: 2);

        $expected = match (Setup::$connector->getName()) {
            'sqlite' => "\"decimal\" NUMERIC NOT NULL",
            'mysql' => "`decimal` DECIMAL(8,2) NOT NULL",
        };

        $this->assertEquals(
            $expected,
            $schema->getFieldsForCreate(),
        );
    }

}
