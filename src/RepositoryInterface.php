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
     * Get first row corresponding of the primary keys.
     *
     * @param  string[] $primaryKeys
     * @return EntityInterface
     * @throws \UnexpectedValueException
     */
    public function findByKeys(array $primaryKeys);

    /**
     * Persist data in database.
     *
     * @param  EntityInterface $entity
     * @return bool
     */
    public function persist(EntityInterface $entity);

    /**
     * Delete an entity in database.
     *
     * @param  EntityInterface $entity
     * @return bool
     */
    public function delete(EntityInterface $entity);

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
