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
 * Entity aware interface.
 *
 * @author Romain Cottard
 */
interface EntityAwareInterface
{
    /**
     * @return $this
     */
    public function enableIgnoreNotMappedFields();

    /**
     * @return $this
     */
    public function disableIgnoreNotMappedFields();

    /**
     * Create new instance of EntityInterface implementation class & return it.
     *
     * @param  \stdClass|null $row
     * @param  bool $exists
     * @return EntityInterface
     */
    public function newEntity(\stdClass $row = null, bool $exists = false);

    /**
     * Create new entity from array.
     * Array fields must be named as the entity properties name.
     *
     * @param  array $form
     * @return EntityInterface
     */
    public function newEntityFromArray(array $form);

    /**
     * Update entity from form data.
     * Form fields must be named as the entity properties name.
     *
     * @param  EntityInterface $data
     * @param  array $form
     * @return EntityInterface
     */
    public function updateEntityFromArray(EntityInterface $data, array $form);

    /**
     * Create new instance of EntityInterface implementation class & return it.
     * Remove prefix from result set field to retrieve the correct field name.
     *
     * @param  \stdClass $row
     * @param  string $suffix
     * @param  string $type
     * @return EntityInterface
     * @throws \LogicException
     */
    public function newEntitySuffixAware(\stdClass $row, string $suffix, string $type);

    /**
     * Create new instance of EntityInterface implementation class & return it.
     *
     * @param  array $data
     * @param  array $config
     * @return GenericEntity
     */
    public function newGenericEntity(array $data = [], array $config = []): GenericEntity;

    /**
     * Hydrate entity with form entity values
     *
     * @param  GenericEntity $genericEntity
     * @return EntityInterface
     */
    public function newEntityFromGeneric(GenericEntity $genericEntity);

    /**
     * @param  EntityInterface $entity
     * @param  string $field
     * @return bool
     */
    public function isEntityUpdated(EntityInterface $entity, string $field): bool;

    /**
     * @param  EntityInterface $entity
     * @param  string $field
     * @return mixed
     */
    public function getEntityValue(EntityInterface $entity, string $field);

    /**
     * Get array "key" => "value" for primaries keys.
     *
     * @param  EntityInterface $entity
     * @return array
     */
    public function getEntityPrimaryKeysValues(EntityInterface $entity): array;
}
