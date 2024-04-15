<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Eureka\Component\Orm;

use Eureka\Component\Orm\Exception\EntityNotExistsException;
use Eureka\Component\Orm\Exception\InsertFailedException;
use Eureka\Component\Orm\Exception\OrmException;

/**
 * Interface RepositoryInterface
 *
 * @author Romain Cottard
 *
 * @template TEntity of EntityInterface
 * @extends EntityAwareInterface<TEntity>
 * @extends MapperInterface<TEntity>
 */
interface RepositoryInterface extends
    CacheAwareInterface,
    ConnectionAwareInterface,
    EntityAwareInterface,
    MapperInterface,
    TableInterface
{
    /**
     * Get first row corresponding of the primary keys.
     *
     * @param  int $id
     * @return TEntity
     * @throws OrmException
     * @throws EntityNotExistsException
     * @throws \LogicException
     */
    public function findById(int $id): object;

    /**
     * Get rows corresponding of the keys.
     *
     * @param  array<string, string|int|float|bool|null> $keys
     * @return array<TEntity> List of row
     * @throws OrmException
     */
    public function findAllByKeys(array $keys): array;

    /**
     * Get first row corresponding of the primary keys.
     *
     * @param  array<string, string|int|float|bool|null> $primaryKeys
     * @return TEntity
     * @throws \UnexpectedValueException
     * @throws EntityNotExistsException
     * @throws OrmException
     */
    public function findByKeys(array $primaryKeys): object;

    /**
     * Either insert or update an entity
     *
     * @param  TEntity $entity
     * @param  bool $onDuplicateUpdate If true, add on duplicate update clause to the insert query.
     * @param  bool $onDuplicateIgnore If true, add IGNORE on insert query to avoid SQL errors if duplicate
     * @return bool
     * @throws OrmException
     */
    public function persist(
        EntityInterface $entity,
        bool $onDuplicateUpdate = false,
        bool $onDuplicateIgnore = false,
    ): bool;

    /**
     * Delete data from database.
     *
     * @param  TEntity $entity
     * @return bool
     * @throws OrmException
     * @throws \LogicException
     */
    public function delete(EntityInterface $entity): bool;

    /**
     * Insert active row (or update row if it possible).
     *
     * @param  TEntity $entity
     * @param  bool $onDuplicateUpdate If true, add on duplicate update clause to the insert query.
     * @param  bool $onDuplicateIgnore If true, add IGNORE on insert query to avoid SQL errors if duplicate
     * @return bool State of insert
     * @throws InsertFailedException
     * @throws OrmException
     * @throws \LogicException
     */
    public function insert(
        EntityInterface $entity,
        bool $onDuplicateUpdate = false,
        bool $onDuplicateIgnore = false,
    ): bool;

    /**
     * Update data into database
     *
     * @param  TEntity $entity
     * @return bool
     * @throws OrmException
     * @throws \LogicException
     */
    public function update(EntityInterface $entity): bool;
}
