<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Eureka\Component\Orm;

/**
 * Interface RepositoryInterface
 *
 * @author Romain Cottard
 */
interface RepositoryInterface extends MapperInterface
{
    /**
     * Get first row corresponding of the primary keys.
     *
     * @param  int $id
     * @return EntityInterface
     * @throws \LogicException
     * @throws \Eureka\Component\Orm\Exception\OrmException
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function findById(int $id): EntityInterface;

    /**
     * Get rows corresponding of the keys.
     *
     * @param  string[] $keys
     * @return EntityInterface[] List of row
     * @throws \Psr\Cache\InvalidArgumentException
     * @throws \Eureka\Component\Orm\Exception\OrmException
     * @throws \Exception
     */
    public function findAllByKeys(array $keys): array;

    /**
     * Get first row corresponding of the primary keys.
     *
     * @param  string[] $primaryKeys
     * @return EntityInterface
     * @throws \UnexpectedValueException
     * @throws \Eureka\Component\Orm\Exception\OrmException
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function findByKeys(array $primaryKeys): EntityInterface;

    /**
     * Either insert or update an entity
     *
     * @param  EntityInterface $entity
     * @param  bool $onDuplicateUpdate If true, add on duplicate update clause to the insert query.
     * @param  bool $onDuplicateIgnore If true, add IGNORE on insert query to avoid SQL errors if duplicate
     * @return bool
     * @throws \Psr\Cache\InvalidArgumentException
     * @throws \Exception
     */
    public function persist(EntityInterface $entity, bool $onDuplicateUpdate = false, bool $onDuplicateIgnore = false): bool;

    /**
     * Delete data from database.
     *
     * @param  EntityInterface $entity
     * @return bool
     * @throws \LogicException
     * @throws \Psr\Cache\InvalidArgumentException
     * @throws \Exception
     */
    public function delete(EntityInterface $entity): bool;

    /**
     * Insert active row (or update row if it possible).
     *
     * @param  EntityInterface $entity
     * @param  bool $onDuplicateUpdate If true, add on duplicate update clause to the insert query.
     * @param  bool $onDuplicateIgnore If true, add IGNORE on insert query to avoid SQL errors if duplicate
     * @return bool State of insert
     * @throws \Eureka\Component\Orm\Exception\InsertFailedException
     * @throws \LogicException
     * @throws \Psr\Cache\InvalidArgumentException
     * @throws \Exception
     */
    public function insert(EntityInterface $entity, bool $onDuplicateUpdate = false, bool $onDuplicateIgnore = false): bool;

    /**
     * Update data into database
     *
     * @param  EntityInterface $entity
     * @return bool
     * @throws \LogicException
     * @throws \Psr\Cache\InvalidArgumentException
     * @throws \Exception
     */
    public function update(EntityInterface $entity): bool;
}
