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
use Eureka\Component\Orm\Exception\OrmException;
use Eureka\Component\Orm\Exception\UndefinedMapperException;
use Eureka\Component\Orm\Query\SelectBuilder;
use Eureka\Component\Orm\Tests\Generated\Entity\Address;
use Eureka\Component\Orm\Tests\Generated\Entity\Comment;
use Eureka\Component\Orm\Tests\Generated\Entity\User;
use Eureka\Component\Orm\Tests\Generated\Infrastructure\Mapper\AddressMapper;
use Eureka\Component\Orm\Tests\Generated\Infrastructure\Mapper\CommentMapper;
use Eureka\Component\Orm\Tests\Generated\Infrastructure\Mapper\UserMapper;
use Eureka\Component\Orm\Tests\Generated\Repository\AddressRepositoryInterface;
use Eureka\Component\Orm\Tests\Generated\Repository\CommentRepositoryInterface;
use Eureka\Component\Orm\Tests\Generated\Repository\UserRepositoryInterface;
use Eureka\Component\Validation\Entity\ValidatorEntityFactory;
use Eureka\Component\Validation\ValidatorFactory;
use PHPUnit\Framework\TestCase;

/**
 * Class MapperJoinTest
 *
 * @author Romain Cottard
 */
class MapperJoinTest extends TestCase
{
    /**
     * @return void
     * @throws OrmException
     * @throws UndefinedMapperException
     */
    public function testICanRetrieveUserWithJoinedData()
    {
        $userRepository    = $this->getUserRepository($this->getMockEntityFindOne());
        $addressRepository = $this->getAddressRepository($this->getMockEntityAddressFindOne());

        /** @var User $user */
        $users = $userRepository->selectJoin(new SelectBuilder($userRepository), ['UserAddress', 'Unknown']);
        /** @var User $expectedUser */
        $expectedUser = $userRepository->newEntity(
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

        /** @var Address $expectedAddress */
        $expectedAddress = $addressRepository->newEntity(
            (object) [
                'address_id'       => 1,
                'user_id'          => 1,
                'address_number'   => '13',
                'address_text'     => 'Backer Street',
            ],
            true
        );
        $expectedUser->setAllUserAddress(
            [
                $expectedAddress
            ]
        );

        $this->assertEquals([$expectedUser], $users);
    }

    /**
     * @return void
     * @throws OrmException
     * @throws UndefinedMapperException
     */
    public function testICanRetrieveUserWithoutJoinedLeftData()
    {
        $userRepository    = $this->getUserRepository($this->getMockEntityFindOne(false));

        /** @var User $user */
        $users = $userRepository->selectJoin(new SelectBuilder($userRepository), ['UserComment']);
        /** @var User $expectedUser */
        $expectedUser = $userRepository->newEntity(
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

        $this->assertEquals([$expectedUser], $users);
    }

    /**
     * @return void
     * @throws OrmException
     * @throws UndefinedMapperException
     */
    public function testICanRetrieveUserWithJoinedLeftData()
    {
        $userRepository = $this->getUserRepository($this->getMockEntityFindOne(true));

        /** @var User $user */
        $user = $userRepository->selectJoin(new SelectBuilder($userRepository), ['UserComment']);
        /** @var User $expectedUser */
        $expectedUser = $userRepository->newEntity(
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

        /** @var Comment $expectedComment */
        $commentRepository = $this->getCommentRepository();
        $expectedComment   = $commentRepository->newEntity(
            (object) [
                'comment_id'       => 1,
                'user_id'          => 1,
                'comment_text'     => 'This is my comment',
            ],
            true
        );
        $expectedUser->setUserComment($expectedComment);

        $this->assertEquals([$expectedUser], $user);
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
            [
                AddressMapper::class => $this->getAddressRepository($this->getMockEntityAddressFindAll()),
                CommentMapper::class => $this->getCommentRepository($this->getMockEntityNone()),
            ],
            null,
            false,
        );
    }

    /**
     * @param array $entityMock
     * @return AddressRepositoryInterface
     */
    private function getAddressRepository(array $entityMock = []): AddressRepositoryInterface
    {
        $connectionFactory = $this->getConnectionFactoryMock($entityMock);
        return new AddressMapper(
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
     * @param array $entityMock
     * @return CommentRepositoryInterface
     */
    private function getCommentRepository(array $entityMock = []): CommentRepositoryInterface
    {
        $connectionFactory = $this->getConnectionFactoryMock($entityMock);
        return new CommentMapper(
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
     * @param bool $withMockComment
     * @return array
     */
    private function getMockEntityFindOne(bool $withMockComment = false): array
    {
        $commentId   = null;
        $commentText = null;

        if ($withMockComment) {
            $commentId   = 1;
            $commentText = 'This is my comment';
        }

        return [
            (object) [
                'user_id'                => 1,
                'user_is_enabled'        => true,
                'user_email'             => 'user@example.com',
                'user_password'          => md5('password'),
                'user_date_create'       => '2020-01-01 10:00:00',
                'user_date_update'       => null,
                'address_id'             => 1,
                'address_number'         => '13',
                'address_text'           => 'Backer Street',
                'comment_id_comment_0'   => $commentId,
                'comment_text_comment_0' => $commentText,
            ],
            false,
        ];
    }

    /**
     * @return array
     */
    private function getMockEntityAddressFindOne(): array
    {
        return [
            (object) [
                'address_id'       => 1,
                'user_id'          => 1,
                'address_number'   => '13',
                'address_text'     => 'Backer Street',
            ],
            false,
        ];
    }

    /**
     * @return array
     */
    private function getMockEntityAddressFindAll(): array
    {
        return [
            (object) [
                'address_id'       => 1,
                'user_id'          => 1,
                'address_number'   => '13',
                'address_text'     => 'Backer Street',
            ],
            (object) [
                'address_id'       => 2,
                'user_id'          => 2,
                'address_number'   => '221B',
                'address_text'     => 'Rue de Montmartre',
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
        require_once __DIR__ . '/../Generated/Entity/Abstracts/AbstractComment.php';
        require_once __DIR__ . '/../Generated/Entity/User.php';
        require_once __DIR__ . '/../Generated/Entity/UserParent.php';
        require_once __DIR__ . '/../Generated/Entity/Address.php';
        require_once __DIR__ . '/../Generated/Entity/Comment.php';

        //~ Repository
        require_once __DIR__ . '/../Generated/Repository/UserRepositoryInterface.php';
        require_once __DIR__ . '/../Generated/Repository/UserParentRepositoryInterface.php';
        require_once __DIR__ . '/../Generated/Repository/AddressRepositoryInterface.php';
        require_once __DIR__ . '/../Generated/Repository/CommentRepositoryInterface.php';

        //~ Mapper
        require_once __DIR__ . '/../Generated/Infrastructure/Mapper/Abstracts/AbstractUserMapper.php';
        require_once __DIR__ . '/../Generated/Infrastructure/Mapper/Abstracts/AbstractUserParentMapper.php';
        require_once __DIR__ . '/../Generated/Infrastructure/Mapper/Abstracts/AbstractAddressMapper.php';
        require_once __DIR__ . '/../Generated/Infrastructure/Mapper/Abstracts/AbstractCommentMapper.php';
        require_once __DIR__ . '/../Generated/Infrastructure/Mapper/UserMapper.php';
        require_once __DIR__ . '/../Generated/Infrastructure/Mapper/UserParentMapper.php';
        require_once __DIR__ . '/../Generated/Infrastructure/Mapper/AddressMapper.php';
        require_once __DIR__ . '/../Generated/Infrastructure/Mapper/CommentMapper.php';
    }
}
