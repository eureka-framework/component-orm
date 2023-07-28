<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Eureka\Component\Orm\Tests\Mapper;

use Eureka\Component\Database\Connection;
use Eureka\Component\Database\ConnectionFactory;
use Eureka\Component\Orm\AbstractMapper;
use Eureka\Component\Orm\Exception\EntityNotExistsException;
use Eureka\Component\Orm\Exception\OrmException;
use Eureka\Component\Orm\Exception\UndefinedMapperException;
use Eureka\Component\Orm\MapperInterface;
use Eureka\Component\Orm\Query\QueryBuilder;
use Eureka\Component\Orm\Query\SelectBuilder;
use Eureka\Component\Orm\RepositoryInterface;
use Eureka\Component\Orm\Tests\Generated\Entity\User;
use Eureka\Component\Orm\Tests\Generated\Infrastructure\Mapper\UserMapper;
use Eureka\Component\Orm\Tests\Generated\Infrastructure\Mapper\UserParentMapper;
use Eureka\Component\Orm\Tests\Generated\Repository\UserParentRepositoryInterface;
use Eureka\Component\Orm\Tests\Generated\Repository\UserRepositoryInterface;
use Eureka\Component\Validation\Entity\ValidatorEntityFactory;
use Eureka\Component\Validation\ValidatorFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub\Exception;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

/**
 * Class MapperTest
 *
 * @author Romain Cottard
 */
class MapperTest extends TestCase
{
    /**
     * @return void
     */
    public function testICanInstantiateUserMapper(): void
    {
        $repository = $this->getUserRepository();
        $repository->disableCacheOnRead();
        $repository->enableCacheOnRead();

        $this->assertInstanceOf(UserMapper::class, $repository);
        $this->assertInstanceOf(AbstractMapper::class, $repository);
        $this->assertInstanceOf(UserRepositoryInterface::class, $repository);
        $this->assertInstanceOf(MapperInterface::class, $repository);
    }

    /**
     * @return void
     * @throws OrmException
     */
    public function testICanInsertEntity(): void
    {
        $repository = $this->getUserRepository();

        /** @var User $user */
        $user = $repository->newEntity(
            (object) [
                'user_id'          => 1,
                'user_is_enabled'  => true,
                'user_email'       => 'user@example.com',
                'user_password'    => md5('password'),
                'user_date_create' => '2020-01-01 10:00:00',
                'user_date_update' => null,
            ]
        );

        $this->assertFalse($user->exists(), 'User should not exists');
        $repository->persist($user);
        $this->assertTrue($user->exists(), 'User should exists');
        $repository->insert($user);
    }

    /**
     * @return void
     * @throws OrmException
     */
    public function testICanInsertUpdateEntity(): void
    {
        $repository = $this->getUserRepository();

        /** @var User $user */
        $user = $repository->newEntity(
            (object) [
                'user_id'          => 1,
                'user_is_enabled'  => true,
                'user_email'       => 'user@example.com',
                'user_password'    => md5('password'),
                'user_date_create' => '2020-01-01 10:00:00',
                'user_date_update' => null,
            ],
            true
        );

        $repository->persist($user); // nothing happen, entity is not updated

        $user->setDateUpdate('2020-01-01 10:00:00');
        $this->assertTrue($user->isUpdated());
        $repository->persist($user);
        $this->assertFalse($user->isUpdated());
    }

    /**
     * @return void
     * @throws OrmException
     */
    public function testICanDeleteEntity(): void
    {
        $repository = $this->getUserRepository();

        /** @var User $user */
        $user = $repository->newEntity(
            (object) [
                'user_id'          => 1,
                'user_is_enabled'  => true,
                'user_email'       => 'user@example.com',
                'user_password'    => md5('password'),
                'user_date_create' => '2020-01-01 10:00:00',
                'user_date_update' => null,
            ],
            true
        );

        $this->assertTrue($user->exists(), 'User should always exists');
        $repository->delete($user);
        $this->assertFalse($user->exists(), 'User should not exists anymore');
    }

    /**
     * @return void
     * @throws OrmException
     */
    public function testICanFindEntityById(): void
    {
        $repository = $this->getUserRepository($this->getMockEntityFindId1());

        /** @var User $user */
        $user = $repository->findById(1);

        /** @var User $user */
        $expected = $repository->newEntity(
            (object) [
                'user_id'          => 1,
                'user_is_enabled'  => true,
                'user_email'       => 'user@example.com',
                'user_password'    => md5('password'),
                'user_date_create' => '2020-01-01 10:00:00',
                'user_date_update' => null,
            ],
            true
        );
        $this->assertEquals($expected, $user);

        //~ Then retrieve from cache
        /** @var User $user */
        $user = $repository->findById(1);
        $this->assertEquals($expected, $user);
        $this->assertSame(1, $repository->rowCount());
    }

