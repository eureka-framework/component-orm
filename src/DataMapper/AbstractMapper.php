<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eureka\Component\Orm\DataMapper;

use Eureka\Component\Cache\CacheWrapperAbstract as Cache;
use Eureka\Component\Database\ExceptionNoData;
use Eureka\Component\Database\Connection;
use Eureka\Component\Orm\Exception;

/**
 * DataMapper Mapper abstract class.
 *
 * @author  Romain Cottard
 */
abstract class AbstractMapper
{
    /** @var string $dataClass Name of class use to instance DataMapper Data class. */
    protected $dataClass = '';

    /** @var CacheInterface $cache Cache instance. Not connected if cache is not used. */
    protected $cache = null;

    /** @var Connection $connection Connection instance */
    protected $connection = null;

    /** @var bool $isCacheEnabled If cache is enabled for Mapper class */
    protected $isCacheEnabled = false;

    /** @var string $cacheName Name of config cache to use. */
    protected $cacheName = '';

    /** @var string $table Table name. */
    protected $table = '';

    /** @var array $fields List of fields */
    protected $fields = array();

    /** @var array $primaryKeys List of primary keys fields */
    protected $primaryKeys = array();

    /** @var array $keys List of keys fields */
    protected $keys = array();

    /** @var array $primaryKeys List of primary keys */
    protected $dataNamesMap = array();

    /** @var int $lastId Auto increment id of the last insert query. */
    protected $lastId = 0;

    /** @var DataInterface $data Data instance. */
    protected $data = null;

    /** @var array $wheres List of where restriction for current query */
    protected $wheres = array();

    /** @var array $sets List of sets clause for current query */
    protected $sets = array();

    /** @var array $binds List of binding values */
    protected $binds = array();

    /** @var array $groupBy List of groupBy for current query */
    protected $groupBy = array();

    /** @var array $having List of having restriction for current query */
    protected $having = array();

    /** @var array $order List of order by restriction for current query */
    protected $orders = array();

    /** @var int $limit Max limit for current query. */
    protected $limit = null;

    /** @var int $offset Start fetch result position for current query */
    protected $offset = null;

    /** @var bool If true, does not throw an exception for not mapped fields (ie : COUNT()) in setDataValue */
    protected $ignoreNotMappedFields = false;

    /**
     * AbstractMapper constructor.
     *
     * @param  Connection $connection
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Enable cache usage.
     *
     * @return static
     */
    public function enableCache()
    {
        $this->isCacheEnabled = true;

        return $this;
    }

    /**
     * Disable cache usage.
     *
     * @return static
     */
    public function disableCache()
    {
        $this->isCacheEnabled = false;

        return $this;
    }

    /**
     * Set cache instance & enable cache if it is specified.
     *
     * @param  CacheInterface $cache
     * @param  bool $enableCache
     * @return static
     */
    public function setCacheInstance(CacheInterface $cache, $enableCache = false)
    {
        if ($enableCache) {
            $this->isCacheEnabled = true;
        }

        $this->cache = $cache;

        return $this;
    }

    /**
     * Return fields for current table.
     *
     * @return array
     */
    public function getFields()
    {
        return $this->fields;
    }

    /**
     * Return fields for current table.
     *
     * @return string
     */
    public function getTable()
    {
        return $this->table;
    }

