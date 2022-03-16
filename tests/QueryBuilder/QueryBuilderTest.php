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
use Eureka\Component\Database\ConnectionFactory;
use Eureka\Component\Orm\Enumerator\JoinType;
use Eureka\Component\Orm\Exception\EmptySetClauseException;
use Eureka\Component\Orm\Exception\EmptyWhereClauseException;
use Eureka\Component\Orm\Exception\InvalidQueryException;
use Eureka\Component\Orm\Exception\OrmException;
use Eureka\Component\Orm\Query\Factory;
use Eureka\Component\Orm\Query\InsertBuilder;
use Eureka\Component\Orm\Tests\Generated\Infrastructure\Mapper\UserMapper;
use Eureka\Component\Orm\Tests\Generated\Repository\UserRepositoryInterface;
use Eureka\Component\Validation\Entity\ValidatorEntityFactory;
use Eureka\Component\Validation\ValidatorFactory;
use PHPUnit\Framework\TestCase;

/**
 * Class QueryBuilderTest
 *
 * @author Romain Cottard
 */
class QueryBuilderTest extends TestCase
{
    /**
     * @return void
     * @throws OrmException
     */
    public function testIHaveAnErrorWhenITryToUseQueryBuilderFactoryWithUnknownType(): void
    {
        $repository = $this->getUserRepository($this->getMockEntityFindAll());

        $this->expectException(OrmException::class);
        $this->expectExceptionMessage('Unknown query builder type!');

        Factory::getBuilder('unknown_type', $repository);
    }

    /**
     * @return void
     * @throws OrmException
     */
    public function testICanInstantiateQueryBuilderWithFactory(): void
    {
        $repository = $this->getUserRepository($this->getMockEntityFindAll());

        $queryBuilder = Factory::getBuilder(Factory::TYPE_QUERY, $repository);
        $queryBuilder->setListIndexedByField('user_id');
        $queryBuilder->bind([':user_id' => 1]);

        $this->assertSame('user_id', $queryBuilder->getListIndexedByField());
        $this->assertSame([':user_id' => 1], $queryBuilder->getBind());
    }

    /**
     * @return void
     * @throws OrmException
     */
    public function testICanGetWellFormattedInsertQueryFromInsertQueryBuilder(): void
    {
        $repository = $this->getUserRepository($this->getMockEntityFindAll());

        /** @var InsertBuilder $queryBuilder */
        $queryBuilder = Factory::getBuilder(Factory::TYPE_INSERT, $repository);
        $queryBuilder->addSet('user_id', 1);
        $queryBuilder->addSet('user_name', 'test');

        //~ Basic query
        $suffix = '\_[a-z0-9]{13}';
        $insertQuery  = $queryBuilder->getQuery();
        $patternQuery = "/INSERT INTO user SET `user\_id` = :user\_id{$suffix}\, `user\_name` = :user\_name{$suffix}/";
        $this->assertMatchesRegularExpression($patternQuery, $insertQuery);

        //~ Query with IGNORE for duplicate
        $insertQuery  = $queryBuilder->getQuery(false, true);
        $patternQuery = "/INSERT IGNORE INTO user SET `user\_id` = :user\_id{$suffix}\, `user\_name` = :user\_name{$suffix}/";
        $this->assertMatchesRegularExpression($patternQuery, $insertQuery);

        //~ Query with IGNORE for duplicate
        $queryBuilder->addUpdate('user_name', 'test');
        $insertQuery  = $queryBuilder->getQuery(true, false);
        $patternQuery = "/INSERT INTO user SET `user\_id` = :user\_id{$suffix}\, `user\_name` = :user\_name{$suffix} ON DUPLICATE KEY UPDATE `user\_name` = :user\_name{$suffix}/";
        $this->assertMatchesRegularExpression($patternQuery, $insertQuery);
    }

    /**
     * @return void
     * @throws OrmException
     */
    public function testICanGetWellFormattedInsertQueryFromInsertQueryBuilderWithEntity(): void
    {
        $repository = $this->getUserRepository($this->getMockEntityFindAll());
        $user       = $repository->newEntity(
            (object) [
                'user_id'          => 1,
                'user_is_enabled'  => true,
                'user_email'       => 'user@example.com',
                'user_password'    => md5('password'),
                'user_date_create' => '2020-01-01 10:00:00',
                'user_date_update' => null,
            ]
        );

        /** @var InsertBuilder $queryBuilder */
        $queryBuilder = Factory::getBuilder(Factory::TYPE_INSERT, $repository, $user);
        $query = $queryBuilder->getQuery(true);

        $this->assertIsString($query);
    }