    /**
     * @return void
     * @throws OrmException
     */
    public function testICanFindEntityByIdWithoutCacheEnabledNorCache(): void
    {
        $repository = $this->getUserRepositoryNoCache($this->getMockEntityFindId1(false));

        /** @var User $user */
        $user = $repository->findById(1);

        /** @var User $user */
        $expected = $repository->newEntity(
            (object) [
                'user_id'          => 1,
                'user_is_enabled'  => true,
                'user_email'       => 'user@example.com',
                'user_password'    => md5('password'),
                'user_date_create' => '2020-01-01 10:00:00',
                'user_date_update' => null,
            ],
            true
        );
        $this->assertEquals($expected, $user);

        $repository->delete($user);
        $this->assertFalse($user->exists());
    }

    /**
     * @return void
     * @throws OrmException
     */
    public function testIHaveAnExceptionWhenITryToFindNotExistingEntity(): void
    {
        $repository = $this->getUserRepository($this->getMockEntityNone());

        $this->expectException(EntityNotExistsException::class);
        $repository->findById(1);
    }

    /**
     * @return void
     * @throws OrmException
     */
    public function testICanFindEntitiesByKeys(): void
    {
        $repository = $this->getUserRepositoryNoCache($this->getMockEntityFindAll());

        $users = $repository->findAllByKeys(['user_id' => 1]);

        $this->assertCount(2, $users);
    }

    /**
     * @return void
     * @throws OrmException
     */
    public function testICanGetListOfEntityIndexedByGivenFieldWhenExecuteQuery(): void
    {
        $repository = $this->getUserRepository($this->getMockEntityFindAll(false), false);
        $collection = $repository->query((new SelectBuilder($repository))->setListIndexedByField('user_email'));

        $expected = [
            'user@example.com' => $repository->newEntity(
                (object) [
                    'user_id'          => 1,
                    'user_is_enabled'  => true,
                    'user_email'       => 'user@example.com',
                    'user_password'    => md5('password'),
                    'user_date_create' => '2020-01-01 10:00:00',
                    'user_date_update' => null,
                ],
                true
            ),
            'user02@example.com' => $repository->newEntity(
                (object) [
                    'user_id'          => 2,
                    'user_is_enabled'  => true,
                    'user_email'       => 'user02@example.com',
                    'user_password'    => md5('password'),
                    'user_date_create' => '2020-01-02 10:00:00',
                    'user_date_update' => null,
                ],
                true
            ),
        ];

        $this->assertEquals($expected, $collection);
    }

    /**
     * @return void
     * @throws OrmException
     */
    public function testICanGetListOfEntityIndexedByGivenFieldWhenExecuteSelect(): void
    {
        $repository = $this->getUserRepository($this->getMockEntityFindAll());
        $builder    = new SelectBuilder($repository);
        $builder->addWhere('user_id', 1)->setListIndexedByField('user_id');

        $users = $repository->select($builder);
        $ids   = array_keys($users);

        $this->assertCount(2, $users);
        $this->assertSame($ids[0], $users[$ids[0]]->getId());
        $this->assertSame($ids[1], $users[$ids[1]]->getId());

        //~ Also test when retrieve all entities from cache
        $builder = new SelectBuilder($repository);
        $builder->addWhere('user_id', 1)->setListIndexedByField('user_id');

        /** @var User[] $users */
        $users = $repository->select($builder);

        $this->assertCount(2, $users);
        $this->assertSame($ids[0], $users[$ids[0]]->getId());
        $this->assertSame($ids[1], $users[$ids[1]]->getId());
    }

    /**
     * @return void
     * @throws OrmException
     */
    public function testIHaveAnExceptionWhenITryToExecuteQueryAndIndexResultByNonExistingField(): void
    {
        $repository = $this->getUserRepository($this->getMockEntityFindAll(false), false);

        $this->expectException(OrmException::class);
        $this->expectExceptionMessage('List is supposed to be indexed by a column that does not exist: unknown_field');
        $repository->query((new SelectBuilder($repository))->setListIndexedByField('unknown_field'));
    }

    /**
     * @return void
     * @throws OrmException
     */
    public function testICanCheckIfRowExists(): void
    {
        $repository = $this->getUserRepository($this->getMockEntityFindId1());

        $this->assertTrue($repository->rowExists((new SelectBuilder($repository))->addWhere('user_id', 1)));
    }