    /**
     * Create new instance of extended DataInterface class & return it.
     *
     * @param  \stdClass $row
     * @param  bool $exists
     * @return mixed
     * @throws \LogicException
     */
    public function newDataInstance(\stdClass $row = null, $exists = false)
    {
        $data = new $this->dataClass($this->connection);

        if (!($data instanceof DataInterface)) {
            throw new \LogicException('Data object not instance of DataInterface class!');
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
     * Add In list item.
     *
     * @param  string $field Field name
     * @param  array $values List of values (integer)
     * @param  string $whereConcat Concat type with other where elements
     * @param  bool $not Whether the wondition should be NOT IN instead of IN
     * @return $this
     */
    public function addIn($field, $values, $whereConcat = 'AND', $not = false)
    {
        if (!is_array($values) || empty($values)) {
            throw new Exception\EmptyInValuesException();
        }

        $field = (0 < count($this->wheres) ? ' ' . $whereConcat . ' ' . $field : $field);

        //~ Bind values (more safety)
        $index  = 1;
        $fields = array();
        $prefix = ':' . $field . '_';
        foreach ($values as $value) {
            $name = $prefix . $index;

            $fields[]           = $name;
            $this->binds[$name] = (string) $value;

            $index++;
        }

        $this->wheres[] = '`' . $field . '`' . ($not ? ' NOT' : '') . ' IN (' . implode(',', $fields) . ')';

        return $this;
    }

    /**
     * Add order clause.
     *
     * @param  string $order
     * @param  string $dir
     * @return $this
     */
    public function addOrder($order, $dir = 'ASC')
    {
        $this->orders[] = $order . ' ' . $dir;

        return $this;
    }

    /**
     * Add groupBy clause.
     *
     * @param  string $field
     * @return $this
     */
    public function addGroupBy($field)
    {
        $this->groupBy[] = $field;

        return $this;
    }

    /**
     * Add having clause.
     *
     * @param  string $field
     * @param  string|int $value
     * @param  string $sign
     * @param  string $havingConcat
     * @return $this
     */
    public function addHaving($field, $value, $sign = '=', $havingConcat = 'AND')
    {
        $fieldHaving = (0 < count($this->having) ? ' ' . $havingConcat . ' ' . $field : $field);

        $this->having[]                        = $fieldHaving . ' ' . $sign . ' :' . strtolower($field);
        $this->binds[':' . strtolower($field)] = $value;

        return $this;
    }

    /**
     * Add where clause.
     *
     * @param  string $field
     * @param  string|int $value
     * @param  string $sign
     * @param  string $whereConcat
     * @return $this
     */
    public function addWhere($field, $value, $sign = '=', $whereConcat = 'AND')
    {
        $suffix     = uniqid();
        $fieldWhere = (0 < count($this->wheres) ? ' ' . $whereConcat . ' ' . $field : $field);

        $this->wheres[]                                  = $fieldWhere . ' ' . $sign . ' :' . strtolower($field) . $suffix;
        $this->binds[':' . strtolower($field) . $suffix] = $value;

        return $this;
    }

    /**
     * Set limit & offset.
     *
     * @param int $limit
     * @param int $offset
     * @return $this
     */
    public function setLimit($limit, $offset = null)
    {
        $this->limit  = (int) $limit;
        $this->offset = $offset;

        return $this;
    }

    /**
     * Set bind
     *
     * @param  array $binds Binded values
     * @return $this
     */
    public function bind(array $binds)
    {
        $this->binds = $binds;

        return $this;
    }

    /**
     * Clear query params
     *
     * @return $this
     */
    public function clear()
    {
        $this->wheres  = array();
        $this->sets    = array();
        $this->groupBy = array();
        $this->having  = array();
        $this->orders  = array();
        $this->binds   = array();
        $this->limit   = null;
        $this->offset  = null;
        $this->data    = null;

        return $this;
    }

    /**
     * Return autoincrement id of the last insert query.
     *
     * @return int
     */
    public function getLastId()
    {
        return $this->lastId;
    }

    /**
     *
     * @param  \Eureka\Component\Orm\DataMapper\DataInterface $data
     * @return string
     */
    public function getQueryInsert(DataInterface $data)
    {
        //~ List of fields to update.
        $queryFields = array();

        //~ Check for updated fields.
        foreach ($this->fields as $field) {
            $queryFields[] = $field . ' = ' . $this->connection->quote($this->getDataValue($data, $field));
        }

        $querySet = '';
        if (!empty($queryFields)) {
            $querySet = 'SET ' . implode(', ', $queryFields);
        }

        if (empty($querySet)) {
            throw new \LogicException(__METHOD__ . '|Set clause cannot be empty !');
        } else {
            $querySet = ' ' . $querySet;
        }

        return $query = 'INSERT INTO ' . $this->getTable() . $querySet;
    }

    /**
     * Get fields to select
     *
     * @param  bool $usePrefix
     * @param  string $prefix
     * @return string
     */
    public function getQueryFields($usePrefix = false, $prefix = '')
    {
        $fields = $this->fields;

        if ($usePrefix) {
            $prefix = (empty($prefix) ? $this->getTable() : $prefix);
            $fields = array();
            foreach ($this->fields as $field) {
                $fields[] = $prefix . '.' . $field;
            }
        }

        return implode(', ', $fields);
    }

    /**
     * Build field list to update (only field with different value from db)
     *
     * @param  DataInterface $data
     * @param  bool $forceCheck If force check (do not force for insert query)
     * @return string
     */
    public function getQueryFieldsSet(DataInterface $data, $forceCheck = true)
    {
        //~ List of fields to update.
        $queryFields = array();

        //~ Check for updated fields.
        foreach ($this->fields as $field) {

            //~ Skip fields that are not updated
            if ($forceCheck && !$this->isDataUpdated($data, $field)) {
                continue;
            }

            $queryFields[] = $field . ' = :' . strtolower($field);

            $this->binds[':' . strtolower($field)] = $this->getDataValue($data, $field);
        }

        $set = '';
        if (!empty($queryFields)) {
            $set = 'SET ' . implode(', ', $queryFields);
        }

        return $set;
    }

    /**
     * Build field list to update (only field with different value from db)
     *
     * @param  DataInterface $data
     * @return string
     */
    public function getQueryFieldsOnDuplicateUpdate(DataInterface $data)
    {
        if (!$data->isUpdated()) {
            return '';
        }

        //~ List of fields to update.
        $queryFields = array();

        //~ Check for updated fields.
        foreach ($this->fields as $field) {

            //~ Skip fields that are not updated
            if (!$this->isDataUpdated($data, $field)) {
                continue;
            }

            $queryFields[] = $field . ' = :' . strtolower($field);

            $this->binds[':' . strtolower($field)] = $this->getDataValue($data, $field);
        }

        $onDuplicateUpdate = '';
        if (!empty($queryFields)) {
            $onDuplicateUpdate = 'ON DUPLICATE KEY UPDATE ' . implode(', ', $queryFields);
        };

        return $onDuplicateUpdate;
    }

    /**
     * Get limit clause.
     *
     * @return string
     */
    public function getQueryLimit()
    {
        if ($this->limit !== null && $this->offset !== null) {
            return 'LIMIT ' . $this->offset . ', ' . $this->limit;
        } else {
            if (null !== $this->limit) {
                return 'LIMIT ' . $this->limit;
            } else {
                return '';
            }
        }
    }

    /**
     * Get OrderBy clause.
     *
     * @return string
     */
    public function getQueryOrderBy()
    {
        return (0 < count($this->orders) ? 'ORDER BY ' . implode(',', $this->orders) : '');
    }

    /**
     * Get GroupBy clause.
     *
     * @return string
     */
    public function getQueryGroupBy()
    {
        return (0 < count($this->groupBy) ? 'GROUP BY ' . implode(', ', $this->groupBy) : '');
    }

    /**
     * Get Having clause.
     *
     * @return string
     */
    public function getQueryHaving()
    {
        $return = '';

        if (0 < count($this->having)) {
            $return = 'HAVING ';
            foreach ($this->having as $having) {
                $return .= $having . ' ';
            }
        }

        return $return;
    }

    /**
     * Get Where clause.
     *
     * @return string
     */
    public function getQueryWhere()
    {
        $return = '';

        if (0 < count($this->wheres)) {
            $return = 'WHERE ';
            foreach ($this->wheres as $where) {
                $return .= $where . ' ';
            }
        }

        return $return;
    }

    /**
     * Get the higher value for the primary key
     *
     * @return mixed
     * @throws LogicException
     */
    public function getMaxId()
    {
        if (count($this->primaryKeys) > 1) {
            throw new LogicException(__METHOD__ . '|Cannot use getMaxId() method for table with multiple primary keys !');
        }

        $field = reset($this->primaryKeys);

        $statement = $this->connection->prepare('SELECT MAX(' . $field . ') AS ' . $field . ' FROM ' . $this->getTable());
        $statement->execute();

        return $statement->fetch(Connection::FETCH_OBJ)->{$field};
    }

    /**
     * Count number of results for query.
     *
     * @param string $field
     * @return integer
     * @throws \DomainException
     */
    public function count($field = '*')
    {
        if ($field !== '*' && !in_array($field, $this->getFields())) {
            throw new \DomainException(__METHOD__ . '|Field is not allowed ! (field: ' . $field . ')');
        }

        $query = 'SELECT COUNT(' . $field . ') AS NB_RESULTS FROM ' . $this->table . ' ' . $this->getQueryWhere();

        $statement = $this->connection->prepare($query);
        $statement->execute($this->binds);

        $this->clear();

        return (int) $statement->fetchColumn(0);
    }

    /**
     * Check if value row exists in database..
     *
     * @param  string $field
     * @param  mixed $value Value
     * @return bool
     */
    public function rowExists($field, $value)
    {
        $this->addWhere($field, $value);

        try {
            $this->selectOne();

            return true;
        } catch (ExceptionNoData $exception) {
            return false;
        }
    }

    /**
     * Fetch rows for specified query.
     *
     * @param  string $query
     * @return DataInterface[] Array of DataInterface object for query.
     */
    public function query($query)
    {
        $statement = $this->connection->prepare($query);
        $statement->execute($this->binds);

        $this->clear();

        $collection = array();

        while (false !== ($row = $statement->fetch(Connection::FETCH_OBJ))) {
            $collection[] = $this->newDataInstance($row, true);
        }

        return $collection;
    }

    /**
     * Fetch rows for specified query.
     *
     * @param  string $query
     * @return \stdClass[] Array of stdClass object for query.
     */
    public function queryRows($query)
    {
        $statement = $this->connection->prepare($query);
        $statement->execute($this->binds);

        $this->clear();

        $collection = array();

        while (false !== ($row = $statement->fetch(Connection::FETCH_OBJ))) {
            $collection[] = $row;
        }

        return $collection;
    }

    /**
     * Delete data from database.
     *
     * @param  DataInterface $data
     * @return $this
     * @throws \LogicException
     */
    public function delete(DataInterface $data)
    {
        foreach ($this->primaryKeys as $key) {
            $this->addWhere($key, $this->getDataValue($data, $key));
        }

        $where = $this->getQueryWhere();

        if (empty($where)) {
            throw new \LogicException(__METHOD__ . '| Where restriction is empty for current DELETE query !');
        }

        $query     = 'DELETE FROM ' . $this->getTable() . ' ' . $this->getQueryWhere();
        $statement = $this->connection->prepare($query);
        $statement->execute($this->binds);

        //~ Reset some data
        $data->setExists(false);
        $data->resetUpdated();

        //~ Clear
        $this->clear();
        $this->deleteCache($data);

        return $this;
    }

    /**
     * Insert active row (or update row if it possible).
     *
     * @param  DataInterface $data
     * @param  bool $forceUpdate If true, add on duplicate update clause to the insert query.
     * @param  bool $forceIgnore If true, add IGNORE on insert query to avoid SQL errors if duplicate
     * @return bool State of insert
     * @throws InsertFailedException
     * @throws \LogicException
     */
    public function insert(DataInterface $data, $forceUpdate = false, $forceIgnore = false)
    {
        if ($data->exists() && !$data->isUpdated()) {
            return false;
        }

        //~ Reset binded fields
        $this->binds = array();

        $querySet = $this->getQueryFieldsSet($data, false);
        if (empty($querySet)) {
            throw new \LogicException(__METHOD__ . '|Set clause cannot be empty !');
        } else {
            $querySet = ' ' . $querySet;
        }

        $queryDuplicateUpdate = '';

        $queryIgnore = $forceIgnore ? ' IGNORE ' : ' ';

        if ($forceUpdate || $data->exists()) {
            $queryDuplicateUpdate = $this->getQueryFieldsOnDuplicateUpdate($data);

            if (empty($queryDuplicateUpdate)) {
                throw new \LogicException(__METHOD__ . '|ON DUPLICATE UPDATE clause cannot be empty !');
            }

            $queryDuplicateUpdate = ' ' . $queryDuplicateUpdate;
        }

        $query     = 'INSERT INTO ' . $this->getTable() . $querySet . $queryDuplicateUpdate;
        $statement = $this->connection->prepare($query);
        $statement->execute($this->binds);

        /*
		if ($queryIgnore && $result->count === 0) {
            throw new InsertFailedException(__METHOD__ . 'INSERT IGNORE could not insert (duplicate key or error)');
        }
		*/

        //~ If has auto increment key (generaly, is a primary key), set value
        $lastInsertId = (int) $this->connection->lastInsertId();
        if ($lastInsertId > 0) {
            $this->lastId = $lastInsertId;

            $data->setAutoIncrementId($this->getLastId());
        }

        //~ Reset some data
        $data->setExists(true);
        $data->resetUpdated();

        //~ Clear
        $this->clear();
        $this->deleteCache($data);

        return true;
    }

    /**
     * Update data into database
     *
     * @param  DataInterface $data
     * @return bool
     * @throws \LogicException
     */
    public function update(DataInterface $data)
    {
        if (!$data->isUpdated()) {
            return false;
        }

        //~ Reset bound fields
        $this->binds = array();

        foreach ($this->primaryKeys as $key) {
            $this->addWhere($key, $this->getDataValue($data, $key));
        }

        $set = $this->getQueryFieldsSet($data);
        if (empty($set)) {
            return false;
        }

        $where = $this->getQueryWhere();
        if (empty($where)) {
            throw new \LogicException(__METHOD__ . '|Where clause is empty!');
        }

        $query     = 'UPDATE ' . $this->getTable() . ' ' . $set . ' ' . $where;
        $statement = $this->connection->prepare($query);
        $statement->execute($this->binds);

        //~ Reset some data
        $data->resetUpdated();

        //~ Clear
        $this->clear();
        $this->deleteCache($data);

        return true;
    }

    /**
     * Either insert or update an entity
     *
     * @param  DataInterface $data
     * @param  bool $forceIgnore If true, add IGNORE to the insert query.
     * @return bool
     */
    public function persist($data, $forceIgnore = false)
    {
        if ($data->exists()) {
            return $this->update($data);
        } else {
            return $this->insert($data, false, $forceIgnore);
        }
    }

    /**
     * Get first row corresponding of the primary keys.
     *
     * @param  integer $id
     * @return DataInterface
     * @throws \LogicException
     */
    public function findById($id)
    {
        if (count($this->primaryKeys) > 1) {
            throw new \LogicException(__METHOD__ . '|Cannot use findById() method for table with multiple primary keys !');
        }

        $field = reset($this->primaryKeys);

        return $this->findByKeys(array($field => $id));
    }

    /**
     * Get first row corresponding of the primary keys.
     *
     * @param  array $primaryKeys
     * @return DataInterface
     * @throws \UnexpectedValueException
     */
    public function findByKeys($primaryKeys)
    {
        $data = $this->newDataInstance(null, false);

        if (!is_array($primaryKeys)) {
            throw new \UnexpectedValueException(__METHOD__ . '|$primaryKeys must be an array !');
        }

        //~ Set values before cache
        foreach ($primaryKeys as $field => $value) {
            $this->setDataValue($data, $field, $value);
        }

        $data = $this->getCache($data);

        if ($data === false) {
            foreach ($primaryKeys as $field => $value) {
                $this->addWhere($field, $value);
            }

            $data = $this->selectOne();

            //~ Clear & set in cache
            $this->clear();
            $this->setCache($data);
        }

        return $data;
    }

    /**
     * Select all rows corresponding of where clause.
     *
     * @return DataInterface[] List of row.
     */
    public function select()
    {
        $query     = 'SELECT ' . $this->getQueryFields() . ' FROM ' . $this->getTable() . ' ' . $this->getQueryWhere() . ' ' . $this->getQueryOrderBy() . ' ' . $this->getQueryLimit();
        $statement = $this->connection->prepare($query);
        $statement->execute($this->binds);

        $this->clear();

        $collection = array();

        while (false !== ($row = $statement->fetch(Connection::FETCH_OBJ))) {
            $collection[] = $this->newDataInstance($row, true);
        }

        return $collection;
    }

    /**
     * Select all rows corresponding of where clause.
     *
     * @return array [List of row, total]
     */
    /*public function selectWithCount()
    {
        $calcFoundRows = $this->connection->hasCountRows() ? 'SQL_CALC_FOUND_ROWS ' : '';

        $query = 'SELECT ' . $calcFoundRows . $this->getQueryFields() . ' FROM ' . $this->table . ' ' . $this->getQueryWhere() . ' ' .
            $this->getQueryGroupBy() . ' ' . $this->getQueryHaving() . ' ' . $this->getQueryOrderBy() . ' ' .
            $this->getQueryLimit();

        $this->connection->setValues($this->binds);
        $list = $this->connection->query($query);

        $this->clear();

        $collection = array();

        foreach ($list->data as $row) {
            $collection[] = $this->newDataInstance($row, true);
        }

        return [$collection, $list->total];
    }*/

    /**
     * Select first rows corresponding to where clause.
     *
     * @return DataInterface
     * @throws ExceptionNoData
     */
    public function selectOne()
    {
        $this->setLimit(1);

        $query = 'SELECT ' . $this->getQueryFields() . ' FROM ' . $this->getTable() . ' ' . $this->getQueryWhere() . ' ' . $this->getQueryOrderBy() . ' ' . $this->getQueryLimit();

        $statement = $this->connection->prepare($query);
        $statement->execute($this->binds);

        $this->clear();

        if ($statement->rowCount() === 0) {
            throw new ExceptionNoData('No data for current query! (query: ' . $query . ', bind: ' . json_encode($this->binds) . ')', 10001);
        }

        //~ Create new object, set data & save it in cache
        return $this->newDataInstance($statement->fetch(Connection::FETCH_OBJ), true);
    }

    /**
     * Delete cache
     *
     * @param  DataInterface $data
     * @return $this
     */
    private function deleteCache(DataInterface $data)
    {
        if (!$this->isCacheEnabled) {
            return $this;
        }

        $this->cache->remove($data->getCacheKey());

        return $this;
    }

    /**
     * Get Data object from cache if is enabled.
     *
     * @param  DataInterface $data
     * @return bool|DataInterface
     */
    private function getCache(DataInterface $data)
    {
        if (!$this->isCacheEnabled) {
            return false;
        }

        return $this->cache->get($data->getCacheKey());
    }

    /**
     * Set data into cache if enabled.
     *
     * @param  DataInterface $data
     * @return $this
     */
    private function setCache(DataInterface $data)
    {
        if (!$this->isCacheEnabled) {
            return $this;
        }

        $this->cache->set($data->getCacheKey(), $data);

        return $this;
    }

    /**
     * Check if data value is updated or not
     *
     * @param  DataInterface $data
     * @param  string $field
     * @return bool
     * @throws \DomainException
     */
    private function isDataUpdated($data, $field)
    {
        if (!isset($this->dataNamesMap[$field]['property'])) {
            throw new \DomainException('Field have not mapping with Data instance (field: ' . $field . ')');
        }

        $property = $this->dataNamesMap[$field]['property'];

        return $data->isUpdated($property);
    }

    /**
     * Get value from DataInterface instance based on field value
     *
     * @param  DataInterface $data
     * @param  string $field
     * @return mixed
     * @throws \DomainException
     */
    protected function getDataValue($data, $field)
    {
        if (!isset($this->dataNamesMap[$field]['get'])) {
            throw new \DomainException('Field have not mapping with Data instance (field: ' . $field . ')');
        }

        $method = $this->dataNamesMap[$field]['get'];

        return $data->{$method}();
    }

    /**
     * Set value into DataInterface instance based on field value
     *
     * @param  DataInterface $data
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
     * Set value for ignoreNotMappedFields
     *
     * @param bool $value
     * @return $this
     */
    public function setIgnoreNotMappedFields($value)
    {
        $this->ignoreNotMappedFields = (bool) $value;

        return $this;
    }

    /**
     * Set cache instance & enable cache if it is specified.
     *
     * @param  Cache $cache
     * @param  bool $enableCache
     * @return self
     */
    public function initCache(Cache $cache, $enableCache = false)
    {
        $this->cache          = $cache;
        $this->isCacheEnabled = $enableCache;

        if ($enableCache) {
            $this->cache->enable();
        } else {
            $this->cache->disable();
        }

        return $this;
    }

    /**
     * Apply the callback Function to each row, as a Data instance.
     * Where condition can be add before calling this method and will be applied to filter the data.
     *
     * @param  callable $callback Function to apply to each row. Must take a Data instance as unique parameter.
     * @param  string $key Primary key to iterate on.
     * @param  int $start First index; default 0.
     * @param  int $end Last index, -1 picks the max; default -1.
     * @return void
     * @throws UnexpectedValueException
     */
    public function apply(callable $callback, $key, $start = 0, $end = -1, $batchSize = 10000)
    {
        if (!in_array($key, $this->primaryKeys)) {
            throw new UnexpectedValueException(__METHOD__ . ' | The key must be a primary key.');
        }

        $statement = $this->connection->prepare('SELECT MIN(' . $key . ') AS MIN, MAX(' . $key . ') AS MAX FROM ' . $this->getTable());
        $statement->execute();

        $bounds = $statement->fetch(Connection::FETCH_OBJ);

        $minIndex          = max($start, $bounds->MIN);
        $maxIndex          = $end < 0 ? $bounds->MAX : min($end, $bounds->MAX);
        $currentBatchIndex = $minIndex;

        $wheresCopy = [];
        if (!empty($this->wheres)) {
            $wheresCopy = $this->wheres; // Keep a copy of the current WHERE statements, to apply them to each batch.
        }

        while ($currentBatchIndex <= $maxIndex) {
            $this->wheres = $wheresCopy; // Apply initial WHERE statements.
            $this->addWhere($key, $currentBatchIndex, '>=')->addWhere($key, $currentBatchIndex + $batchSize, '<');

            $batch = $this->query('SELECT ' . $this->getQueryFields() . ' FROM ' . $this->getTable() . ' ' . $this->getQueryWhere());

            foreach ($batch as $item) {
                call_user_func($callback, $item);
            }

            $currentBatchIndex += $batchSize;
        }
    }

    /**
     * Return a map of names (set, get and property) for a db field
     *
     * @param  string $field
     * @return array
     * @throws OutOfRangeException
     */
    public function getNamesMap($field)
    {
        if (!isset($this->dataNamesMap[$field])) {
            throw new OutOfRangeException('Specified field does not exist in data names map');
        }

        return $this->dataNamesMap[$field];
    }

    /**
     * Return the primary keys
     *
     * @return string
     */
    public function getPrimaryKeys()
    {
        return $this->primaryKeys;
    }

    /**
     * Start new transaction.
     */
    public function beginTransaction()
    {
        $this->connection->beginTransaction();
    }

    /**
     * Commit transactions.
     *
     * @throws DatabaseException
     */
    public function commit()
    {
        $this->connection->commit();
    }

    /**
     * Rollback transactions.
     *
     * @throws DatabaseException
     */
    public function rollBack()
    {
        $this->connection->rollBack();
    }

    /**
     * Check if we are in transaction or not.
     *
     * @return bool
     */
    public function inTransaction()
    {
        return $this->connection->inTransaction();
    }
}