    /**
     * @return void
     * @throws OrmException
     */
    public function testICanGetQueryFromQueryBuilder(): void
    {
        $repository = $this->getUserRepository($this->getMockEntityFindId1());
        $queryBuilder = Factory::getBuilder(Factory::TYPE_QUERY, $repository);
        $queryBuilder->addFrom('user');
        $queryBuilder->addField('user_id');

        $query = $queryBuilder->getQuery();
        $expected = 'SELECT `user_id` FROM `user`';
        $this->assertSame($expected, $query);
    }

    /**
     * @return void
     * @throws OrmException
     */
    public function testICanUseQueryFieldsPersonalizedParameters(): void
    {
        $repository = $this->getUserRepository($this->getMockEntityFindId1());
        $queryBuilder = Factory::getBuilder(Factory::TYPE_QUERY, $repository);
        $queryField = $queryBuilder->getQueryFieldsPersonalized(['user_id' => 'usr_id', 'user_name' => '']);

        $this->assertSame('`user_id` AS `usr_id`, `user_name`', $queryField);
    }

    /**
     * @return void
     * @throws OrmException
     */
    public function testICanGetQueryFields(): void
    {
        $repository   = $this->getUserRepository($this->getMockEntityFindId1());
        $queryBuilder = Factory::getBuilder(Factory::TYPE_QUERY, $repository);
        $queryBuilder->addField('user_id');
        $queryField   = $queryBuilder->getQueryFields($repository);

        $this->assertSame('`user_id`', $queryField);
    }

    /**
     * @return void
     * @throws OrmException
     */
    public function testICanGetQueryFieldsWithOnlyPrefixedPrimaryKeys(): void
    {
        $repository   = $this->getUserRepository($this->getMockEntityFindId1());
        $queryBuilder = Factory::getBuilder(Factory::TYPE_QUERY, $repository);
        $queryField   = $queryBuilder->getQueryFields($repository, true, true);

        $this->assertSame('user.user_id', $queryField);
    }

    /**
     * @return void
     * @throws OrmException
     */
    public function testICanGetQueryFieldsWithOnlyCustomPrefixedPrimaryKeys(): void
    {
        $repository   = $this->getUserRepository($this->getMockEntityFindId1());
        $queryBuilder = Factory::getBuilder(Factory::TYPE_QUERY, $repository);
        $queryField   = $queryBuilder->getQueryFieldsList($repository, true, true, 'usr_alias', '_suffix');

        $this->assertSame('usr_alias.user_id AS user_id_suffix', $queryField[0]);
    }

    /**
     * @return void
     * @throws OrmException
     */
    public function testICanEnableAndDisableRowFoundCalculationForQuery(): void
    {
        $repository = $this->getUserRepository($this->getMockEntityFindId1());
        $queryBuilder = Factory::getBuilder(Factory::TYPE_QUERY, $repository);

        $queryBuilder->enableCalculateFoundRows();
        $queryField = $queryBuilder->getQueryFieldsPersonalized(['user_id' => 'usr_id', 'user_name' => '']);
        $this->assertSame('SQL_CALC_FOUND_ROWS `user_id` AS `usr_id`, `user_name`', $queryField);

        $queryBuilder->clear();
        $queryBuilder->disableCalculateFoundRows();
        $queryField = $queryBuilder->getQueryFieldsPersonalized(['user_id' => 'usr_id', 'user_name' => '']);
        $this->assertSame('`user_id` AS `usr_id`, `user_name`', $queryField);
    }

    /**
     * @return void
     * @throws OrmException
     */
    public function testICanAddOrderToQueryBuilder(): void
    {
        $repository = $this->getUserRepository($this->getMockEntityFindId1());
        $queryBuilder = Factory::getBuilder(Factory::TYPE_QUERY, $repository);
        $queryBuilder->addOrder('user_id', 'ASC');

        $this->assertSame(' ORDER BY user_id ASC', $queryBuilder->getQueryOrderBy());
    }

    /**
     * @return void
     * @throws OrmException
     */
    public function testICanAddGroupByToQueryBuilder(): void
    {
        $repository = $this->getUserRepository($this->getMockEntityFindId1());
        $queryBuilder = Factory::getBuilder(Factory::TYPE_QUERY, $repository);
        $queryBuilder->addGroupBy('user_id');

        $this->assertSame(' GROUP BY user_id ', $queryBuilder->getQueryGroupBy());
    }