    /**
     * @return void
     */
    public function testICanCountRows(): void
    {
        $repository = $this->getUserRepository($this->getMockEntityFindAll());

        $this->assertSame(2, $repository->count(new QueryBuilder($repository)));
    }

    /**
     * @return void
     * @throws OrmException
     */
    public function testICanCheckIfRowDoesNotExists(): void
    {
        $repository = $this->getUserRepository($this->getMockEntityNone());

        $this->assertFalse($repository->rowExists((new SelectBuilder($repository))->addWhere('user_id', 1)));
    }

    /**
     * @return void
     */
    public function testICanGetMaxPrimaryKeyIdForARepository(): void
    {
        $repository = $this->getUserRepository($this->getMockEntityFindId1());

        $this->assertSame(1, $repository->getMaxId());
    }

    /**
     * @return void
     */
    public function testAReconnectionIsMadeAutomaticallyWhenConnectionIsLostAndITryToExecuteAQuery(): void
    {
        $repository = $this->getUserRepository($this->getMockEntityFindId1(), true, 2006);

        $this->assertSame(1, $repository->getMaxId());
    }

    /**
     * @return void
     * @throws OrmException
     */
    public function testAReconnectionIsMadeAutomaticallyWhenConnectionIsLostAndITryToExecuteAQueryWithResult(): void
    {
        /** @var \stdClass[] $entities */
        $entities   = $this->getMockEntityFindId1();
        $repository = $this->getUserRepository($entities, true, 2006);

        /** @var User $entity */
        $entity = $repository->newEntity($entities[2], true);
        $entity->setExists(true);
        $entity->setEmail('new@email.com');

        $this->assertTrue($repository->persist($entity));
    }

    /**
     * @return void
     */
    public function testIHaveAnExceptionWhenITryToGetMaxPrimaryKeyIdOnRepositoryWithMultiPrimaryKeys(): void
    {
        $repository = $this->getUserParentRepository($this->getMockEntityNone());

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Cannot use getMaxId() method for table with multiple primary keys !');
        $repository->getMaxId();
    }

    /**
     * @return void
     */
    public function testIHaveAnExceptionWhenITryToGetNamesMapForNonExistingField(): void
    {
        $repository = $this->getUserRepository($this->getMockEntityNone());

        $this->expectException(\OutOfRangeException::class);
        $this->expectExceptionMessage('Specified field does not exist in data names map');

        $repository->getNamesMap('unknown_field');
    }

    /**
     * @return void
     */
    public function testIHaveAnExceptionWhenITryToGetNonExistingMapper(): void
    {
        $repository = $this->getUserRepository($this->getMockEntityNone());

        $this->expectException(UndefinedMapperException::class);
        $this->expectExceptionMessage('Mapper does not exist! (mapper: \Unknown\Mapper\ClassName)');

        /** @var class-string<RepositoryInterface> $mapperClass */
        $mapperClass = '\Unknown\Mapper\ClassName';
        $repository->getMapper($mapperClass);
    }

    /**
     * @return void
     * @throws EntityNotExistsException
     * @throws OrmException
     */
    public function testIHaveAnExceptionWhenITryToGetQueryWithAnError(): void
    {
        $repository = $this->getUserRepository($this->getMockEntityFindId1(), false, 1);

        $this->expectException(\PDOException::class);

        $repository->findById(1);
    }

    /**
     * @return void
     * @throws OrmException
     */
    public function testIHaveAnExceptionWhenITryToGetQueryWithResultWithAnError(): void
    {
        /** @var array{
         *     0: \stdClass,
         *     1: bool,
         *     2: \stdClass,
         *     3: bool,
         *     4: \stdClass,
         *     5: bool,
         *     6: \stdClass,
         *     7: bool
         * } $entities */
        $entities = $this->getMockEntityFindId1();
        $repository = $this->getUserRepository($entities, true, 1);

        /** @var User $entity */
        $entity = $repository->newEntity($entities[2], true);
        $entity->setExists(true);
        $entity->setEmail('new@email.com');

        $this->expectException(\PDOException::class);

        $repository->persist($entity);
    }

