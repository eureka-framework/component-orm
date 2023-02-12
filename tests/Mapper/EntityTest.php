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
use Eureka\Component\Orm\Exception\OrmException;
use Eureka\Component\Orm\Tests\Generated\Entity\User;
use Eureka\Component\Orm\Tests\Generated\Entity\UserParent;
use Eureka\Component\Orm\Tests\Generated\Infrastructure\Mapper\UserMapper;
use Eureka\Component\Orm\Tests\Generated\Infrastructure\Mapper\UserParentMapper;
use Eureka\Component\Orm\Tests\Generated\Repository\UserParentRepositoryInterface;
use Eureka\Component\Orm\Tests\Generated\Repository\UserRepositoryInterface;
use Eureka\Component\Validation\Entity\ValidatorEntityFactory;
use Eureka\Component\Validation\ValidatorFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Class TypeTest
 *
 * @author Romain Cottard
 */
class EntityTest extends TestCase
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
    public function testICanCreateNewEmptyUserEntityFromEmptyContent(): void
    {
        $repository = $this->getUserRepository();
        $user       = $repository->newEntity();

        $this->assertInstanceOf(User::class, $user);
    }

    /**
     * @return void
     */
    public function testICanCreateNewUserEntityFromNonEmptyContent(): void
    {
        $repository = $this->getUserRepository();
        /** @var User $user */
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

        $this->assertInstanceOf(User::class, $user);
        $this->assertInstanceOf(UserRepositoryInterface::class, $user->getRepository());
    }

    /**
     * @return void
     */
    public function testICanUpdateAnEntity(): void
    {
        $repository = $this->getUserRepository();
        /** @var User $user */
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

        $user->setExists(true);
        $user->setAutoIncrementId(2);
        $user->setDateUpdate('2020-01-02 10:00:00');

        $this->assertTrue($user->isUpdated());
        $this->assertTrue($user->isUpdated('id'));
        $this->assertTrue($user->isUpdated('dateUpdate'));

        $this->assertTrue($repository->isEntityUpdated($user, 'user_id'));
        $this->assertTrue($repository->isEntityUpdated($user, 'user_date_update'));

        $user->resetUpdated();
        $this->assertFalse($user->isUpdated());
        $this->assertFalse($user->isUpdated('id'));
        $this->assertFalse($user->isUpdated('dateUpdate'));
    }

    /**
     * @return void
     */
    public function testICanCreateNewEntityFromArray(): void
    {
        $repository = $this->getUserRepository();
        $user       = $repository->newEntityFromArray(
            [
                'id'         => 1,
                'isEnabled'  => true,
                'email'      => 'user@example.com',
                'password'   => md5('password'),
                'dateCreate' => '2020-01-01 10:00:00',
            ]
        );

        $expected = $repository->newEntity(
            (object) [
                'user_id'          => 1,
                'user_is_enabled'  => true,
                'user_email'       => 'user@example.com',
                'user_password'    => md5('password'),
                'user_date_create' => '2020-01-01 10:00:00',
                'user_date_update' => null,
            ]
        );

        $a = $expected->getId();

        $this->assertEquals($expected, $user);
    }


    /**
     * @return void
     */
    public function testICanCreateNewEntityWithUnknownFieldWhenIEnableIgnoreNotMappedField(): void
    {
        $repository = $this->getUserRepository();
        $repository->enableIgnoreNotMappedFields();
        $user = $repository->newEntity(
            (object) [
                'user_id'          => 1,
                'user_is_enabled'  => true,
                'user_email'       => 'user@example.com',
                'user_password'    => md5('password'),
                'user_date_create' => '2020-01-01 10:00:00',
                'user_date_update' => null,
                'hey'              => true,
            ]
        );
        $repository->disableIgnoreNotMappedFields();
        $this->assertInstanceOf(User::class, $user);
    }

    /**
     * @return void
     */
    public function testIHaveExceptionWhenIgnoreNotMappedFieldIsDisabled(): void
    {
        $repository = $this->getUserRepository();
        $repository->disableIgnoreNotMappedFields();

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Field have not mapping with entity instance (field: hey)');
        $repository->newEntity(
            (object) [
                'user_id'          => 1,
                'user_is_enabled'  => true,
                'user_email'       => 'user@example.com',
                'user_password'    => md5('password'),
                'user_date_create' => '2020-01-01 10:00:00',
                'user_date_update' => null,
                'hey'              => true,
            ]
        );
    }

    /**
     * @return void
     */
    public function testIHaveExceptionWhenITryToGetValueOfEntityOnNonExistingField(): void
    {
        $repository = $this->getUserRepository();

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

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Cannot get field value: field have no mapping with entity instance (field: user_unknown)');
        $repository->getEntityValue($user, 'user_unknown');
    }

    /**
     * @return void
     */
    public function testICanCreateNewGenericEntityFromUserEntity(): void
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

        $expected = $repository->newGenericEntity(
            [
                'id'          => 1,
                'is_enabled'  => true,
                'email'       => 'user@example.com',
                'password'    => md5('password'),
                'date_create' => '2020-01-01 10:00:00',
                'date_update' => null,
            ]
        );

        $generic = $user->getGenericEntity();

        $this->assertEquals($expected, $generic);
    }

    /**
     * @return void
     */
    public function testICanCreateNewEntityFromGenericEntity(): void
    {
        $repository = $this->getUserRepository();

        $generic = $repository->newGenericEntity(
            [
                'id'          => 1,
                'is_enabled'  => true,
                'email'       => 'user@example.com',
                'password'    => md5('password'),
                'date_create' => '2020-01-01 10:00:00',
                'date_update' => null,
            ]
        );

        $user = $repository->newEntityFromGeneric($generic);

        $expected = $repository->newEntity(
            (object) [
                'user_id'          => 1,
                'user_is_enabled'  => true,
                'user_email'       => 'user@example.com',
                'user_password'    => md5('password'),
                'user_date_create' => '2020-01-01 10:00:00',
                'user_date_update' => null,
            ]
        );

        $this->assertEquals($expected, $user);
    }

    /**
     * @return void
     * @throws OrmException
     */
    public function testICanResetLazyLoadedData(): void
    {
        $repository = $this->getUserRepository();
        $user       = $repository->newEntity();

        $user->setUserParent($this->getUserParentRepository()->newEntity());

        $this->assertInstanceOf(UserParent::class, $user->getUserParent());

        $user->resetLazyLoadedData();
    }

    /**
     * @return ConnectionFactory
     */
    private function getConnectionFactoryMock(): ConnectionFactory
    {
        $mockBuilder = $this->getMockBuilder(Connection::class)->disableOriginalConstructor();
        $connection  = $mockBuilder->getMock();

        $mockBuilder = $this->getMockBuilder(ConnectionFactory::class)->disableOriginalConstructor();
        /** @var ConnectionFactory&MockObject $connectionFactory */
        $connectionFactory = $mockBuilder->getMock();
        $connectionFactory->method('getConnection')->willReturn($connection);

        return $connectionFactory;
    }

    private function getUserRepository(): UserRepositoryInterface
    {
        $connectionFactory = $this->getConnectionFactoryMock();
        return new UserMapper(
            'common',
            $connectionFactory,
            new ValidatorFactory(),
            new ValidatorEntityFactory(new ValidatorFactory()),
            [],
            null,
            true,
        );
    }

    private function getUserParentRepository(): UserParentRepositoryInterface
    {
        $connectionFactory = $this->getConnectionFactoryMock();
        return new UserParentMapper(
            'common',
            $connectionFactory,
            new ValidatorFactory(),
            new ValidatorEntityFactory(new ValidatorFactory()),
            [],
            null,
            true,
        );
    }
}