    /**
     * @return void
     * @throws OrmException
     */
    public function testICanAddHavingClauseToQueryBuilder(): void
    {
        $repository = $this->getUserRepository($this->getMockEntityFindId1());
        $queryBuilder = Factory::getBuilder(Factory::TYPE_QUERY, $repository);
        $queryBuilder->addHaving('user_id', 0, '>');

        $suffix = '\_[a-z0-9]{13}';
        $patternQuery = "/ HAVING user\_id > :user\_id{$suffix} /";
        $this->assertMatchesRegularExpression($patternQuery, $queryBuilder->getQueryHaving());
    }

    /**
     * @return void
     * @throws OrmException
     */
    public function testICanAddJoinToQueryBuilder(): void
    {
        $repository = $this->getUserRepository($this->getMockEntityFindId1());
        $queryBuilder = Factory::getBuilder(Factory::TYPE_QUERY, $repository);
        $queryBuilder->addJoin(JoinType::INNER, 'address', 'user_id', 'user', 'user_id', 'address');

        $expected = ' INNER JOIN address AS address ON user.user_id = address.user_id ';
        $this->assertSame($expected, $queryBuilder->getQueryJoin());
    }

    /**
     * @return void
     * @throws OrmException
     */
    public function testICanAddWhereWithRegexpTypeToQueryBuilder(): void
    {
        $repository = $this->getUserRepository($this->getMockEntityFindId1());
        $queryBuilder = Factory::getBuilder(Factory::TYPE_QUERY, $repository);
        $queryBuilder->addWhere('user_name', 'test[a-z]', 'REGEXP');

        $this->assertSame(' WHERE user_name REGEXP \'test[a-z]\' ', $queryBuilder->getQueryWhere());
    }

    /**
     * @return void
     * @throws OrmException
     */
    public function testICanAddWhereRawToQueryBuilder(): void
    {
        $repository = $this->getUserRepository($this->getMockEntityFindId1());
        $queryBuilder = Factory::getBuilder(Factory::TYPE_QUERY, $repository);
        $queryBuilder->addWhereRaw('user_id IS NULL');
        $queryBuilder->addWhereRaw('user_name LIKE "test"');

        $this->assertSame(' WHERE user_id IS NULL  AND user_name LIKE "test" ', $queryBuilder->getQueryWhere());
    }

    /**
     * @return void
     * @throws OrmException
     */
    public function testICanAddWhereKeysOrToQueryBuilder(): void
    {
        $repository = $this->getUserRepository($this->getMockEntityFindId1());
        $queryBuilder = Factory::getBuilder(Factory::TYPE_QUERY, $repository);
        $queryBuilder->addWhereKeysOr(['primary_key_1' => 1, 'primary_key_2' => 'a']);
        $queryBuilder->addWhereKeysOr(['primary_key_1' => 2, 'primary_key_2' => 'b']);

        $suffix = '\_[a-z0-9]{13}';
        $patternQuery = "/ WHERE  \(primary\_key\_1 = :primary\_key\_1{$suffix} AND primary\_key\_2 = :primary\_key\_2{$suffix}\)   OR  \(primary\_key\_1 = :primary\_key\_1{$suffix} AND primary\_key\_2 = :primary\_key\_2{$suffix}\)  /";
        $this->assertMatchesRegularExpression($patternQuery, $queryBuilder->getQueryWhere());
    }

    /**
     * @return void
     * @throws OrmException
     */
    public function testIHaveAnExceptionWhenITryToSetAddInWithAnEmptyArray(): void
    {
        $repository = $this->getUserRepository($this->getMockEntityFindId1());
        $queryBuilder = Factory::getBuilder(Factory::TYPE_QUERY, $repository);

        $this->expectException(InvalidQueryException::class);
        $this->expectExceptionMessage('Values for addIn must be non empty!');
        $queryBuilder->addIn('user_id', []);
    }

    /**
     * @return void
     * @throws OrmException
     */
    public function testIHaveAnExceptionWhenITryGetQueryWhereWithoutWhereAdded(): void
    {
        $repository = $this->getUserRepository($this->getMockEntityFindId1());
        $queryBuilder = Factory::getBuilder(Factory::TYPE_QUERY, $repository);
        $this->expectException(EmptyWhereClauseException::class);
        $queryBuilder->getQueryWhere(true);
    }

    /**
     * @return void
     * @throws OrmException
     */
    public function testIHaveAnExceptionWhenITryGetQuerySetWithoutSetAdded(): void
    {
        $repository = $this->getUserRepository($this->getMockEntityFindId1());
        $queryBuilder = Factory::getBuilder(Factory::TYPE_INSERT, $repository);
        $this->expectException(EmptySetClauseException::class);
        $queryBuilder->getQuerySet();
    }

