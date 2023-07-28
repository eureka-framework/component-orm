<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Eureka\Component\Orm\Traits;

use Eureka\Component\Orm\EntityInterface;
use Eureka\Component\Orm\Exception;
use Eureka\Component\Orm\Query;
use Eureka\Component\Orm\RepositoryInterface;

/**
 * Repository trait.
 *
 * @author Romain Cottard
 *
 * @template TRepository of RepositoryInterface
 * @template TEntity of EntityInterface
 */
trait RepositoryTrait
{
    use ConnectionAwareTrait;
    use TableTrait;

    /**
     * @param  int $id
     * @return TEntity
     * @throws Exception\EntityNotExistsException
     * @throws Exception\OrmException
     */
    public function findById(int $id): EntityInterface
    {
        if (count($this->getPrimaryKeys()) > 1) {
            // @codeCoverageIgnoreStart
            throw new \LogicException(
                __METHOD__ . '|Cannot use findById() method for table with multiple primary keys !'
            );
            // @codeCoverageIgnoreEnd
        }

        $primaryKeys = $this->getPrimaryKeys();
        $field       = (string) reset($primaryKeys);

        return $this->findByKeys([$field => $id]);
    }

    /**
     * @param  array<string, string|int|float|bool|null> $keys
     * @return TEntity
     * @throws Exception\EntityNotExistsException
     * @throws Exception\InvalidQueryException
     * @throws Exception\OrmException
     */
    public function findByKeys(array $keys): EntityInterface
    {
        /** @var TRepository $this */
        $queryBuilder = new Query\SelectBuilder($this);
        foreach ($keys as $field => $value) {
            $queryBuilder->addWhere($field, $value);
        }

        return $this->selectOne($queryBuilder);
    }

    /**
     * @param  array<string, string|int|float|bool|null> $keys
     * @return array<TEntity>
     * @throws Exception\OrmException
     */
    public function findAllByKeys(array $keys): array
    {
        /** @var TRepository $this */
        $queryBuilder = new Query\SelectBuilder($this);
        foreach ($keys as $field => $value) {
            $queryBuilder->addWhere($field, $value);
        }

        return $this->select($queryBuilder);
    }

    /**
     * @param TEntity $entity
     * @return bool
     * @throws Exception\OrmException
     */
    public function delete(EntityInterface $entity): bool
    {
        $queryBuilder = new Query\DeleteBuilder($this, $entity);

        $result = $this->executeWithResult($queryBuilder->getQuery(), $queryBuilder->getAllBind());

        //~ Reset some data
        $entity->setExists(false);
        $entity->resetUpdated();

        //~ Clear
        $queryBuilder->clear();
        $this->deleteCacheEntity($entity->getCacheKey());

        return $result;
    }

    /**
     * @param  TEntity $entity
     * @param  bool $onDuplicateUpdate
     * @param  bool $onDuplicateIgnore
     * @return bool
     * @throws Exception\InsertFailedException
     * @throws Exception\OrmException
     */
    public function insert(
        EntityInterface $entity,
        bool $onDuplicateUpdate = false,
        bool $onDuplicateIgnore = false
    ): bool {
        if ($entity->exists() && !$entity->isUpdated()) {
            return false;
        }

        $queryBuilder = new Query\InsertBuilder($this, $entity);
        $connection   = $this->getConnection();

        $statement = $this->execute(
            $queryBuilder->getQuery($onDuplicateUpdate, $onDuplicateIgnore),
            $queryBuilder->getAllBind()
        );

        if ($onDuplicateIgnore && $statement->rowCount() === 0) {
            // @codeCoverageIgnoreStart
            throw new Exception\InsertFailedException(
                __METHOD__ . 'INSERT IGNORE could not insert (duplicate key or error)'
            );
            // @codeCoverageIgnoreEnd
        }

        //~ If we have auto increment key (commonly, is a primary key), set value
        $lastInsertId = (int) $connection->lastInsertId();
        if ($lastInsertId > 0) {
            $this->setLastId($lastInsertId);

            $entity->setAutoIncrementId($this->getLastId());
        }

        //~ Reset some data
        $entity->setExists(true);
        $entity->resetUpdated();

        //~ Clear
        $queryBuilder->clear();
        $this->deleteCacheEntity($entity->getCacheKey());

        return true;
    }

    /**
     * @param TEntity $entity
     * @return bool
     * @throws Exception\OrmException
     */
    public function update(EntityInterface $entity): bool
    {
        if (!$entity->isUpdated()) {
            return false;
        }

        $queryBuilder = new Query\UpdateBuilder($this, $entity);

        $result = $this->executeWithResult($queryBuilder->getQuery(), $queryBuilder->getAllBind());

        //~ Reset some data
        $entity->resetUpdated();

        //~ Clear
        $queryBuilder->clear();
        $this->deleteCacheEntity($entity->getCacheKey());

        return $result;
    }

    /**
     * @param TEntity $entity
     * @param bool $onDuplicateUpdate
     * @param bool $onDuplicateIgnore
     * @return bool
     * @throws Exception\InsertFailedException
     * @throws Exception\OrmException
     */
    public function persist(
        EntityInterface $entity,
        bool $onDuplicateUpdate = false,
        bool $onDuplicateIgnore = false
    ): bool {
        if ($entity->exists()) {
            return $this->update($entity);
        } else {
            return $this->insert($entity, $onDuplicateUpdate, $onDuplicateIgnore);
        }
    }
}
