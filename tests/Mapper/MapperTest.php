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
use Eureka\Component\Orm\AbstractMapper;
use Eureka\Component\Orm\Exception\EntityNotExistsException;
use Eureka\Component\Orm\Exception\OrmException;
use Eureka\Component\Orm\Exception\UndefinedMapperException;
use Eureka\Component\Orm\MapperInterface;
use Eureka\Component\Orm\Query\QueryBuilder;
use Eureka\Component\Orm\Query\SelectBuilder;
use Eureka\Component\Orm\Tests\Generated\Entity\User;
use Eureka\Component\Orm\Tests\Generated\Infrastructure\Mapper\UserMapper;
use Eureka\Component\Orm\Tests\Generated\Infrastructure\Mapper\UserParentMapper;
use Eureka\Component\Orm\Tests\Generated\Repository\UserParentRepositoryInterface;
use Eureka\Component\Orm\Tests\Generated\Repository\UserRepositoryInterface;
use Eureka\Component\Validation\Entity\ValidatorEntityFactory;
use Eureka\Component\Validation\ValidatorFactory;
use PHPUnit\Framework\MockObject\Stub\Exception;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

use function PHPUnit\Framework\exactly;

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
    public function testICanInstantiateUserMapper()
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
    public function testICanInsertEntity()
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
    public function testICanInsertUpdateEntity()
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
    public function testICanDeleteEntity()
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
    public function testICanFindEntityById()
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
    public function testICanFindEntityByIdWithoutCacheEnabledNorCache()
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
    public function testIHaveAnExceptionWhenITryToFindNotExistingEntity()
    {
        $repository = $this->getUserRepository($this->getMockEntityNone());

        /** @var User $user */
        $this->expectException(EntityNotExistsException::class);
        $repository->findById(1);
    }

    /**
     * @return void
     * @throws OrmException
     */
    public function testICanFindEntitiesByKeys()
    {
        $repository = $this->getUserRepositoryNoCache($this->getMockEntityFindAll());

        /** @var User $user */
        $users = $repository->findAllByKeys(['user_id' => 1]);

        $this->assertCount(2, $users);
    }

    /**
     * @return void
     * @throws OrmException
     */
    public function testICanGetListOfEntityIndexedByGivenFieldWhenExecuteQuery()
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
    public function testIHaveAnExceptionWhenITryToExecuteQueryAndIndexResultByNonExistingField()
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
    public function testICanCheckIfRowExists()
    {
        $repository = $this->getUserRepository($this->getMockEntityFindId1());

        $this->assertTrue($repository->rowExists((new SelectBuilder($repository))->addWhere('user_id', 1)));
    }

    /**
     * @return void
     */
    public function testICanCountRows()
    {
        $repository = $this->getUserRepository($this->getMockEntityFindAll());

        $this->assertSame(2, $repository->count(new QueryBuilder($repository)));
    }

    /**
     * @return void
     * @throws OrmException
     */
    public function testICanCheckIfRowDoesNotExists()
    {
        $repository = $this->getUserRepository($this->getMockEntityNone());

        $this->assertFalse($repository->rowExists((new SelectBuilder($repository))->addWhere('user_id', 1)));
    }

    /**
     * @return void
     */
    public function testICanGetMaxPrimaryKeyIdForARepository()
    {
        $repository = $this->getUserRepository($this->getMockEntityFindId1());

        $this->assertSame(1, $repository->getMaxId());
    }

    /**
     * @return void
     */
    public function testAReconnectionIsMadeAutomaticallyWhenConnectionIsLostAndITryToExecuteAQuery()
    {
        $repository = $this->getUserRepository($this->getMockEntityFindId1(), true, 2006);

        $this->assertSame(1, $repository->getMaxId());
    }

    /**
     * @return void
     * @throws OrmException
     */
    public function testAReconnectionIsMadeAutomaticallyWhenConnectionIsLostAndITryToExecuteAQueryWithResult()
    {
        $entities = $this->getMockEntityFindId1();
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
    public function testIHaveAnExceptionWhenITryToGetMaxPrimaryKeyIdOnRepositoryWithMultiPrimaryKeys()
    {
        $repository = $this->getUserParentRepository($this->getMockEntityNone());

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Cannot use getMaxId() method for table with multiple primary keys !');
        $repository->getMaxId();
    }

    /**
     * @return void
     */
    public function testIHaveAnExceptionWhenITryToGetNamesMapForNonExistingField()
    {
        $repository = $this->getUserRepository($this->getMockEntityNone());

        $this->expectException(\OutOfRangeException::class);
        $this->expectExceptionMessage('Specified field does not exist in data names map');

        $repository->getNamesMap('unknown_field');
    }

    /**
     * @return void
     */
    public function testIHaveAnExceptionWhenITryToGetNonExistingMapper()
    {
        $repository = $this->getUserRepository($this->getMockEntityNone());

        $this->expectException(UndefinedMapperException::class);
        $this->expectExceptionMessage('Mapper does not exist! (mapper: \Unknown\Mapper\ClassName)');

        $repository->getMapper('\Unknown\Mapper\ClassName');
    }

    /**
     * @return void
     * @throws EntityNotExistsException
     * @throws OrmException
     */
    public function testIHaveAnExceptionWhenITryToGetQueryWithAnError()
    {
        $repository = $this->getUserRepository($this->getMockEntityFindId1(), false, 1);

        $this->expectException(\PDOException::class);

        $repository->findById(1);
    }

    /**
     * @return void
     * @throws OrmException
     */
    public function testIHaveAnExceptionWhenITryToGetQueryWithResultWithAnError()
    {
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
     * @param array $entityMock
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
        $connection->method('lastInsertId')->willReturn(1);

        $mockBuilder = $this->getMockBuilder(ConnectionFactory::class)->disableOriginalConstructor();
        $connectionFactory = $mockBuilder->getMock();
        $connectionFactory->method('getConnection')->willReturn($connection);

        return $connectionFactory;
    }

    /**
     * @param array $entityMock
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
     * @param array $entityMock
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
     * @param array $entityMock
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
     * @return array
     */
    private function getMockEntityFindId1(bool $includeCacheMock = true): array
    {
        $mock = [];

        if ($includeCacheMock) {
            $mock = [
                (object) [
                    'user_id' => 1,
                ],
                false,
            ];
        }

        $mock = array_merge(
            $mock,
            [
                (object) [
                    'user_id'          => 1,
                    'user_is_enabled'  => true,
                    'user_email'       => 'user@example.com',
                    'user_password'    => md5('password'),
                    'user_date_create' => '2020-01-01 10:00:00',
                    'user_date_update' => null,
                ],
                false,
            ]
        );

        return array_merge($mock, $mock); // double for cache test
    }

    /**
     * @param bool $includeCacheMock
     * @return array
     */
    private function getMockEntityFindAll(bool $includeCacheMock = true): array
    {
        $mock = [];

        if ($includeCacheMock) {
            $mock = [
                (object) [
                    'user_id' => 1,
                ],
                (object) [
                    'user_id' => 2,
                ],
                false,
            ];
        }

        $mock = array_merge(
            $mock,
            [
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
            ]
        );

        return array_merge($mock, $mock); // double for cache test
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
