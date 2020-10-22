<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Eureka\Component\Validation\Tests;

use Eureka\Component\Orm\Exception\GeneratorException;
use Eureka\Component\Orm\Generator\Type;
use PHPUnit\Framework\TestCase;

/**
 * Class TypeTest
 *
 * @author Romain Cottard
 */
class TypeTest extends TestCase
{
    /**
     * @dataProvider typeProvider
     *
     * @param string $sqlType
     * @param string $classType
     * @return void
     * @throws GeneratorException
     */
    public function testICanCreateTypeWithFactory(string $sqlType, string $classType)
    {
        $type = Type\Factory::create($sqlType, '');

        $this->assertInstanceOf($classType, $type);
    }

    /**
     * @return void
     * @throws GeneratorException
     */
    public function testIHaveAnExceptionWhenITryToGetTypeWithUnknownType()
    {
        $this->expectException(GeneratorException::class);
        $this->expectExceptionMessage('Sql type cannot be converted into php type! (type: unknowntype)');
        Type\Factory::create('unknowntype', '');
    }

    /**
     * @param string $sqlType
     * @return void
     * @throws GeneratorException
     *
     * @dataProvider invalidTypeProvider
     */
    public function testIHaveAnExceptionWhenITryToGetTypeWithInvalidSqlType(string $sqlType)
    {
        $this->expectException(GeneratorException::class);
        $this->expectExceptionMessage('Invalid sql type');
        Type\Factory::create($sqlType, '');
    }

    /**
     * @return string[][]
     */
    public function typeProvider(): array
    {
        return [
            'bigint'     => ['bigint(20)', Type\TypeBigint::class],
            'blob'       => ['blob', Type\TypeBlob::class],
            'bool'       => ['tinyint(1)', Type\TypeBool::class],
            'char'       => ['char(2)', Type\TypeChar::class],
            'date'       => ['date', Type\TypeDate::class],
            'datetime'   => ['datetime', Type\TypeDatetime::class],
            'decimal'    => ['decimal(5,2)', Type\TypeDecimal::class],
            'double'     => ['double(5,2)', Type\TypeDouble::class],
            'enum'       => ['enum(a,b,c)', Type\TypeEnum::class],
            'float'      => ['float(5,2)', Type\TypeFloat::class],
            'int'        => ['int(11)', Type\TypeInt::class],
            'longblob'   => ['longblob', Type\TypeLongblob::class],
            'longtext'   => ['longtext', Type\TypeLongtext::class],
            'mediumblob' => ['mediumblob', Type\TypeMediumblob::class],
            'mediumtext' => ['mediumtext', Type\TypeMediumtext::class],
            'mediumint'  => ['mediumint(8)', Type\TypeMediumint::class],
            'smallint'   => ['smallint(5)', Type\TypeSmallint::class],
            'text'       => ['text', Type\TypeText::class],
            'time'       => ['time', Type\TypeTime::class],
            'timestamp'  => ['timestamp', Type\TypeTimestamp::class],
            'tinyint'    => ['tinyint(3)', Type\TypeTinyint::class],
            'tinytext'   => ['tinytext', Type\TypeTinytext::class],
            'varbinary'  => ['varbinary(50)', Type\TypeVarbinary::class],
            'VARCHAR'    => ['varchar(50)', Type\TypeVarchar::class],
        ];
    }

    /**
     * @return string[][]
     */
    public function invalidTypeProvider(): array
    {
        return [
            ['_invalid'],
            ['1nvalid'],
        ];
    }
}
