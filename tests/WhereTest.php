<?php declare(strict_types=1);

use Vendimia\Database\{
    TableAliases,
    WhereTrait,
    Entity,
    Setup,
    Field,
};

use PHPUnit\Framework\TestCase;

class TargetClass extends Entity
{
    #[Field\Char(256)]
    public string $name;

    #[Field\Char(256)]
    public string $lastname;

    #[Field\Integer]
    public string $age;
}

final class WhereTest extends TestCase
{
    public function testQueryPrimaryKeyDirectly(): void
    {
        $query = TargetClass::query(99);

        $expected = match (Setup::$connector->getName()) {
            'sqlite' =>
                '"T0"."id"=99',
            'mysql' =>
                '`T0`.`id`=99',
        };

        $this->assertEquals(
            $expected,
            $query->getSQLWhereString(),
        );
    }

    public function testQueryWithMultiplePrimaryKeyvalues(): void
    {
        $query = TargetClass::query(99, 1024, 666);

        $expected = match (Setup::$connector->getName()) {
            'sqlite' =>
                '"T0"."id" IN (99, 1024, 666)',
            'mysql' =>
                '`T0`.`id` IN (99, 1024, 666)',
        };

        $this->assertEquals(
            $expected,
            $query->getSQLWhereString(),
        );
    }

    public function testMultipleQueryWithImplicitAnd(): void
    {
        $query = TargetClass::query(name: 'oliver', lastname: 'etchebarne');

        $expected = match (Setup::$connector->getName()) {
            'sqlite' =>
                '"T0"."name"=\'oliver\' AND "T0"."lastname"=\'etchebarne\'',
            'mysql' =>
                '`T0`.`name`=\'oliver\' AND `T0`.`lastname`=\'etchebarne\'',
        };

        $this->assertEquals(
            $expected,
            $query->getSQLWhereString(),
        );
    }

    public function testAndOrQueries(): void
    {
        // Este query en la vida real sería un desastre, el
        // age = 44 AND lastname = "etchebarne" se ejecutaría primero.
        $query = TargetClass::query(name: 'oliver')
            ->or(age: 44)
            ->and(lastname: 'etchebarne')
        ;

        $expected = match (Setup::$connector->getName()) {
            'sqlite' =>
                '"T0"."name"=\'oliver\' OR "T0"."age"=44 AND "T0"."lastname"=\'etchebarne\'',
            'mysql' =>
                '`T0`.`name`=\'oliver\' OR `T0`.`age`=44 AND `T0`.`lastname`=\'etchebarne\'',
        };

        $this->assertEquals(
            $expected,
            $query->getSQLWhereString(),
        );
    }

    public function testGroupedExpression(): void
    {
        $query = TargetClass::query(name: 'oliver')
            ->or()->group(fn($query) => $query
                ->where(age: 44)
                ->and(lastname: 'etchebarne')
            )
        ;

        $expected = match (Setup::$connector->getName()) {
            'sqlite' =>
                '"T0"."name"=\'oliver\' OR ("T0"."age"=44 AND "T0"."lastname"=\'etchebarne\')',
            'mysql' =>
                '`T0`.`name`=\'oliver\' OR (`T0`.`age`=44 AND `T0`.`lastname`=\'etchebarne\')',
        };

        $this->assertEquals(
            $expected,
            $query->getSQLWhereString(),
        );

    }

}
