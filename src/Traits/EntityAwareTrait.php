<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Eureka\Component\Orm\Traits;

use Eureka\Component\Orm\EntityInterface;
use Eureka\Component\Orm\RepositoryInterface;

/**
 * Entity Trait.
 *
 * @author Romain Cottard
 */
trait EntityAwareTrait
{
    /** @var string $entityClass Name of class use to instance DataMapper Data class. */
    protected $entityClass = '';

    /** @var bool If true, does not throw an exception for not mapped fields (ie : COUNT()) in setDataValue */
    protected $ignoreNotMappedFields = false;

    /**
     * @return $this
     */
    public function enableIgnoreNotMappedFields(): RepositoryInterface
    {
        $this->ignoreNotMappedFields = true;

        return $this;
    }

    /**
     * @return $this
     */
    public function disableIgnoreNotMappedFields(): RepositoryInterface
    {
        $this->ignoreNotMappedFields = false;

        return $this;
    }

    /**
     * @param  string $entityClass
     * @return $this
     */
    public function setEntityClass(string $entityClass): RepositoryInterface
    {
        $this->entityClass = $entityClass;

        return $this;
    }

    /**
     * @param  \stdClass|null $row
     * @param  bool $exists
     * @return \Eureka\Component\Orm\EntityInterface
     */
    public function newEntity(\stdClass $row = null, bool $exists = false): EntityInterface
    {
        $entity = new $this->entityClass($this->mappers, $this->validatorFactoryContainer);

        if (!($entity instanceof EntityInterface)) {
            throw new \LogicException('Data object not instance of EntityInterface class!');
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
     * @param  \Eureka\Component\Orm\EntityInterface $entity
     * @param  string $field
     * @return bool
     */
    public function isEntityUpdated(EntityInterface $entity, string $field): bool
    {
        if (!isset($this->dataNamesMap[$field]['property'])) {
            throw new \DomainException('Cannot define field as updated: field have not mapping with Data instance (field: ' . $field . ')');
        }

        $property = $this->entityNamesMap[$field]['property'];

        return $entity->isUpdated($property);
    }

    /**
     * @param  \Eureka\Component\Orm\EntityInterface $entity
     * @param  string $field
     * @return mixed
     */
    public function getEntityValue(EntityInterface $entity, string $field)
    {
        if (!isset($this->dataNamesMap[$field]['get'])) {
            throw new \DomainException('Cannot get field value: field have no mapping with Data instance (field: ' . $field . ')');
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
    protected function getEntityPrimaryKeysValues(EntityInterface $entity): array
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
     * @return mixed
     * @throws \DomainException
     */
    protected function setEntityValue(EntityInterface $entity, string $field, $value): RepositoryInterface
    {
        if (!isset($this->entityNamesMap[$field]['set'])) {
            if (true === $this->ignoreNotMappedFields) {
                return $this;
            }

            throw new \DomainException('Field have not mapping with Data instance (field: ' . $field . ')');
        }

        $method = $this->entityNamesMap[$field]['set'];

        $entity->{$method}($value);

        return $this;
    }
}
