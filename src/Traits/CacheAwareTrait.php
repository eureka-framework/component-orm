<?php declare(strict_types=1);

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eureka\Component\Orm\Traits;

use Eureka\Component\Database\Connection;
use Eureka\Component\Orm\EntityInterface;
use Eureka\Component\Orm\Exception\InvalidQueryException;
use Eureka\Component\Orm\Exception\OrmException;
use Eureka\Component\Orm\Query;
use Eureka\Component\Orm\RepositoryInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Cache\InvalidArgumentException;

/**
 * Cache trait to manage cache part in Data Mapper.
 *
 * @author Romain Cottard
 */
trait CacheAwareTrait
{
    /** @var CacheItemPoolInterface $cache Cache instance. Not connected if cache is not used. */
    protected $cache = null;

    /** @var bool $isCacheEnabledOnRead If cache is enabled for Mapper class (for read) */
    protected $isCacheEnabledOnRead = false;

    /** @var bool $cacheSkipMissingItemQuery If skip query after select from cache (when has no missing item) */
    protected $cacheSkipMissingItemQuery = false;

    /**
     * Enable cache on read queries.
     *
     * @return RepositoryInterface
     */
    public function enableCacheOnRead(): RepositoryInterface
    {
        $this->isCacheEnabledOnRead = true;

        return $this;
    }

    /**
     * Disable cache on read query.
     *
     * @return RepositoryInterface
     */
    public function disableCacheOnRead(): RepositoryInterface
    {
        $this->isCacheEnabledOnRead = false;

        return $this;
    }

    /**
     * Set cache instance.
     *
     * @param CacheItemPoolInterface $cache
     * @return RepositoryInterface
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
     * @param Connection $connection
     * @param RepositoryInterface $mapper
     * @param Query\SelectBuilder $queryBuilder
     * @return array
     * @throws InvalidQueryException
     * @throws OrmException
     */
    protected function selectFromCache(
        Connection $connection,
        RepositoryInterface $mapper,
        Query\SelectBuilder $queryBuilder
    ): array {
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

                $values = $mapper->getEntityPrimaryKeysValues($entityIdInstance);
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
     * @param  EntityInterface $entity
     * @return null|EntityInterface
     * @throws OrmException
     */
    protected function getCacheEntity(EntityInterface $entity): ?EntityInterface
    {
        if (!$this->isCacheEnabledOnRead) {
            return null;
        }

        try {
            $cacheItem = $this->cache->getItem($entity->getCacheKey());
        } catch (InvalidArgumentException $exception) {
            throw new OrmException('Cannot delete cache', $exception->getCode(), $exception);
        }

        return $cacheItem->get();
    }

    /**
     * Delete cache
     *
     * @param  EntityInterface $entity
     * @return RepositoryInterface
     * @throws OrmException
     */
    protected function deleteCacheEntity(EntityInterface $entity): RepositoryInterface
    {
        if (!$this->cache instanceof CacheItemPoolInterface) {
            return $this;
        }

        try {
            $this->cache->deleteItem($entity->getCacheKey());
        } catch (InvalidArgumentException $exception) {
            throw new OrmException('Cannot delete cache', $exception->getCode(), $exception);
        }

        return $this;
    }

    /**
     * Set data into cache if enabled.
     *
     * @param  EntityInterface $entity
     * @return RepositoryInterface
     * @throws OrmException
     */
    protected function setCacheEntity(EntityInterface $entity): RepositoryInterface
    {
        if (!$this->cache instanceof CacheItemPoolInterface) {
            return $this;
        }

        try {
            $cacheItem = $this->cache->getItem($entity->getCacheKey());

            $cacheItem->set($entity);

            $this->cache->save($cacheItem);
        } catch (InvalidArgumentException $exception) {
            throw new OrmException('Cannot save cache', $exception->getCode(), $exception);
        }

        return $this;
    }
}
