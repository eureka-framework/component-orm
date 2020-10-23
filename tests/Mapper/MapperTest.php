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
use Eureka\Component\Orm\MapperInterface;
use Eureka\Component\Orm\Query\SelectBuilder;
use Eureka\Component\Orm\Tests\Generated\Entity\User;
use Eureka\Component\Orm\Tests\Generated\Infrastructure\Mapper\UserMapper;
use Eureka\Component\Orm\Tests\Generated\Repository\UserRepositoryInterface;
use Eureka\Component\Validation\Entity\ValidatorEntityFactory;
use Eureka\Component\Validation\ValidatorFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

/**
 * Class TypeTest
 *
 * @author Romain Cottard
 */
class MapperTest extends TestCase
{
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
        $repository = $this->getUserRepositoryNoCache($this->getMockEntityFindAll(true));

        /** @var User $user */
        $users = $repository->findAllByKeys(['user_id' => 1]);

        $this->assertCount(2, $users);
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
     * @throws OrmException
     */
    public function testICanCheckIfRowDoesNotExists()
    {
        $repository = $this->getUserRepository($this->getMockEntityNone());

        $this->assertFalse($repository->rowExists((new SelectBuilder($repository))->addWhere('user_id', 1)));
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

        $statementMock = $this->getMockBuilder(\PDOStatement::class)->getMock();
        $statementMock->method('execute')->willReturn(true);
        $statementMock->method('rowCount')->willReturn(2);
        $statementMock->method('fetch')->willReturnOnConsecutiveCalls(...$entityMock);

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
            new ArrayAdapter(),
            true,
        );
    }

    /**
     * @param array $entityMock
     * @return UserRepositoryInterface
     */
    private function getUserRepositoryNoCache(array $entityMock = []): UserRepositoryInterface
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
}
