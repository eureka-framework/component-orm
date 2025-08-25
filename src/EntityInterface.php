<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Eureka\Component\Orm;

use Eureka\Component\Validation\Entity\GenericEntity;

/**
 * @template TRepository of RepositoryInterface
 * @template TEntity of EntityInterface
 */
interface EntityInterface
{
    /**
     * Set auto increment value.
     * Must be overridden to use internal property setter method, according to the data class definition.
     *
     * @param  int $id
     * @return static
     */
    public function setAutoIncrementId(int $id): static;

    /**
     * Return cache key for the current data instance.
     *
     * @return string
     */
    public function getCacheKey(): string;

    /**
     * @return RepositoryInterface
     */
    public function getRepository(): RepositoryInterface;

    /**
     * Get form entity container.
     *
     * @return GenericEntity
     */
    public function getGenericEntity(): GenericEntity;

    /**
     * Hydrate entity with form entity values
     *
     * @param  GenericEntity $genericEntity
     * @return static
     */
    public function hydrateFromGenericEntity(GenericEntity $genericEntity): static;

    /**
     * If the data set exists.
     *
     * @return bool
     */
    public function exists(): bool;

    /**
     * If at least one data has been updated.
     * If property name is specified, check only property.
     *
     * @param  bool $exists
     * @return static
     */
    public function setExists(bool $exists): static;

    /**
     * If at least one data has been updated.
     * If property name is specified, check only property.
     *
     * @param  string|null $property
     * @return bool
     */
    public function isUpdated(string|null $property = null): bool;

    /**
     * Reset updated list of properties
     *
     * @return static
     */
    public function resetUpdated(): static;
}
