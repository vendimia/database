<?php declare(strict_types=1);
use PHPUnit\Framework\{
    TestCase,
    Attributes\DataProvider,
};

use Vendimia\Database\Migration\Schema;
use Vendimia\Database\Setup;
use Vendimia\Database\FieldType;

const REGEXP = '/^[-+]?\d*(\.\d+)?$/';

final class ValidNumberTest extends TestCase
{
    public static function validDigitProvider(): array
    {
        return [
            ['0'],
            ['0.1'],
            ['.12'],
            ['000000000000.00'],
            ['-10.0'],
            ['+1'],
            ['01024'],
            ['-.9'],
        ];
    }

    public static function invalidDigitProvider(): array{
        return [
            ['uno'],
            ['0e1024'],
            ['0x10'],
            ['10.10.10'],
            ['  99  '],
            ['+-1'],
            ['.'],
        ];
    }

    #[DataProvider('validDigitProvider')]
    public function testValidNumbers(?string $string_number): void
    {
        $this->assertEquals(
            1,
            preg_match(REGEXP, $string_number)
        );
    }

    #[DataProvider('invalidDigitProvider')]
    public function testInvalidNumbers(string $string_number): void
    {
        $this->assertEquals(
            0,
            preg_match(REGEXP, $string_number)
        );
    }
}