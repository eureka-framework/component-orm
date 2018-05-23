<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
     */
    public function findById($id);

    /**
     * Get rows corresponding of the keys.
     *
     * @param  string[] $keys
     * @return EntityInterface[] List of row
     * @throws \Psr\Cache\InvalidArgumentException
     * @throws \Exception
     */
    public function findAllByKeys(array $keys);

    /**
     * Get first row corresponding of the primary keys.
     *
     * @param  string[] $primaryKeys
     * @return EntityInterface
     * @throws \UnexpectedValueException
     */
    public function findByKeys(array $primaryKeys);

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
    public function persist(EntityInterface $entity, $onDuplicateUpdate = false, $onDuplicateIgnore = false);

    /**
     * Delete data from database.
     *
     * @param  EntityInterface $entity
     * @return bool
     * @throws \LogicException
     * @throws \Psr\Cache\InvalidArgumentException
     * @throws \Exception
     */
    public function delete(EntityInterface $entity);

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
    public function insert(EntityInterface $entity, $onDuplicateUpdate = false, $onDuplicateIgnore = false);

    /**
     * Update data into database
     *
     * @param  EntityInterface $entity
     * @return bool
     * @throws \LogicException
     * @throws \Psr\Cache\InvalidArgumentException
     * @throws \Exception
     */
    public function update(EntityInterface $entity);

    /**
     * Create new instance of extended EntityInterface class & return it.
     *
     * @param  \stdClass $row
     * @param  bool $exists
     * @return object
     * @throws \LogicException
     */
    public function newEntity(\stdClass $row = null, $exists = false);

}
