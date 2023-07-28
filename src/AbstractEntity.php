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
 *
 * @template TRepository of RepositoryInterface
 * @template TEntity of EntityInterface
 * @implements EntityInterface<TRepository, TEntity>
 */
abstract class AbstractEntity implements EntityInterface
{
    use ValidatorAwareTrait;

    /** @var bool $exists If data already exists in db for example. */
    private bool $exists = false;

    /** @var bool[] $updated List of updated field */
    private array $updated = [];

    /** @phpstan-var TRepository $repository Entity repository */
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
     * @return static
     * @codeCoverageIgnore
     */
    public function setAutoIncrementId(int $id): static
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
    public function setExists(bool $exists): static
    {
        $this->exists = $exists;

        return $this;
    }

    /**
     * If at least one data has been updated.
     * If property name is specified, check only property.
     *
     * @param  string|null $property
     * @return bool
     */
    public function isUpdated(?string $property = null): bool
    {
        if (null === $property) {
            return \count($this->updated) > 0;
        }

        return isset($this->updated[$property]) && $this->updated[$property] === true;
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
     * @return static
     */
    public function resetUpdated(): static
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
            if (str_starts_with($attribute, 'join')) {
                $this->$attribute = null;
            }
        }
    }

    /**
     * @return TRepository
     */
    public function getRepository(): RepositoryInterface
    {
        return $this->repository;
    }

    /**
     * Get form entity container.
     *
     * @return GenericEntity
     */
    public function getGenericEntity(): GenericEntity
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
     * @return static
     */
    public function hydrateFromGenericEntity(GenericEntity $genericEntity): static
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
     * @param TRepository $repository
     * @return void
     */
    protected function setRepository(RepositoryInterface $repository): void
    {
        $this->repository = $repository;
    }
}
