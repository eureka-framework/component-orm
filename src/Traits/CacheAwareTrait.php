<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Eureka\Component\Orm\Traits;

use Eureka\Component\Orm\AbstractMapper;
use Eureka\Component\Orm\EntityInterface;
use Eureka\Component\Orm\Exception\InvalidQueryException;
use Eureka\Component\Orm\Exception\OrmException;
use Eureka\Component\Orm\Query;
use Eureka\Component\Orm\RepositoryInterface;
use PDO;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Cache\InvalidArgumentException;

/**
 * Cache trait to manage cache part in Data Mapper.
 *
 * @author Romain Cottard
 *
 * @template TEntity of EntityInterface
 * @template TRepository of RepositoryInterface
 */
trait CacheAwareTrait
{
    /** @var ?CacheItemPoolInterface $cache Cache instance. Not connected if cache is not used. */
    protected ?CacheItemPoolInterface $cache = null;

    /** @var bool $isCacheEnabledOnRead If cache is enabled for Mapper class (for read) */
    protected bool $isCacheEnabledOnRead = false;

    /** @var bool $cacheSkipMissingItemQuery If skip query after select from cache (when has no missing item) */
    protected bool $cacheSkipMissingItemQuery = false;

    /**
     * Enable cache on read queries.
     *
     * @return static
     */
    public function enableCacheOnRead(): static
    {
        $this->isCacheEnabledOnRead = true;

        return $this;
    }

    /**
     * Disable cache on read query.
     *
     * @return static
     */
    public function disableCacheOnRead(): static
    {
        $this->isCacheEnabledOnRead = false;

        return $this;
    }

    /**
     * Set cache instance.
     *
     * @param CacheItemPoolInterface|null $cache
     * @return static
     */
    protected function setCache(CacheItemPoolInterface $cache = null): static
    {
        $this->cache = $cache;

        return $this;
    }

    /**
     * Try to get all entities from cache.
     * Return list of entities (for found) / null (for not found in cache)
     *
     * @param AbstractMapper<TEntity, TRepository> $repository
     * @param Query\SelectBuilder<TRepository, TEntity> $queryBuilder
     * @return array<TEntity|null>
     * @throws InvalidQueryException
     * @throws OrmException
     */
    protected function selectFromCache(
        AbstractMapper $repository,
        Query\SelectBuilder $queryBuilder
    ): array {
        if (!$this->isCacheEnabledOnRead) {
            return []; // @codeCoverageIgnore
        }

        if (count($repository->getPrimaryKeys()) === 0) {
            return []; // @codeCoverageIgnore
        }

        $statement = $this->execute($queryBuilder->getQuery(false, '', true), $queryBuilder->getBind());

        $queryBuilder->clear(true);

        //~ Save total number of row from query
        $this->rowCount = $statement->rowCount();

        $hasOnePrimaryKey = count($repository->getPrimaryKeys()) === 1;
        $collection       = [];

        while (false !== ($row = $statement->fetch(PDO::FETCH_OBJ))) {
            $entityIdInstance = $repository->newEntity($row, true);

            //~ Pre-fill collection to keep the order
            $collection[$entityIdInstance->getCacheKey()] = null;

            $entityRow = $this->getCacheEntity($entityIdInstance->getCacheKey());
            if ($entityRow === null) {
                $values = $repository->getEntityPrimaryKeysValues($entityIdInstance);
                if ($hasOnePrimaryKey) {
                    $addIn[] = current($values);
                } else {
                    $queryBuilder->addWhereKeysOr($values);
                }
            } else {
                $collection[$entityIdInstance->getCacheKey()] = $repository->newEntity($entityRow, true);
            }
        }

        if (!empty($addIn) && !empty($values)) {
            $queryBuilder->addIn(key($values), $addIn);
        }

        //~ When retrieve all data from cache, skip missing cache item query.
        if (count(array_filter($collection)) === count($collection)) {
            $this->cacheSkipMissingItemQuery = true;
        }

        return $collection;
    }

    /**
     * Get Data object from cache if is enabled.
     *
     * @param string $cacheKey
     * @return \stdClass|null
     * @throws OrmException
     */
    protected function getCacheEntity(string $cacheKey): ?\stdClass
    {
        if (!$this->isCacheEnabledOnRead) {
            return null; // @codeCoverageIgnore
        }

        try {
            $cacheItem = $this->cache->getItem($cacheKey);
        } catch (InvalidArgumentException $exception) { // @codeCoverageIgnore
            throw new OrmException('Cannot delete cache', $exception->getCode(), $exception); // @codeCoverageIgnore
        }

        return $cacheItem->get();
    }

    /**
     * Delete cache
     *
     * @param string $cacheKey
     * @return static
     * @throws OrmException
     */
    protected function deleteCacheEntity(string $cacheKey): static
    {
        if (!$this->cache instanceof CacheItemPoolInterface) {
            return $this;
        }

        try {
            $this->cache->deleteItem($cacheKey);
        } catch (InvalidArgumentException $exception) { // @codeCoverageIgnore
            throw new OrmException('Cannot delete cache', $exception->getCode(), $exception); // @codeCoverageIgnore
        }

        return $this;
    }

    /**
     * Set data into cache if enabled.
     *
     * @param string $cacheKey
     * @param \stdClass $row
     * @return static
     * @throws OrmException
     */
    protected function setCacheEntity(string $cacheKey, \stdClass $row): static
    {
        if (!$this->cache instanceof CacheItemPoolInterface) {
            return $this;
        }

        try {
            $cacheItem = $this->cache->getItem($cacheKey);
            $cacheItem->set($row);
            $this->cache->save($cacheItem);
        } catch (InvalidArgumentException $exception) { // @codeCoverageIgnore
            throw new OrmException('Cannot save cache', $exception->getCode(), $exception); // @codeCoverageIgnore
        }

        return $this;
    }
}