    /**
     * @param array $entityMock
     * @return ConnectionFactory
     */
    private function getConnectionFactoryMock(array $entityMock = []): ConnectionFactory
    {
        if (empty($entityMock)) {
            $entityMock = $this->getMockEntityNone();
        }

        $count = (count($entityMock) - 1);

        $statementMock = $this->getMockBuilder(\PDOStatement::class)->getMock();
        $statementMock->method('execute')->willReturn(true);
        $statementMock->method('rowCount')->willReturn($count);
        $statementMock->method('fetch')->willReturnOnConsecutiveCalls(...$entityMock);
        $statementMock->method('fetchColumn')->willReturn($count);

        $mockBuilder = $this->getMockBuilder(Connection::class)->disableOriginalConstructor();
        $connection  = $mockBuilder->getMock();
        $connection->method('prepare')->willReturn($statementMock);
        $connection->method('lastInsertId')->willReturn('1');

        $mockBuilder = $this->getMockBuilder(ConnectionFactory::class)->disableOriginalConstructor();
        $connectionFactory = $mockBuilder->getMock();
        $connectionFactory->method('getConnection')->willReturn($connection);

        return $connectionFactory;
    }

    /**
     * @param array $entityMock
     * @return UserRepositoryInterface
     */
    private function getUserRepository(array $entityMock = []): UserRepositoryInterface
    {
        $connectionFactory = $this->getConnectionFactoryMock($entityMock);
        return new UserMapper(
            'common',
            $connectionFactory,
            new ValidatorFactory(),
            new ValidatorEntityFactory(new ValidatorFactory()),
            [],
            null,
            false,
        );
    }

    private function getMockEntityNone(): array
    {
        return [false];
    }

    /**
     * @return array
     */
    private function getMockEntityFindId1(): array
    {
        return [
            (object) [
                'user_id'          => 1,
                'user_is_enabled'  => true,
                'user_email'       => 'user@example.com',
                'user_password'    => md5('password'),
                'user_date_create' => '2020-01-01 10:00:00',
                'user_date_update' => null,
            ],
            false,
        ];
    }

    /**
     * @return array
     */
    private function getMockEntityFindAll(): array
    {
        return [
            (object) [
                'user_id'          => 1,
                'user_is_enabled'  => true,
                'user_email'       => 'user@example.com',
                'user_password'    => md5('password'),
                'user_date_create' => '2020-01-01 10:00:00',
                'user_date_update' => null,
            ],
            (object) [
                'user_id'          => 2,
                'user_is_enabled'  => true,
                'user_email'       => 'user02@example.com',
                'user_password'    => md5('password'),
                'user_date_create' => '2020-01-02 10:00:00',
                'user_date_update' => null,
            ],
            false,
        ];
    }

    /**
     * @return void
     */
    public function setUp(): void
    {
        //~ Entity
        require_once __DIR__ . '/../Generated/Entity/Abstracts/AbstractUser.php';
        require_once __DIR__ . '/../Generated/Entity/Abstracts/AbstractUserParent.php';
        require_once __DIR__ . '/../Generated/Entity/Abstracts/AbstractAddress.php';
        require_once __DIR__ . '/../Generated/Entity/User.php';
        require_once __DIR__ . '/../Generated/Entity/UserParent.php';
        require_once __DIR__ . '/../Generated/Entity/Address.php';

        //~ Repository
        require_once __DIR__ . '/../Generated/Repository/UserRepositoryInterface.php';
        require_once __DIR__ . '/../Generated/Repository/UserParentRepositoryInterface.php';
        require_once __DIR__ . '/../Generated/Repository/AddressRepositoryInterface.php';

        //~ Mapper
        require_once __DIR__ . '/../Generated/Infrastructure/Mapper/Abstracts/AbstractUserMapper.php';
        require_once __DIR__ . '/../Generated/Infrastructure/Mapper/Abstracts/AbstractUserParentMapper.php';
        require_once __DIR__ . '/../Generated/Infrastructure/Mapper/Abstracts/AbstractAddressMapper.php';
        require_once __DIR__ . '/../Generated/Infrastructure/Mapper/UserMapper.php';
        require_once __DIR__ . '/../Generated/Infrastructure/Mapper/UserParentMapper.php';
        require_once __DIR__ . '/../Generated/Infrastructure/Mapper/AddressMapper.php';
    }
}
