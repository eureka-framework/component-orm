<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eureka\Component\Orm;

use Eureka\Component\Database\Connection;
use Eureka\Component\Orm\Exception\EntityNotExistsException;
use Eureka\Component\Orm\Exception\InsertFailedException;
use Eureka\Component\Orm\Exception\OrmException;
use Eureka\Component\Orm\Query;
use Psr\Cache\CacheItemPoolInterface;

/**
 * DataMapper Mapper abstract class.
 *
 * @author Romain Cottard
 */
abstract class AbstractMapper implements RepositoryInterface
{
    /** @var string $dataClass Name of class use to instance DataMapper Data class. */
    protected $dataClass = '';

    /** @var \Eureka\Component\Orm\AbstractMapper[] $mappers */
    protected $mappers = [];

    /** @var \Psr\Cache\CacheItemPoolInterface $cache Cache instance. Not connected if cache is not used. */
    protected $cache = null;

    /** @var Connection $connection Connection instance */
    protected $connection = null;

    /** @var bool $isCacheEnabledOnRead If cache is enabled for Mapper class (for read) */
    protected $isCacheEnabledOnRead = false;

    /** @var string $cacheName Name of config cache to use. */
    protected $cacheName = '';

    /** @var string $table Table name. */
    protected $table = '';

    /** @var string[] $fields List of fields */
    protected $fields = [];

    /** @var string[] $primaryKeys List of primary keys fields */
    protected $primaryKeys = [];

    /** @var string[] $keys List of keys fields */
    protected $keys = [];

    /** @var string[][] $dataNamesMap List of mapped fields => getters/setters/properties keys */
    protected $dataNamesMap = [];

    /** @var int $lastId Auto increment id of the last insert query. */
    protected $lastId = 0;

    /** @var bool If true, does not throw an exception for not mapped fields (ie : COUNT()) in setDataValue */
    protected $ignoreNotMappedFields = false;

    /** @var int $rowCount The number of rows affected by the last SQL statement */
    protected $rowCount = 0;

    /** @var bool $cacheSkipMissingItemQuery If skip query after select from cache (when has no missing item) */
    protected $cacheSkipMissingItemQuery = false;

