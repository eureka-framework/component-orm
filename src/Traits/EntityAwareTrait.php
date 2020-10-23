<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=0);

namespace Eureka\Component\Orm\Traits;

use Eureka\Component\Orm\EntityInterface;
use Eureka\Component\Orm\Enumerator\JoinType;
use Eureka\Component\Orm\RepositoryInterface;
use Eureka\Component\Validation\Entity\GenericEntity;

/**
 * Entity Trait.
 *
 * @author Romain Cottard
 */
trait EntityAwareTrait
{
    /** @var string $entityClass Name of class use to instance entity class. */
    protected string $entityClass = '';

    /** @var bool $ignoreNotMappedFields If true, does not throw an exception for not mapped fields (ie : COUNT()) in setDataValue */
    protected bool $ignoreNotMappedFields = false;

    /**
     * @return $this|RepositoryInterface
     */
    public function enableIgnoreNotMappedFields(): RepositoryInterface
    {
        $this->ignoreNotMappedFields = true;

        return $this;
    }

    /**
     * @return $this|RepositoryInterface
     */
    public function disableIgnoreNotMappedFields(): RepositoryInterface
    {
        $this->ignoreNotMappedFields = false;

        return $this;
    }

    /**
     * @param  string $entityClass
     * @return $this|RepositoryInterface
     */
    public function setEntityClass(string $entityClass): RepositoryInterface
    {
        $this->entityClass = $entityClass;

        return $this;
    }

    /**
     * Create new instance of EntityInterface implementation class & return it.
     *
     * @param  \stdClass|null $row
     * @param  bool $exists
     * @return EntityInterface
     */
    public function newEntity(\stdClass $row = null, bool $exists = false): EntityInterface
    {
        $entity = new $this->entityClass($this, $this->getValidatorFactory(), $this->getValidatorEntityFactory());

        if (!($entity instanceof EntityInterface)) {
            throw new \LogicException('Entity object is not an instance of EntityInterface class!'); // @codeCoverageIgnore
        }

        if ($row instanceof \stdClass) {
            foreach ($row as $field => $value) {
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
     * @param  array $form
     * @return EntityInterface
     */
    public function newEntityFromArray(array $form): EntityInterface
    {
        return $this->updateEntityFromArray($this->newEntity(), $form);
    }

    /**
     * Hydrate entity with form entity values
     *
     * @param  GenericEntity $genericEntity
     * @return EntityInterface
     */
    public function newEntityFromGeneric(GenericEntity $genericEntity): EntityInterface
    {
        $entity = $this->newEntity();
        $entity->hydrateFromGenericEntity($genericEntity);

        return $entity;
    }

    /**
     * Update entity from form data.
     * Form fields must be named as the entity properties name.
     *
     * @param EntityInterface $entity
     * @param array $form
     * @return EntityInterface
     */
    public function updateEntityFromArray(EntityInterface $entity, array $form): EntityInterface
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
     * @param  \stdClass $row
     * @param  string $suffix
     * @param  string $type
     * @return EntityInterface|null
     * @throws \LogicException
     */
    public function newEntitySuffixAware(\stdClass $row, string $suffix, string $type): ?EntityInterface
    {
        $entity = new $this->entityClass($this, $this->getValidatorFactory());

        if (!($entity instanceof EntityInterface)) {
            throw new \LogicException('Entity object is not an instance of AbstractData class!');
        }

        if (! ($row instanceof \stdClass)) {
            return null;
        }

        $data              = [];
        $hasSomeJoinValues = ($type !== JoinType::LEFT);

        //~ Check if join entity as value (or if is LEFT without left entity)
        foreach ($row as $field => $value) {
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

        foreach ($data as $field => $value) {
            try {
                $this->setEntityValue($entity, $field, $value);
            } catch (\TypeError $exception) {
                //~ Skip type error when data came from database
            }
        }

        $entity->setExists(true);

        return $entity;
    }


    /**
     * @param  EntityInterface $entity
     * @param  string $field
     * @return bool
     */
    public function isEntityUpdated(EntityInterface $entity, string $field): bool
    {
        if (!isset($this->entityNamesMap[$field]['property'])) {
            throw new \DomainException('Cannot define field as updated: field have not mapping with entity instance (field: ' . $field . ')');
        }

        $property = $this->entityNamesMap[$field]['property'];

        return $entity->isUpdated($property);
    }

    /**
     * @param  EntityInterface $entity
     * @param  string $field
     * @return mixed
     */
    public function getEntityValue(EntityInterface $entity, string $field)
    {
        if (!isset($this->entityNamesMap[$field]['get'])) {
            throw new \DomainException('Cannot get field value: field have no mapping with entity instance (field: ' . $field . ')');
        }

        $method = $this->entityNamesMap[$field]['get'];

        return $entity->{$method}();
    }

    /**
     * Get array "key" => "value" for primaries keys.
     *
     * @param  EntityInterface $entity
     * @return array
     */
    public function getEntityPrimaryKeysValues(EntityInterface $entity): array
    {
        $values = [];

        foreach ($this->getPrimaryKeys() as $key) {
            $getter       = $this->entityNamesMap[$key]['get'];
            $values[$key] = $entity->{$getter}();
        }

        return $values;
    }

    /**
     * Set value into EntityInterface instance based on field value
     *
     * @param  EntityInterface $entity
     * @param  string $field
     * @param  mixed $value
     * @return self|RepositoryInterface
     * @throws \DomainException
     */
    protected function setEntityValue(EntityInterface $entity, string $field, $value): RepositoryInterface
    {
        if (!isset($this->entityNamesMap[$field]['set'])) {
            if (true === $this->ignoreNotMappedFields) {
                return $this;
            }

            throw new \DomainException('Field have not mapping with entity instance (field: ' . $field . ')');
        }

        $method = $this->entityNamesMap[$field]['set'];

        $entity->{$method}($value);

        return $this;
    }
}
