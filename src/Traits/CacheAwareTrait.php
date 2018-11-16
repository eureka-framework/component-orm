<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Eureka\Component\Orm\Traits;

use Eureka\Component\Database\Connection;
use Eureka\Component\Orm\EntityInterface;
use Eureka\Component\Orm\Query;
use Eureka\Component\Orm\RepositoryInterface;
use Psr\Cache\CacheItemPoolInterface;

/**
 * Cache trait to manage cache part in DataMapper.
 *
 * @author Romain Cottard
 */
trait CacheAwareTrait
{
    /** @var \Psr\Cache\CacheItemPoolInterface $cache Cache instance. Not connected if cache is not used. */
    protected $cache = null;

    /** @var bool $isCacheEnabledOnRead If cache is enabled for Mapper class (for read) */
    protected $isCacheEnabledOnRead = false;

    /** @var bool $cacheSkipMissingItemQuery If skip query after select from cache (when has no missing item) */
    protected $cacheSkipMissingItemQuery = false;

    /**
     * Enable cache on read queries.
     *
     * @return $this
     */
    public function enableCacheOnRead(): RepositoryInterface
    {
        $this->isCacheEnabledOnRead = true;

        return $this;
    }

    /**
     * Disable cache on read query.
     *
     * @return $this
     */
    public function disableCacheOnRead(): RepositoryInterface
    {
        $this->isCacheEnabledOnRead = false;

        return $this;
    }

    /**
     * Set cache instance.
     *
     * @param \Psr\Cache\CacheItemPoolInterface $cache
     * @return $this
     */
    protected function setCache(CacheItemPoolInterface $cache = null): RepositoryInterface
    {
        $this->cache = $cache;

        return $this;
    }

    /**
     * Try to get all entities from cache.
     * Return list of entities (for found) / null (for not found in cache)
     *
     * @param \Eureka\Component\Database\Connection $connection
     * @param \Eureka\Component\Orm\RepositoryInterface $mapper
     * @param \Eureka\Component\Orm\Query\SelectBuilder $queryBuilder
     * @return array
     * @throws \Eureka\Component\Orm\Exception\InvalidQueryException
     * @throws \Eureka\Component\Orm\Exception\OrmException
     * @throws \Psr\Cache\InvalidArgumentException
     */
    protected function selectFromCache(Connection $connection, RepositoryInterface $mapper, Query\SelectBuilder $queryBuilder): array
    {
        if (!$this->isCacheEnabledOnRead) {
            return [];
        }

        if (count($mapper->getPrimaryKeys()) === 0) {
            return [];
        }

        $statement = $connection->prepare($queryBuilder->getQuery());
        $statement->execute($queryBuilder->getBind());

        $queryBuilder->clear(true);

        //~ Save total number of row from query
        $this->rowCount = $statement->rowCount();

        $hasOnePrimaryKey = count($mapper->getPrimaryKeys()) === 1;
        $collection       = [];

        while (false !== ($row = $statement->fetch(Connection::FETCH_OBJ))) {

            $entityIdInstance = $mapper->newEntity($row, true);

            //~ Pre-fill collection to keep the order
            $collection[$entityIdInstance->getCacheKey()] = null;

            $entity = $this->getCacheEntity($entityIdInstance);
            if ($entity === null) {
                $this->cacheSkipMissingItemQuery = true;
                $values                          = $mapper->getEntityPrimaryKeysValues($entityIdInstance);
                if ($hasOnePrimaryKey) {
                    $addIn[] = current($values);
                } else {
                    $queryBuilder->addWhereKeysOr($values);
                }
            } else {
                $collection[$entityIdInstance->getCacheKey()] = $entity;
            }
        }

        if (!empty($addIn) && !empty($values)) {
            $queryBuilder->addIn(key($values), $addIn);
        }

        return $collection;
    }

    /**
     * Get Data object from cache if is enabled.
     *
     * @param  \Eureka\Component\Orm\EntityInterface $entity
     * @return null|EntityInterface
     * @throws \Psr\Cache\InvalidArgumentException
     */
    protected function getCacheEntity(EntityInterface $entity): ?EntityInterface
    {
        if (!$this->isCacheEnabledOnRead) {
            return null;
        }

        $cacheItem = $this->cache->getItem($entity->getCacheKey());

        return $cacheItem->get();
    }

    /**
     * Delete cache
     *
     * @param  EntityInterface $entity
     * @return $this
     * @throws \Psr\Cache\InvalidArgumentException
     */
    protected function deleteCacheEntity(EntityInterface $entity): RepositoryInterface
    {
        if (!$this->cache instanceof CacheItemPoolInterface) {
            return $this;
        }

        $this->cache->deleteItem($entity->getCacheKey());

        return $this;
    }

    /**
     * Set data into cache if enabled.
     *
     * @param  EntityInterface $entity
     * @return $this
     * @throws \Psr\Cache\InvalidArgumentException
     */
    protected function setCacheEntity(EntityInterface $entity): RepositoryInterface
    {
        if (!$this->cache instanceof CacheItemPoolInterface) {
            return $this;
        }

        $cacheItem = $this->cache->getItem($entity->getCacheKey());

        $cacheItem->set($entity);

        $this->cache->save($cacheItem);

        return $this;
    }
}