    /**
     * @param array<mixed> $entityMock
     * @param bool $includeCacheMock
     * @param int $exceptionCode
     * @return ConnectionFactory
     */
    private function getConnectionFactoryMock(
        array $entityMock = [],
        bool $includeCacheMock = true,
        int $exceptionCode = 0
    ): ConnectionFactory {
        if (empty($entityMock)) {
            $entityMock = $this->getMockEntityNone();
        }

        if ($includeCacheMock) {
            $count = (int) ((count($entityMock) - 4) / 4);
        } else {
            $count = (int) ((count($entityMock) - 2) / 2);
        }

        $statementMock = $this->getMockBuilder(\PDOStatement::class)->getMock();
        $statementMock->method('rowCount')->willReturn($count);
        $statementMock->method('fetch')->willReturnOnConsecutiveCalls(...$entityMock);
        $statementMock->method('fetchColumn')->willReturn($count);

        if ($exceptionCode > 0) {
            $exception = new \PDOException('Exception', $exceptionCode);
            $exception->errorInfo = [0 => 'HY000', 1 => $exceptionCode, 2 => 'Exception'];
            $statementMock->expects($this->exactly($exceptionCode === 2006 ? 2 : 1))->method('execute')->willReturnOnConsecutiveCalls(...
                [
                    new Exception($exception),
                    true,
                ]);
        } else {
            $statementMock->method('execute')->willReturn(true);
        }

        $mockBuilder = $this->getMockBuilder(Connection::class)->disableOriginalConstructor();
        $connection  = $mockBuilder->getMock();
        $connection->method('prepare')->willReturn($statementMock);
        $connection->method('lastInsertId')->willReturn('1');
        $connection->method('inTransaction')->willReturn(false);

        $mockBuilder = $this->getMockBuilder(ConnectionFactory::class)->disableOriginalConstructor();
        /** @var ConnectionFactory&MockObject $connectionFactory */
        $connectionFactory = $mockBuilder->getMock();
        $connectionFactory->method('getConnection')->willReturn($connection);

        return $connectionFactory;
    }

    /**
     * @param array<mixed> $entityMock
     * @param bool $includeCacheMock
     * @param int $exceptionCode
     * @return UserRepositoryInterface
     */
    private function getUserRepository(
        array $entityMock = [],
        bool $includeCacheMock = true,
        int $exceptionCode = 0
    ): UserRepositoryInterface {
        $connectionFactory = $this->getConnectionFactoryMock($entityMock, $includeCacheMock, $exceptionCode);
        return new UserMapper(
            'common',
            $connectionFactory,
            new ValidatorFactory(),
            new ValidatorEntityFactory(new ValidatorFactory()),
            [],
            new ArrayAdapter(),
            true,
        );
    }

    /**
     * @param array<mixed> $entityMock
     * @return UserParentRepositoryInterface
     */
    private function getUserParentRepository(array $entityMock = []): UserParentRepositoryInterface
    {
        $connectionFactory = $this->getConnectionFactoryMock($entityMock, false);
        return new UserParentMapper(
            'common',
            $connectionFactory,
            new ValidatorFactory(),
            new ValidatorEntityFactory(new ValidatorFactory()),
            []
        );
    }

    /**
     * @param array<mixed> $entityMock
     * @return UserRepositoryInterface
     */
    private function getUserRepositoryNoCache(array $entityMock = []): UserRepositoryInterface
    {
        $connectionFactory = $this->getConnectionFactoryMock($entityMock, false);
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

    /**
     * @return false[]
     */
    private function getMockEntityNone(): array
    {
        return [false];
    }

    /**
     * @param bool $includeCacheMock
     * @return array{
     *     0: \stdClass,
     *     1: bool,
     *     2: \stdClass,
     *     3: bool,
     *     4: \stdClass,
     *     5: bool,
     *     6: \stdClass,
     *     7: bool
     * }|array{
     *     0: \stdClass,
     *     1: bool,
     * }
     */
    private function getMockEntityFindId1(bool $includeCacheMock = true): array
    {
        if (!$includeCacheMock) {
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

        // double for cache test
        return [
            (object) [
                'user_id' => 1,
            ],
            false,
            (object) [
                'user_id'          => 1,
                'user_is_enabled'  => true,
                'user_email'       => 'user@example.com',
                'user_password'    => md5('password'),
                'user_date_create' => '2020-01-01 10:00:00',
                'user_date_update' => null,
            ],
            false,
            (object) [
                'user_id' => 1,
            ],
            false,
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
     * @param bool $includeCacheMock
     * @return array{
     *     0: \stdClass,
     *     1: \stdClass,
     *     2: bool,
     *     3: \stdClass,
     *     4: \stdClass,
     *     5: bool,
     *     6: \stdClass,
     *     7: \stdClass,
     *     8: bool,
     *     9: \stdClass,
     *     10: \stdClass,
     *     11: bool
     * }|array{
     *     0: \stdClass,
     *     1: \stdClass,
     *     2: bool,
     * }
     */
    private function getMockEntityFindAll(bool $includeCacheMock = true): array
    {
        if (!$includeCacheMock) {
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

        return [
           (object) ['user_id' => 1],
           (object) ['user_id' => 2],
           false,
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
           (object) ['user_id' => 1],
           (object) ['user_id' => 2],
           false,
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
