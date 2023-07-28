<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=0);

namespace Eureka\Component\Orm;

use Eureka\Component\Validation\Entity\GenericEntity;

/**
 * @template TRepository of RepositoryInterface
 * @template TEntity of EntityInterface
 */
interface EntityAwareInterface
{
    /**
     * @return static
     */
    public function enableIgnoreNotMappedFields(): static;

    /**
     * @return static
     */
    public function disableIgnoreNotMappedFields(): static;

    /**
     * Create new instance of EntityInterface implementation class & return it.
     *
     * @param  \stdClass|null $row
     * @param  bool $exists
     * @return TEntity
     */
    public function newEntity(\stdClass $row = null, bool $exists = false): EntityInterface;

    /**
     * Create new entity from array.
     * Array fields must be named as the entity properties name.
     *
     * @param  array<string|int|float|bool|null> $form
     * @return TEntity
     */
    public function newEntityFromArray(array $form): EntityInterface;

    /**
     * Update entity from form data.
     * Form fields must be named as the entity properties name.
     *
     * @param  TEntity $data
     * @param  array<string|int|float|bool|null> $form
     * @return TEntity
     */
    public function updateEntityFromArray(EntityInterface $data, array $form): EntityInterface;

    /**
     * Create new instance of EntityInterface implementation class & return it.
     * Remove prefix from result set field to retrieve the correct field name.
     *
     * @param  \stdClass $row
     * @param  string $suffix
     * @param  string $type
     * @return TEntity|null
     * @throws \LogicException
     */
    public function newEntitySuffixAware(\stdClass $row, string $suffix, string $type): ?EntityInterface;

    /**
     * Create new instance of EntityInterface implementation class & return it.
     *
     * @param  array<string|int|float|bool|null> $data
     * @param  array<mixed> $config
     * @return GenericEntity
     */
    public function newGenericEntity(array $data = [], array $config = []): GenericEntity;

    /**
     * Hydrate entity with form entity values
     *
     * @param  GenericEntity $genericEntity
     * @return TEntity
     */
    public function newEntityFromGeneric(GenericEntity $genericEntity): EntityInterface;

    /**
     * @param  TEntity $entity
     * @param  string $field
     * @return bool
     */
    public function isEntityUpdated(EntityInterface $entity, string $field): bool;

    /**
     * @param  TEntity $entity
     * @param  string $field
     * @return string|int|float|bool|null
     */
    public function getEntityValue(EntityInterface $entity, string $field): mixed;

    /**
     * Get array "key" => "value" for primaries keys.
     *
     * @param  TEntity $entity
     * @return array<string|int|float|bool|null>
     */
    public function getEntityPrimaryKeysValues(EntityInterface $entity): array;
}
