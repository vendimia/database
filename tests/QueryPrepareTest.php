<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

use Vendimia\Database\Migration\Schema;
use Vendimia\Database\Setup;
use Vendimia\Database\FieldType;
use Vendimia\Database\ConstrainAction;

final class QueryPrepareTest extends TestCase
{
    public function testPrepareWithSimpleParametersQuery()
    {
        $connector = Setup::getConnector();

        $query = $connector->prepare('SELECT * FROM {table:i} WHERE {variable:i}={value}',
            table: 'a_table',
            variable: 'a_variable',
            value: 'a_value',
        );

        $expected = match (Setup::$connector->getName()) {
            'sqlite' =>
                'SELECT * FROM "a_table" WHERE "a_variable"=\'a_value\'',
            'mysql' =>
                'SELECT * FROM `a_table` WHERE `a_variable`=\'a_value\'',
        };

        $this->assertEquals(
            $expected,
            $query
        );
    }

    public function testPrepareWithSelfValuedVariables()
    {
        $connector = Setup::getConnector();

        $query = $connector->prepare('SELECT {last_name:is} FROM {table:si} where {name:si}={oliver:s}');

        $expected = match (Setup::$connector->getName()) {
            'sqlite' =>
                'SELECT "last_name" FROM "table" where "name"=\'oliver\'',
            'mysql' =>
                'SELECT `last_name` FROM `table` where `name`=\'oliver\'',
        };

        $this->assertEquals(
            $expected,
            $query
        );

    }

    public function testPrepareShouldFailWithInvalidArgumentException()
    {
        $this->expectException(InvalidArgumentException::class);

        $connector = Setup::getConnector();

        $query = $connector->prepare('{a}');
    }

}
