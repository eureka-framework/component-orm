<?php declare(strict_types=1);

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eureka\Component\Orm\Traits;

use Eureka\Component\Database\Connection;
use Eureka\Component\Orm\EntityInterface;
use Eureka\Component\Orm\Exception;
use Eureka\Component\Orm\Query;
use Eureka\Component\Orm\RepositoryInterface;

/**
 * Repository trait.
 *
 * @author Romain Cottard
 */
trait RepositoryTrait
{
    /**
     * @param  int $id
     * @return object
     * @throws Exception\EntityNotExistsException
     * @throws Exception\OrmException
     */
    public function findById(int $id): object
    {
        if (count($this->getPrimaryKeys()) > 1) {
            throw new \LogicException(
                __METHOD__ . '|Cannot use findById() method for table with multiple primary keys !'
            );
        }

        $primaryKeys = $this->getPrimaryKeys();
        $field       = reset($primaryKeys);

        return $this->findByKeys([$field => $id]);
    }

    /**
     * @param  array $keys
     * @return EntityInterface
     * @throws Exception\EntityNotExistsException
     * @throws Exception\InvalidQueryException
     * @throws Exception\OrmException
     */
    public function findByKeys(array $keys): object
    {
        /** @var \Eureka\Component\Orm\RepositoryInterface $this */

        $queryBuilder = Query\Factory::getBuilder(Query\Factory::TYPE_SELECT, $this);
        foreach ($keys as $field => $value) {
            $queryBuilder->addWhere($field, $value);
        }

        return $this->selectOne($queryBuilder);
    }

    /**
     * @param  array $keys
     * @return EntityInterface[]
     * @throws Exception\InvalidQueryException
     * @throws Exception\OrmException
     */
    public function findAllByKeys(array $keys): array
    {
        /** @var \Eureka\Component\Orm\RepositoryInterface $this */
        $queryBuilder = Query\Factory::getBuilder(Query\Factory::TYPE_SELECT, $this);
        foreach ($keys as $field => $value) {
            $queryBuilder->addWhere($field, $value);
        }

        return $this->select($queryBuilder);
    }

    /**
     * @param \Eureka\Component\Orm\EntityInterface $entity
     * @return bool
     * @throws \Eureka\Component\Orm\Exception\OrmException
     */
    public function delete(EntityInterface $entity): bool
    {
        /** @var \Eureka\Component\Orm\RepositoryInterface $this */
        $queryBuilder = Query\Factory::getBuilder(Query\Factory::TYPE_DELETE, $this, $entity);

        /** @var Connection $connection */
        $connection = $this->getConnection();

        $statement = $connection->prepare($queryBuilder->getQuery());
        $result    = $statement->execute($queryBuilder->getBind());

        //~ Reset some data
        $entity->setExists(false);
        $entity->resetUpdated();

        //~ Clear
        $queryBuilder->clear();
        $this->deleteCacheEntity($entity);

        return (bool) $result;
    }

    /**
     * @param  \Eureka\Component\Orm\EntityInterface $entity
     * @param  bool $onDuplicateUpdate
     * @param  bool $onDuplicateIgnore
     * @return bool
     * @throws \Eureka\Component\Orm\Exception\InsertFailedException
     * @throws \Eureka\Component\Orm\Exception\OrmException
     */
    public function insert(
        EntityInterface $entity,
        bool $onDuplicateUpdate = false,
        bool $onDuplicateIgnore = false
    ): bool {
        if ($entity->exists() && !$entity->isUpdated()) {
            return false;
        }

        /** @var RepositoryInterface $this */
        $queryBuilder = Query\Factory::getBuilder(Query\Factory::TYPE_INSERT, $this, $entity);

        /** @var Connection $connection */
        $connection = $this->getConnection();

        /** @var Query\InsertBuilder $queryBuilder */
        $statement = $connection->prepare($queryBuilder->getQuery($onDuplicateUpdate, $onDuplicateIgnore));
        $statement->execute($queryBuilder->getBind());

        if ($onDuplicateIgnore && $statement->rowCount() === 0) {
            throw new Exception\InsertFailedException(
                __METHOD__ . 'INSERT IGNORE could not insert (duplicate key or error)'
            );
        }

        //~ If has auto increment key (commonly, is a primary key), set value
        $lastInsertId = (int) $connection->lastInsertId();
        if ($lastInsertId > 0) {
            $this->lastId = $lastInsertId;

            $entity->setAutoIncrementId($this->getLastId());
        }

        //~ Reset some data
        $entity->setExists(true);
        $entity->resetUpdated();

        //~ Clear
        $queryBuilder->clear();
        $this->deleteCacheEntity($entity);

        return true;
    }

    /**
     * @param \Eureka\Component\Orm\EntityInterface $entity
     * @return bool
     * @throws \Eureka\Component\Orm\Exception\OrmException
     */
    public function update(EntityInterface $entity): bool
    {
        if (!$entity->isUpdated()) {
            return false;
        }

        /** @var \Eureka\Component\Orm\RepositoryInterface $this */
        $queryBuilder = Query\Factory::getBuilder(Query\Factory::TYPE_UPDATE, $this, $entity);

        /** @var Connection $connection */
        $connection = $this->getConnection();

        $statement = $connection->prepare($queryBuilder->getQuery());
        $result    = $statement->execute($queryBuilder->getBind());

        //~ Reset some data
        $entity->resetUpdated();

        //~ Clear
        $queryBuilder->clear();
        $this->deleteCacheEntity($entity);

        return $result;
    }

    /**
     * @param \Eureka\Component\Orm\EntityInterface $entity
     * @param bool $onDuplicateUpdate
     * @param bool $onDuplicateIgnore
     * @return bool
     * @throws \Eureka\Component\Orm\Exception\InsertFailedException
     * @throws \Eureka\Component\Orm\Exception\OrmException
     */
    public function persist(EntityInterface $entity, bool $onDuplicateUpdate = false, bool $onDuplicateIgnore = false): bool
    {
        if ($entity->exists()) {
            return $this->update($entity);
        } else {
            return $this->insert($entity, $onDuplicateUpdate, $onDuplicateIgnore);
        }
    }
}
