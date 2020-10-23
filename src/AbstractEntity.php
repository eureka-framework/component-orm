<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Eureka\Component\Orm;

use Eureka\Component\Orm\Traits\ValidatorAwareTrait;
use Eureka\Component\Validation\Entity\GenericEntity;

/**
 * DataMapper Data abstract class.
 *
 * @author Romain Cottard
 */
abstract class AbstractEntity implements EntityInterface
{
    use ValidatorAwareTrait;

    /** @var bool $exists If data already exists in db for example. */
    private bool $exists = false;

    /** @var bool $isDeleted If entity must be deleted instead of persist. */
    private bool $isDeleted = false;

    /** @var array $updated List of updated field */
    private array $updated = [];

    /** @var RepositoryInterface $repository Entity repository */
    private RepositoryInterface $repository;

    /**
     * Return cache key for the current data instance.
     *
     * @return string
     */
    abstract public function getCacheKey(): string;

    /**
     * Set auto increment value.
     * Must be overridden to use internal property setter method, according to the data class definition.
     *
     * @param  int $id
     * @return $this
     * @codeCoverageIgnore
     */
    public function setAutoIncrementId(int $id)
    {
        return $this;
    }

    /**
     * If the data set exists.
     *
     * @return bool
     */
    public function exists(): bool
    {
        return $this->exists;
    }

    /**
     * If the data set exists.
     *
     * @param  bool $exists
     * @return $this
     */
    public function setExists(bool $exists): EntityInterface
    {
        $this->exists = (bool) $exists;

        return $this;
    }

    /**
     * If at least one data has been updated.
     * If property name is specified, check only property.
     *
     * @param  ?string $property
     * @return bool
     */
    public function isUpdated(string $property = null): bool
    {
        if (null === $property) {
            return (count($this->updated) > 0);
        }

        return (isset($this->updated[$property]) && $this->updated[$property] === true);
    }

    /**
     * Flag property as updated
     *
     * @param  string $property
     * @return void
     */
    protected function markFieldAsUpdated(string $property): void
    {
        $this->updated[$property] = true;
    }

    /**
     * Reset updated list of properties
     *
     * @return $this
     */
    public function resetUpdated(): EntityInterface
    {
        $this->updated = [];

        return $this;
    }

    /**
     * Empties the join* fields
     */
    public function resetLazyLoadedData(): void
    {
        $attributes = get_object_vars($this);
        foreach ($attributes as $attribute => $value) {
            if (strpos($attribute, 'join') === 0) {
                $this->$attribute = null;
            }
        }
    }

    /**
     * @return RepositoryInterface
     */
    public function getRepository(): RepositoryInterface
    {
        return $this->repository;
    }

    /**
     * Get form entity container.
     *
     * @param  void
     * @return GenericEntity
     */
    public function getGenericEntity()
    {
        $genericEntity = $this->newGenericEntity([]);
        $repository    = $this->getRepository();

        foreach ($repository->getFields() as $field) {
            $config = $repository->getNamesMap($field);
            $getter = $config['get'];
            $setter = $config['set'];

            $genericEntity->$setter($this->$getter());
        }

        return $genericEntity;
    }

    /**
     * Hydrate entity with form entity values
     *
     * @param  GenericEntity $genericEntity
     * @return $this
     */
    public function hydrateFromGenericEntity(GenericEntity $genericEntity)
    {
        $repository = $this->getRepository();
        foreach ($repository->getFields() as $field) {
            $config = $repository->getNamesMap($field);
            $getter = $config['get'];
            $setter = $config['set'];

            $newValue = $genericEntity->$getter();
            if ($newValue !== null) {
                $this->$setter($newValue);
            }
        }

        return $this;
    }

    /**
     * @param RepositoryInterface $repository
     * @return void
     */
    protected function setRepository(RepositoryInterface $repository): void
    {
        $this->repository = $repository;
    }
}
