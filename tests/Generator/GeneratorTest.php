<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Eureka\Component\Validation\Tests;

use Eureka\Component\Database\Connection;
use Eureka\Component\Orm\Exception\GeneratorException;
use Eureka\Component\Orm\Generator\Generator;
use PHPUnit\Framework\TestCase;

/**
 * Class GeneratorTest
 *
 * @author Romain Cottard
 */
class GeneratorTest extends TestCase
{
    /**
     * @return void
     */
    public function testICanInstantiateGenerator()
    {
        $generator = new Generator();
        $this->assertInstanceOf(Generator::class, $generator);
    }

    /**
     * @return void
     * @throws GeneratorException
     */
    public function testICanGenerateMappersAndEntityClassesAccordingToConfigAndMockedDatabase()
    {
        $generator = new Generator();
        $generator->generate($this->getConnectionMock(), $this->getConfig(), '', false);

        $this->assertInstanceOf(Generator::class, $generator);
    }

    /**
     * @return Connection
     */
    private function getConnectionMock(): Connection
    {
        $mockBuilder = $this->getMockBuilder(Connection::class)->disableOriginalConstructor();
        $connection  = $mockBuilder->getMock();
        $connection->method('query')->with('SHOW FULL COLUMNS FROM user')->willReturn(
            $this->getPDOStatementMock(
                [
                    (object) ['Field' => 'user_id', 'Type' => 'int(10) unsigned', 'Collation' => null, 'Null' => 'NO', 'Key' => 'PRI', 'Default' => null, 'Extra' => 'auto_increment', 'Privileges' => '', 'Comment' => ''],
                    (object) ['Field' => 'user_is_enabled', 'Type' => 'tinyint(1) unsigned', 'Collation' => null, 'Null' => 'NO', 'Key' => '', 'Default' => 1, 'Extra' => '', 'Privileges' => '', 'Comment' => ''],
                    (object) ['Field' => 'user_email', 'Type' => 'varchar(200)', 'Collation' => 'utf8_unicode_ci', 'Null' => 'NO', 'Key' => 'UNI', 'Default' => null, 'Extra' => '', 'Privileges' => '', 'Comment' => ''],
                    (object) ['Field' => 'user_password', 'Type' => 'varchar(100)', 'Collation' => 'utf8_unicode_ci', 'Null' => 'NO', 'Key' => 'UNI', 'Default' => null, 'Extra' => '', 'Privileges' => '', 'Comment' => ''],
                    (object) ['Field' => 'user_date_create', 'Type' => 'datetime', 'Collation' => null, 'Null' => 'NO', 'Key' => '', 'Default' => null, 'Extra' => '', 'Privileges' => '', 'Comment' => ''],
                    (object) ['Field' => 'user_date_update', 'Type' => 'datetime', 'Collation' => null, 'Null' => 'YES', 'Key' => '', 'Default' => null, 'Extra' => '', 'Privileges' => '', 'Comment' => ''],
                    false,
                    (object) ['Field' => 'user_id', 'Type' => 'int(10) unsigned', 'Collation' => null, 'Null' => 'NO', 'Key' => 'PRI', 'Default' => null, 'Extra' => 'autoincrement', 'Privileges' => '', 'Comment' => ''],
                    (object) ['Field' => 'user_is_enabled', 'Type' => 'tinyint(1) unsigned', 'Collation' => null, 'Null' => 'NO', 'Key' => '', 'Default' => 1, 'Extra' => '', 'Privileges' => '', 'Comment' => ''],
                    (object) ['Field' => 'user_email', 'Type' => 'varchar(200)', 'Collation' => 'utf8_unicode_ci', 'Null' => 'NO', 'Key' => 'UNI', 'Default' => null, 'Extra' => '', 'Privileges' => '', 'Comment' => ''],
                    (object) ['Field' => 'user_password', 'Type' => 'varchar(100)', 'Collation' => 'utf8_unicode_ci', 'Null' => 'NO', 'Key' => 'UNI', 'Default' => null, 'Extra' => '', 'Privileges' => '', 'Comment' => ''],
                    (object) ['Field' => 'user_date_create', 'Type' => 'datetime', 'Collation' => null, 'Null' => 'NO', 'Key' => '', 'Default' => null, 'Extra' => '', 'Privileges' => '', 'Comment' => ''],
                    (object) ['Field' => 'user_date_update', 'Type' => 'datetime', 'Collation' => null, 'Null' => 'YES', 'Key' => '', 'Default' => null, 'Extra' => '', 'Privileges' => '', 'Comment' => ''],
                    false,
                ]
            )
        );

        return $connection;
    }

    private function getPDOStatementMock(array $mockedData = []): \PDOStatement
    {
        $mockBuilder = $this->getMockBuilder(\PDOStatement::class);
        $statement   = $mockBuilder->getMock();
        $statement->method('fetch')->with(Connection::FETCH_OBJ)->willReturnOnConsecutiveCalls(...$mockedData);

        return $statement;
    }

    /**
     * @return array
     */
    private function getConfig(): array
    {
        return [
            'user' => [
                'comment' => [
                    'author'    => 'Eureka Orm Generator',
                    'copyright' => 'Test Author',
                ],
                'namespace' => [
                    'entity'     => 'Eureka\Component\Orm\Tests\Generated\Entity',
                    'mapper'     => 'Eureka\Component\Orm\Tests\Generated\Infrastructure\Mapper',
                    'repository' => 'Eureka\Component\Orm\Tests\Generated\Repository',
                ],
                'path' => [
                    'entity'     => __DIR__ . '/../Generated/Entity',
                    'mapper'     => __DIR__ . '/../Generated/Infrastructure/Mapper',
                    'repository' => __DIR__ . '/../Generated/Repository',
                ],
                'cache' => [
                    'prefix'     => 'test.user',
                ],
                'database' => [
                    'table'      => 'user',
                    'prefix'     => 'user',
                ],
                'class' => [
                    'classname'  => 'User',
                ],
                'joins' => [],

                'validation' => [
                    'enabled'             => true,
                    'auto'                => true,
                    'extended_validation' => null,
                ],
            ],
        ];
    }
}
