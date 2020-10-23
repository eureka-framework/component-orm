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
use Eureka\Component\Orm\Enumerator\JoinRelation;
use Eureka\Component\Orm\Enumerator\JoinType;
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
     * @return void
     * @throws GeneratorException
     */
    public function testICanGenerateMappersAndEntityClassesAccordingToConfigAndMockedDatabaseAndFilteredOnUniqueConfigName()
    {
        $generator = new Generator();
        $generator->generate($this->getConnectionMock(), $this->getConfig(), 'user', false);

        $this->assertInstanceOf(Generator::class, $generator);
    }

    /**
     * @return void
     * @throws GeneratorException
     */
    public function testIHaveAnExceptionWhenITryToGenerateCodeWithEmptyConfig()
    {
        $generator = new Generator();
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Invalid config. Empty information about orm!');
        $generator->generate($this->getConnectionMock(), [], '', false);

        $this->assertInstanceOf(Generator::class, $generator);
    }

    /**
     * @return void
     * @throws GeneratorException
     */
    public function testIHaveAnExceptionWhenITryToGenerateCodeWithNotDefinedJoinedConfigName()
    {
        $generator = new Generator();
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Invalid orm config file for "user_invalid"');
        $generator->generate($this->getConnectionMock(), $this->getInvalidJoinConfig(), '', false);

        $this->assertInstanceOf(Generator::class, $generator);
    }

    /**
     * @return void
     * @throws GeneratorException
     */
    public function testIHaveAnExceptionWhenITryToGenerateCodeWithNonExistingJoinedConfigName()
    {
        $generator = new Generator();
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Invalid config. Joined config "not_exist" does not exist!');
        $generator->generate($this->getConnectionMock(), $this->getConfigWithMissingJoinedConfig(), '', false);

        $this->assertInstanceOf(Generator::class, $generator);
    }

    /**
     * @return Connection
     */
    private function getConnectionMock(): Connection
    {
        $mockBuilder = $this->getMockBuilder(Connection::class)->disableOriginalConstructor();
        $connection  = $mockBuilder->getMock();

        $connection->method('query')->will(
            $this->returnValueMap(
                [
                    [
                        'SHOW FULL COLUMNS FROM user',
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
                                (object) ['Field' => 'user_password', 'Type' => 'varchar(100)', 'Collation' => 'utf8_unicode_ci', 'Null' => 'NO', 'Key' => '', 'Default' => null, 'Extra' => '', 'Privileges' => '', 'Comment' => ''],
                                (object) ['Field' => 'user_date_create', 'Type' => 'datetime', 'Collation' => null, 'Null' => 'NO', 'Key' => '', 'Default' => null, 'Extra' => '', 'Privileges' => '', 'Comment' => ''],
                                (object) ['Field' => 'user_date_update', 'Type' => 'datetime', 'Collation' => null, 'Null' => 'YES', 'Key' => '', 'Default' => null, 'Extra' => '', 'Privileges' => '', 'Comment' => ''],
                                false,
                            ]
                        )
                    ],
                    [
                        'SHOW FULL COLUMNS FROM address',
                        $this->getPDOStatementMock(
                            [
                                (object) ['Field' => 'address_id', 'Type' => 'int(10) unsigned', 'Collation' => null, 'Null' => 'NO', 'Key' => 'PRI', 'Default' => null, 'Extra' => 'auto_increment', 'Privileges' => '', 'Comment' => ''],
                                (object) ['Field' => 'user_id', 'Type' => 'int(10) unsigned', 'Collation' => null, 'Null' => 'NO', 'Key' => '', 'Default' => null, 'Extra' => '', 'Privileges' => '', 'Comment' => ''],
                                (object) ['Field' => 'address_number', 'Type' => 'varchar(50)', 'Collation' => 'utf8_unicode_ci', 'Null' => 'NO', 'Key' => '', 'Default' => null, 'Extra' => '', 'Privileges' => '', 'Comment' => ''],
                                (object) ['Field' => 'address_text', 'Type' => 'text', 'Collation' => 'utf8_unicode_ci', 'Null' => 'NO', 'Key' => 'UNI', 'Default' => null, 'Extra' => '', 'Privileges' => '', 'Comment' => ''],
                                false,
                                (object) ['Field' => 'address_id', 'Type' => 'int(10) unsigned', 'Collation' => null, 'Null' => 'NO', 'Key' => 'PRI', 'Default' => null, 'Extra' => 'auto_increment', 'Privileges' => '', 'Comment' => ''],
                                (object) ['Field' => 'user_id', 'Type' => 'int(10) unsigned', 'Collation' => null, 'Null' => 'NO', 'Key' => '', 'Default' => 1, 'Extra' => '', 'Privileges' => '', 'Comment' => ''],
                                (object) ['Field' => 'address_number', 'Type' => 'varchar(50)', 'Collation' => 'utf8_unicode_ci', 'Null' => 'NO', 'Key' => '', 'Default' => null, 'Extra' => '', 'Privileges' => '', 'Comment' => ''],
                                (object) ['Field' => 'address_text', 'Type' => 'text', 'Collation' => 'utf8_unicode_ci', 'Null' => 'NO', 'Key' => 'UNI', 'Default' => null, 'Extra' => '', 'Privileges' => '', 'Comment' => ''],
                                false,
                            ]
                        ),
                    ],
                    [
                        'SHOW FULL COLUMNS FROM user_parent',
                        $this->getPDOStatementMock(
                            [
                                (object) ['Field' => 'user_id', 'Type' => 'int(10) unsigned', 'Collation' => null, 'Null' => 'NO', 'Key' => 'PRI', 'Default' => null, 'Extra' => '', 'Privileges' => '', 'Comment' => ''],
                                (object) ['Field' => 'user_id_parent', 'Type' => 'int(10) unsigned', 'Collation' => null, 'Null' => 'NO', 'Key' => 'PRI', 'Default' => null, 'Extra' => '', 'Privileges' => '', 'Comment' => ''],
                                (object) ['Field' => 'user_parent_in_test', 'Type' => 'tinyint(1)', 'Collation' => null, 'Null' => 'NO', 'Key' => '', 'Default' => 1, 'Extra' => '', 'Privileges' => '', 'Comment' => ''],
                                (object) ['Field' => 'user_parent_has_test', 'Type' => 'tinyint(1)', 'Collation' => null, 'Null' => 'NO', 'Key' => '', 'Default' => 1, 'Extra' => '', 'Privileges' => '', 'Comment' => ''],
                                (object) ['Field' => 'user_parent_comment', 'Type' => 'varchar(100)', 'Collation' => null, 'Null' => 'NO', 'Key' => '', 'Default' => 'no comment', 'Extra' => '', 'Privileges' => '', 'Comment' => ''],
                                (object) ['Field' => 'user_parent_bigint', 'Type' => 'bigint(20)', 'Collation' => null, 'Null' => 'NO', 'Key' => '', 'Default' => 0, 'Extra' => '', 'Privileges' => '', 'Comment' => ''],
                                (object) ['Field' => 'user_parent_mediumint', 'Type' => 'mediumint(8)', 'Collation' => null, 'Null' => 'NO', 'Key' => '', 'Default' => 0, 'Extra' => '', 'Privileges' => '', 'Comment' => ''],
                                (object) ['Field' => 'user_parent_smallint', 'Type' => 'smallint(5)', 'Collation' => null, 'Null' => 'NO', 'Key' => '', 'Default' => 0, 'Extra' => '', 'Privileges' => '', 'Comment' => ''],
                                (object) ['Field' => 'user_parent_tinyint', 'Type' => 'tinyint(3)', 'Collation' => null, 'Null' => 'NO', 'Key' => '', 'Default' => 0, 'Extra' => '', 'Privileges' => '', 'Comment' => ''],
                                (object) ['Field' => 'user_parent_float', 'Type' => 'float(5,2)', 'Collation' => null, 'Null' => 'NO', 'Key' => '', 'Default' => 0.0, 'Extra' => '', 'Privileges' => '', 'Comment' => ''],
                                (object) ['Field' => 'user_parent_longtext', 'Type' => 'longtext', 'Collation' => null, 'Null' => 'NO', 'Key' => '', 'Default' => null, 'Extra' => '', 'Privileges' => '', 'Comment' => ''],
                                (object) ['Field' => 'user_parent_mediumtext', 'Type' => 'mediumtext', 'Collation' => null, 'Null' => 'NO', 'Key' => '', 'Default' => null, 'Extra' => '', 'Privileges' => '', 'Comment' => ''],
                                (object) ['Field' => 'user_parent_tinytext', 'Type' => 'tinytext', 'Collation' => null, 'Null' => 'NO', 'Key' => '', 'Default' => null, 'Extra' => '', 'Privileges' => '', 'Comment' => ''],
                                false,
                                (object) ['Field' => 'user_id', 'Type' => 'int(10) unsigned', 'Collation' => null, 'Null' => 'NO', 'Key' => 'PRI', 'Default' => null, 'Extra' => '', 'Privileges' => '', 'Comment' => ''],
                                (object) ['Field' => 'user_id_parent', 'Type' => 'int(10) unsigned', 'Collation' => null, 'Null' => 'NO', 'Key' => 'PRI', 'Default' => null, 'Extra' => '', 'Privileges' => '', 'Comment' => ''],
                                (object) ['Field' => 'user_parent_in_test', 'Type' => 'tinyint(1)', 'Collation' => null, 'Null' => 'NO', 'Key' => '', 'Default' => 1, 'Extra' => '', 'Privileges' => '', 'Comment' => ''],
                                (object) ['Field' => 'user_parent_has_test', 'Type' => 'tinyint(1)', 'Collation' => null, 'Null' => 'NO', 'Key' => '', 'Default' => 1, 'Extra' => '', 'Privileges' => '', 'Comment' => ''],
                                (object) ['Field' => 'user_parent_comment', 'Type' => 'varchar(100)', 'Collation' => null, 'Null' => 'NO', 'Key' => '', 'Default' => 'no comment', 'Extra' => '', 'Privileges' => '', 'Comment' => ''],
                                (object) ['Field' => 'user_parent_bigint', 'Type' => 'bigint(20)', 'Collation' => null, 'Null' => 'NO', 'Key' => '', 'Default' => 0, 'Extra' => '', 'Privileges' => '', 'Comment' => ''],
                                (object) ['Field' => 'user_parent_mediumint', 'Type' => 'mediumint(8)', 'Collation' => null, 'Null' => 'NO', 'Key' => '', 'Default' => 0, 'Extra' => '', 'Privileges' => '', 'Comment' => ''],
                                (object) ['Field' => 'user_parent_smallint', 'Type' => 'smallint(5)', 'Collation' => null, 'Null' => 'NO', 'Key' => '', 'Default' => 0, 'Extra' => '', 'Privileges' => '', 'Comment' => ''],
                                (object) ['Field' => 'user_parent_tinyint', 'Type' => 'tinyint(3)', 'Collation' => null, 'Null' => 'NO', 'Key' => '', 'Default' => 0, 'Extra' => '', 'Privileges' => '', 'Comment' => ''],
                                (object) ['Field' => 'user_parent_float', 'Type' => 'float(5,2)', 'Collation' => null, 'Null' => 'NO', 'Key' => '', 'Default' => 0.0, 'Extra' => '', 'Privileges' => '', 'Comment' => ''],
                                (object) ['Field' => 'user_parent_longtext', 'Type' => 'longtext', 'Collation' => null, 'Null' => 'NO', 'Key' => '', 'Default' => null, 'Extra' => '', 'Privileges' => '', 'Comment' => ''],
                                (object) ['Field' => 'user_parent_mediumtext', 'Type' => 'mediumtext', 'Collation' => null, 'Null' => 'NO', 'Key' => '', 'Default' => null, 'Extra' => '', 'Privileges' => '', 'Comment' => ''],
                                (object) ['Field' => 'user_parent_tinytext', 'Type' => 'tinytext', 'Collation' => null, 'Null' => 'NO', 'Key' => '', 'Default' => null, 'Extra' => '', 'Privileges' => '', 'Comment' => ''],
                                false,
                            ]
                        ),
                    ],
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
                'joins' => [
                    'UserAddress' => [
                        'eager_loading' => true,
                        'config'        => 'address',
                        'relation'      => JoinRelation::MANY,
                        'type'          => JoinType::INNER,
                        'keys'          => ['user_id' => true], // or ['user_id' => 'user_id'],
                    ],
                    'UserParent' => [
                        'eager_loading' => false,
                        'config'        => 'user_parent',
                        'relation'      => JoinRelation::ONE,
                        'type'          => JoinType::INNER,
                        'keys'          => ['user_id' => 'user_id_parent'],
                    ],
                ],

                'validation' => [
                    'enabled'             => true,
                    'auto'                => true,
                    'extended_validation' => null,
                ],
            ],
            'address' => [
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
                    'prefix'     => 'test.address',
                ],
                'database' => [
                    'table'      => 'address',
                    'prefix'     => ['address'],
                ],
                'class' => [
                    'classname'  => 'Address',
                ],
                'joins' => [],
                'validation' => [
                    'enabled'             => true,
                    'auto'                => true,
                    'extended_validation' => null,
                ],
            ],
            'user_parent' => [
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
                    'prefix'     => 'test.user_parent',
                ],
                'database' => [
                    'table'      => 'user_parent',
                    'prefix'     => ['user_parent'],
                ],
                'class' => [
                    'classname'  => 'UserParent',
                ],
                'joins' => [],
                'validation' => [
                    'enabled'             => true,
                    'auto'                => true,
                    'extended_validation' => [
                        'user_parent_comment' => [
                            'type' => 'string',
                            'options' => [
                                'min_length' => '1',
                            ],
                        ],
                    ],
                ],
            ]
        ];
    }

    /**
     * @return array
     */
    private function getInvalidJoinConfig(): array
    {
        return [
            'user_invalid' => [
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
                'joins' => [
                    'UserAddress' => [
                        'eager_loading' => true,
                        'relation'      => JoinRelation::MANY,
                        'type'          => JoinType::INNER,
                        'keys'          => ['user_id' => true], // or ['user_id' => 'user_id'],
                    ],
                ],

                'validation' => [
                    'enabled'             => true,
                    'auto'                => true,
                    'extended_validation' => null,
                ],
            ],
            'address' => [
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
                    'prefix'     => 'test.address',
                ],
                'database' => [
                    'table'      => 'address',
                    'prefix'     => ['address'],
                ],
                'class' => [
                    'classname'  => 'Address',
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

    /**
     * @return array
     */
    private function getConfigWithMissingJoinedConfig(): array
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
                'joins' => [
                    'UserAddress' => [
                        'eager_loading' => true,
                        'config'        => 'not_exist',
                        'relation'      => JoinRelation::MANY,
                        'type'          => JoinType::INNER,
                        'keys'          => ['user_id' => true],
                    ],
                ],

                'validation' => [
                    'enabled'             => true,
                    'auto'                => true,
                    'extended_validation' => null,
                ],
            ],
        ];
    }
}
