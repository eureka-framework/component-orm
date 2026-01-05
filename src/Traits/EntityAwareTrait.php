<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=0);

namespace Eureka\Component\Orm\Traits;

use Eureka\Component\Orm\EntityAwareInterface;
use Eureka\Component\Orm\EntityInterface;
use Eureka\Component\Orm\Enumerator\JoinType;
use Eureka\Component\Orm\RepositoryInterface;
use Eureka\Component\Validation\Entity\GenericEntity;

/**
 * Entity Trait.
 *
 * @author Romain Cottard
 *
 * @template TEntity of EntityInterface
 */
trait EntityAwareTrait
{
    /** @phpstan-var class-string<TEntity> $entityClass */
    protected string $entityClass;

    /** @var bool $ignoreNotMappedFields If true, does not throw an exception for not mapped fields in setDataValue */
    protected bool $ignoreNotMappedFields = false;

    public function enableIgnoreNotMappedFields(): static
    {
        $this->ignoreNotMappedFields = true;

        return $this;
    }

    public function disableIgnoreNotMappedFields(): static
    {
        $this->ignoreNotMappedFields = false;

        return $this;
    }

    /**
     * @phpstan-param class-string<TEntity> $entityClass
     */
    public function setEntityClass(string $entityClass): static
    {
        $this->entityClass = $entityClass;

        return $this;
    }

    /**
     * Create new instance of EntityInterface implementation class & return it.
     *
     * @phpstan-return TEntity
     */
    public function newEntity(?\stdClass $row = null, bool $exists = false): object
    {
        $entity = new $this->entityClass($this, $this->getValidatorFactory(), $this->getValidatorEntityFactory());

        if ($row instanceof \stdClass) {
            foreach ((array) $row as $field => $value) {
                $this->setEntityValue($entity, $field, $value);
            }
        }

        $entity->setExists($exists);

        return $entity;
    }

    /**
     * Create new entity from array.
     * Array fields must be named as the entity properties name.
     *
     * @param  array<string|int|float|bool|null> $form
     * @phpstan-return TEntity
     */
    public function newEntityFromArray(array $form): EntityInterface
    {
        return $this->updateEntityFromArray($this->newEntity(), $form);
    }

    /**
     * Hydrate entity with form entity values
     *
     * @param  GenericEntity $genericEntity
     * @phpstan-return TEntity
     */
    public function newEntityFromGeneric(GenericEntity $genericEntity): object
    {
        $entity = $this->newEntity();
        $entity->hydrateFromGenericEntity($genericEntity);

        return $entity;
    }

    /**
     * Update entity from form data.
     * Form fields must be named as the entity properties name.
     *
     * @phpstan-param TEntity $entity
     * @param array<string|int|float|bool|null> $form
     * @phpstan-return TEntity
     */
    public function updateEntityFromArray(EntityInterface $entity, array $form): object
    {
        foreach ($this->getFields() as $field) {
            $map = $this->getNamesMap($field);

            if (!array_key_exists($map['property'], $form)) {
                continue;
            }

            $this->setEntityValue($entity, $field, $form[$map['property']]);
        }

        return $entity;
    }

    /**
     * Create new instance of EntityInterface implementation class & return it.
     * Remove prefix from result set field to retrieve the correct field name.
     *
     * @phpstan-return TEntity|null
     * @throws \LogicException
     */
    public function newEntitySuffixAware(\stdClass $row, string $suffix, string $type): ?object
    {
        /** @var TEntity $entity */
        $entity = new $this->entityClass($this, $this->getValidatorFactory(), $this->getValidatorEntityFactory());

        $data              = [];
        $hasSomeJoinValues = ($type !== JoinType::LEFT);

        //~ Check if join entity as value (or if is LEFT without left entity)
        foreach ((array) $row as $field => $value) {
            $suffixPosition = strrpos($field, $suffix);
            if (!empty($suffix) && $suffixPosition !== false) {
                $field = substr($field, 0, $suffixPosition);

                if ($type === JoinType::LEFT && $value !== null) {
                    $hasSomeJoinValues = true;
                }
            }

            $data[$field] = $value;
        }

        if (!$hasSomeJoinValues) {
            return null;
        }

        $entityExists = true;
        foreach ($data as $field => $value) {
            try {
                $this->setEntityValue($entity, $field, $value);
            } catch (\TypeError) { // @codeCoverageIgnoreStart
                $entityExists = false;
            } // @codeCoverageIgnoreEnd
        }

        $entity->setExists($entityExists);

        return $entity;
    }


    /**
     * @phpstan-param TEntity $entity
     */
    public function isEntityUpdated(EntityInterface $entity, string $field): bool
    {
        if (!isset($this->entityNamesMap[$field]['property'])) {
            // @codeCoverageIgnoreStart
            throw new \DomainException(
                'Cannot define field as updated: field have not mapping with entity instance (field: ' . $field . ')',
            );
            // @codeCoverageIgnoreEnd
        }

        $property = $this->getPropertyForField($field);

        return $entity->isUpdated($property);
    }

    /**
     * @phpstan-param TEntity $entity
     */
    public function getEntityValue(EntityInterface $entity, string $field): string|int|float|bool|null
    {
        if (!isset($this->entityNamesMap[$field]['get'])) {
            throw new \DomainException(
                'Cannot get field value: field have no mapping with entity instance (field: ' . $field . ')',
            );
        }

        $getter = $this->entityNamesMap[$field]['get'];

        /** @var string|int|float|bool|null $value */
        $value  = $entity->{$getter}();

        return $value;
    }

    /**
     * Get array "key" => "value" for primaries keys.
     *
     * @phpstan-param TEntity $entity
     * @return array<string|int|float|bool|null>
     */
    public function getEntityPrimaryKeysValues(EntityInterface $entity): array
    {
        $values = [];

        foreach ($this->getPrimaryKeys() as $key) {
            $getter       = $this->getGetterForField($key);

            /** @var string|int|float|bool|null $value */
            $value        = $entity->{$getter}();
            $values[$key] = $value;
        }

        return $values;
    }

    /**
     * Set value into EntityInterface instance based on field value
     *
     * @phpstan-param  TEntity $entity
     * @throws \DomainException
     * @throws \TypeError
     */
    protected function setEntityValue(EntityInterface $entity, string $field, mixed $value): static
    {
        if (!isset($this->entityNamesMap[$field]['set'])) {
            if (true === $this->ignoreNotMappedFields) {
                return $this;
            }

            throw new \DomainException('Field have not mapping with entity instance (field: ' . $field . ')');
        }

        $method = $this->getSetterForField($field);

        $entity->{$method}($value);

        return $this;
    }
}
