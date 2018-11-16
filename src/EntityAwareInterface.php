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
 * Entity aware interface.
 *
 * @author Romain Cottard
 */
interface EntityAwareInterface
{
    /**
     * @return $this
     */
    public function enableIgnoreNotMappedFields(): RepositoryInterface;

    /**
     * @return self
     */
    public function disableIgnoreNotMappedFields(): RepositoryInterface;

    /**
     * @param  \stdClass|null $row
     * @param  bool $exists
     * @return \Eureka\Component\Orm\EntityInterface
     */
    public function newEntity(\stdClass $row = null, bool $exists = false): EntityInterface;

    /**
     * @param  \Eureka\Component\Orm\EntityInterface $entity
     * @param  string $field
     * @return bool
     */
    public function isEntityUpdated(EntityInterface $entity, string $field): bool;

    /**
     * @param  \Eureka\Component\Orm\EntityInterface $entity
     * @param  string $field
     * @return mixed
     */
    public function getEntityValue(EntityInterface $entity, string $field);
}