    /**
     * AbstractMapper constructor.
     *
     * @param Connection $connection
     * @param AbstractMapper[] $mappers
     * @param CacheItemPoolInterface $cache
     * @param bool $enableCacheOnRead
     */
    public function __construct(Connection $connection, $mappers = [], CacheItemPoolInterface $cache = null, $enableCacheOnRead = false)
    {
        $this->connection = $connection;
        $this->mappers    = $mappers;
        $this->cache = $cache;

        if ($enableCacheOnRead) {
            $this->enableCacheOnRead();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function addMappers($mappers)
    {
        $this->mappers = array_merge($this->mappers, $mappers);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function enableCacheOnRead()
    {
        $this->isCacheEnabledOnRead = true;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function disableCacheOnRead()
    {
        $this->isCacheEnabledOnRead = false;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function enableIgnoreNotMappedFields()
    {
        $this->ignoreNotMappedFields = true;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function disableIgnoreNotMappedFields()
    {
        $this->ignoreNotMappedFields = false;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getFields()
    {
        return $this->fields;
    }

    /**
     * {@inheritdoc}
     */
    public function getPrimaryKeys()
    {
        return $this->primaryKeys;
    }

    /**
     * {@inheritdoc}
     */
    public function getNamesMap($field)
    {
        if (!isset($this->dataNamesMap[$field])) {
            throw new \OutOfRangeException('Specified field does not exist in data names map');
        }

        return $this->dataNamesMap[$field];
    }

    /**
     * {@inheritdoc}
     */
    public function getTable()
    {
        return $this->table;
    }

    /**
     * {@inheritdoc}
     */
    public function newEntity(\stdClass $row = null, $exists = false)
    {
        $data = new $this->dataClass($this->mappers);

        if (!($data instanceof EntityInterface)) {
            throw new \LogicException('Data object not instance of EntityInterface class!');
        }

        if ($row instanceof \stdClass) {
            foreach ($row as $field => $value) {
                $this->setDataValue($data, $field, $value);
            }
        }

        $data->setExists($exists);

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function getLastId()
    {
        return $this->lastId;
    }

    /**
     * {@inheritdoc}
     */
    public function getMaxId()
    {
        if (count($this->primaryKeys) > 1) {
            throw new \LogicException(__METHOD__ . '|Cannot use getMaxId() method for table with multiple primary keys !');
        }

        $field = reset($this->primaryKeys);

        $statement = $this->connection->prepare('SELECT MAX(' . $field . ') AS ' . $field . ' FROM ' . $this->getTable());
        $statement->execute();

        return $statement->fetch(Connection::FETCH_OBJ)->{$field};
    }

    /**
     * {@inheritdoc}
     */
    public function rowCount()
    {
        return $this->rowCount;
    }

    /**
     * {@inheritdoc}
     */
    public function count(Query\QueryBuilder $queryBuilder, $field = '*')
    {
        $statement = $this->connection->prepare($queryBuilder->getQueryCount($field));
        $statement->execute($queryBuilder->getBind());

        $queryBuilder->clear();

        return (int) $statement->fetchColumn(0);
    }

    /**
     * {@inheritdoc}
     */
    public function rowExists(Query\SelectBuilder $queryBuilder)
    {
        try {
            $this->selectOne($queryBuilder);

            return true;
        } catch (EntityNotExistsException $exception) {
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function query(Query\QueryBuilderInterface $queryBuilder)
    {
        $indexedBy = $queryBuilder->getListIndexedByField();
        $statement = $this->connection->prepare($queryBuilder->getQuery());
        $statement->execute($queryBuilder->getBind());

        $collection = [];

        $queryBuilder->clear();

        $id = 0;
        while (false !== ($row = $statement->fetch(Connection::FETCH_OBJ))) {
			if ($indexedBy !== null && !isset($list->data[0]->$indexedBy)) {
                throw new OrmException('List is supposed to be indexed by a column that does not exist: '.$indexedBy);
            }

            $index = $indexedBy !== null ? $row->$indexedBy : $id++;
            $collection[$index] = $this->newEntity($row, true);
        }

        return $collection;
    }

    /**
     * {@inheritdoc}
     */
    public function queryRows(Query\QueryBuilderInterface $queryBuilder)
    {
        $statement = $this->connection->prepare($queryBuilder->getQuery());
        $statement->execute($queryBuilder->getBind());

        $queryBuilder->clear();

        $collection = array();

        while (false !== ($row = $statement->fetch(Connection::FETCH_OBJ))) {
            $collection[] = $row;
        }

        return $collection;
    }

    /**
     * {@inheritdoc}
     */
    public function delete(EntityInterface $entity)
    {
        $queryBuilder = $this->getQueryBuilder(Query\Factory::TYPE_DELETE, $entity);

        $statement = $this->connection->prepare($queryBuilder->getQuery());
        $result    = $statement->execute($queryBuilder->getBind());

        //~ Reset some data
        $entity->setExists(false);
        $entity->resetUpdated();

        //~ Clear
        $queryBuilder->clear();
        $this->deleteCache($entity);

        return (bool) $result;
    }

    /**
     * {@inheritdoc}
     */
    public function insert(EntityInterface $entity, $onDuplicateUpdate = false, $onDuplicateIgnore = false)
    {
        if ($entity->exists() && !$entity->isUpdated()) {
            return false;
        }

        $queryBuilder = $this->getQueryBuilder(Query\Factory::TYPE_INSERT, $entity);

        $statement = $this->connection->prepare($queryBuilder->getQuery($onDuplicateUpdate, $onDuplicateIgnore));
        $statement->execute($queryBuilder->getBind());

		if ($onDuplicateIgnore && $statement->rowCount() === 0) {
            throw new InsertFailedException(__METHOD__ . 'INSERT IGNORE could not insert (duplicate key or error)');
        }

        //~ If has auto increment key (commonly, is a primary key), set value
        $lastInsertId = (int) $this->connection->lastInsertId();
        if ($lastInsertId > 0) {
            $this->lastId = $lastInsertId;

            $entity->setAutoIncrementId($this->getLastId());
        }

        //~ Reset some data
        $entity->setExists(true);
        $entity->resetUpdated();

        //~ Clear
        $queryBuilder->clear();
        $this->deleteCache($entity);

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function update(EntityInterface $entity)
    {
        if (!$entity->isUpdated()) {
            return false;
        }

        $queryBuilder = $this->getQueryBuilder(Query\Factory::TYPE_UPDATE, $entity);

        $statement = $this->connection->prepare($queryBuilder->getQuery());
        $result    = $statement->execute($queryBuilder->getBind());

        //~ Reset some data
        $entity->resetUpdated();

        //~ Clear
        $queryBuilder->clear();
        $this->deleteCache($entity);

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function persist(EntityInterface $entity, $onDuplicateUpdate = false, $onDuplicateIgnore = false)
    {
        if ($entity->exists()) {
            return $this->update($entity);
        } else {
            return $this->insert($entity, $onDuplicateUpdate, $onDuplicateIgnore);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function findById($id)
    {
        $primaryKeys = $this->getPrimaryKeys();
        if (count($primaryKeys) > 1) {
            throw new \LogicException(__METHOD__ . '|Cannot use findById() method for table with multiple primary keys !');
        }

        $field = reset($primaryKeys);

        return $this->findByKeys([$field => $id]);
    }

    /**
     * {@inheritdoc}
     */
    public function findByKeys(array $keys)
    {
        $queryBuilder = $this->getQueryBuilder(Query\Factory::TYPE_SELECT);
        foreach ($keys as $field => $value) {
            $queryBuilder->addWhere($field, $value);
        }

        return $this->selectOne($queryBuilder);
    }

    /**
     * {@inheritdoc}
     */
    public function findAllByKeys(array $keys)
    {
        $queryBuilder = $this->getQueryBuilder(Query\Factory::TYPE_SELECT);
        foreach ($keys as $field => $value) {
            $queryBuilder->addWhere($field, $value);
        }

        return $this->select($queryBuilder);
    }

    /**
     * {@inheritdoc}
     */
    public function selectOne(Query\SelectBuilder $queryBuilder)
    {
        $queryBuilder->setLimit(1);

        $collection = $this->select($queryBuilder);

        if (empty($collection)) {
            throw new EntityNotExistsException('No data for current selection', 0);
        }

        return current($collection);
    }

    /**
     * {@inheritdoc}
     */
    public function select(Query\SelectBuilder $queryBuilder)
    {
        $collection = [];

        if ($this->isCacheEnabledOnRead) {
            $collection = $this->selectFromCache($queryBuilder);
        }

        if ($this->cacheSkipMissingItemQuery) {
            $this->cacheSkipMissingItemQuery = false;
            $queryBuilder->clear();

            return $collection;
        }

        //~ Save total number of row from query
        //$this->totalNumberOfRows = $list->total;

        $statement = $this->connection->prepare($queryBuilder->getQuery());
        $statement->execute($queryBuilder->getBind());

        while (false !== ($row = $statement->fetch(Connection::FETCH_OBJ))) {
            $data = $this->newEntity($row, true);
            $collection[$data->getCacheKey()] = $data;
            $this->setCache($data);
        }

        $queryBuilder->clear();

        return $collection;
    }

    /**
     * {@inheritdoc}
     */
    public function apply(callable $callback, Query\SelectBuilder $queryBuilder, $key, $start = 0, $end = -1, $batchSize = 10000)
    {
        if (!in_array($key, $this->primaryKeys)) {
            throw new \UnexpectedValueException(__METHOD__ . ' | The key must be a primary key.');
        }

        $statement = $this->connection->prepare('SELECT MIN(' . $key . ') AS MIN, MAX(' . $key . ') AS MAX FROM ' . $this->getTable());
        $statement->execute();

        $bounds = $statement->fetch(Connection::FETCH_OBJ);

        $minIndex          = max($start, $bounds->MIN);
        $maxIndex          = $end < 0 ? $bounds->MAX : min($end, $bounds->MAX);
        $currentBatchIndex = $minIndex;

        while ($currentBatchIndex <= $maxIndex) {
            $clonedQueryBuilder = clone $this->getQueryBuilder(Query\Factory::TYPE_SELECT);
            $clonedQueryBuilder
                ->addWhere($key, $currentBatchIndex, '>=')
                ->addWhere($key, $currentBatchIndex + $batchSize, '<')
            ;

            $batch = $this->query($clonedQueryBuilder);

            foreach ($batch as $item) {
                call_user_func($callback, $item);
            }

            $currentBatchIndex += $batchSize;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function quote($value)
    {
        return $this->connection->quote($value);
    }

    /**
     * {@inheritdoc}
     */
    public function beginTransaction()
    {
        $this->connection->beginTransaction();
    }

    /**
     * {@inheritdoc}
     */
    public function commit()
    {
        $this->connection->commit();
    }

    /**
     * {@inheritdoc}
     */
    public function rollBack()
    {
        $this->connection->rollBack();
    }

    /**
     * {@inheritdoc}
     */
    public function inTransaction()
    {
        return $this->connection->inTransaction();
    }

    /**
     * {@inheritdoc}
     */
    public function getQueryBuilder($type = Query\Factory::TYPE_SELECT, EntityInterface $entity = null)
    {
        return Query\Factory::getBuilder($type, $this, $entity);
    }

    /**
     * {@inheritdoc}
     */
    public function isDataUpdated($data, $field)
    {
        if (!isset($this->dataNamesMap[$field]['property'])) {
            throw new \DomainException('Field have not mapping with Data instance (field: ' . $field . ')');
        }

        $property = $this->dataNamesMap[$field]['property'];

        return $data->isUpdated($property);
    }

    /**
     * {@inheritdoc}
     */
    public function getDataValue($data, $field)
    {
        if (!isset($this->dataNamesMap[$field]['get'])) {
            throw new \DomainException('Field have not mapping with Data instance (field: ' . $field . ')');
        }

        $method = $this->dataNamesMap[$field]['get'];

        return $data->{$method}();
    }

    /**
     * Get array "key" => "value" for primaries keys.
     *
     * @param  EntityInterface $data
     * @return array
     */
    private function getDataPrimaryKeysValues(EntityInterface $data)
    {
        $values = [];

        foreach ($this->getPrimaryKeys() as $key) {
            $getter       = $this->dataNamesMap[$key]['get'];
            $values[$key] = $data->{$getter}();
        }

        return $values;
    }

    /**
     * Set value into EntityInterface instance based on field value
     *
     * @param  EntityInterface $data
     * @param  string $field
     * @param  mixed $value
     * @return mixed
     * @throws \DomainException
     */
    private function setDataValue($data, $field, $value)
    {
        if (!isset($this->dataNamesMap[$field]['set'])) {
            if (true === $this->ignoreNotMappedFields) {
                return $this;
            }

            throw new \DomainException('Field have not mapping with Data instance (field: ' . $field . ')');
        }

        $method = $this->dataNamesMap[$field]['set'];

        $data->{$method}($value);

        return $this;
    }

    /**
     * Try to get all entities from cache.
     * Return list of entities (for found) / null (for not found in cache)
     *
     * @param  Query\SelectBuilder $queryBuilder
     * @return EntityInterface[]
     * @throws \Psr\Cache\InvalidArgumentException
     * @throws \Exception
     */
    private function selectFromCache(Query\SelectBuilder $queryBuilder)
    {
        if (!$this->isCacheEnabledOnRead) {
            return [];
        }

        $primaryKeys = $this->getPrimaryKeys();

        if (count($primaryKeys) === 0) {
            return [];
        }

        $statement = $this->connection->prepare($queryBuilder->getQuery());
        $statement->execute($queryBuilder->getBind());

        $queryBuilder->clear(true);

        //~ Save total number of row from query
        $this->totalNumberOfRows = $statement->rowCount();

        $hasOnePrimaryKey = count($primaryKeys) === 1;
        $collection       = [];

        while (false !== ($row = $statement->fetch(Connection::FETCH_OBJ))) {

            $entityIdInstance = $this->newEntity($row, true);

            //~ Pre-fill collection to keep the order
            $collection[$entityIdInstance->getCacheKey()] = null;

            $entity = $this->getCache($entityIdInstance);
            if ($entity === null) {
                $this->cacheSkipMissingItemQuery = true;
                $values = $this->getDataPrimaryKeysValues($entityIdInstance);
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
     * @param  EntityInterface $data
     * @return null|EntityInterface
     * @throws \Psr\Cache\InvalidArgumentException
     */
    private function getCache(EntityInterface $data)
    {
        if (!$this->isCacheEnabledOnRead) {
            return null;
        }

        $cacheItem = $this->cache->getItem($data->getCacheKey());

        return $cacheItem->get();
    }

    /**
     * Delete cache
     *
     * @param  EntityInterface $data
     * @return $this
     * @throws \Psr\Cache\InvalidArgumentException
     */
    private function deleteCache(EntityInterface $data)
    {
        if (! $this->cache instanceof CacheItemPoolInterface) {
            return $this;
        }

        $this->cache->deleteItem($data->getCacheKey());

        return $this;
    }

    /**
     * Set data into cache if enabled.
     *
     * @param  EntityInterface $data
     * @return $this
     * @throws \Psr\Cache\InvalidArgumentException
     */
    private function setCache(EntityInterface $data)
    {
        if (! $this->cache instanceof CacheItemPoolInterface) {
            return $this;
        }

        $cacheItem = $this->cache->getItem($data->getCacheKey());

        $cacheItem->set($data);

        $this->cache->save($cacheItem);

        return $this;
    }
}
