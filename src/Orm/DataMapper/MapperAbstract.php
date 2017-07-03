<?php

/**
 * Copyright (c) 2010-2016 Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eureka\Component\Orm\DataMapper;

use Eureka\Component\Cache\CacheWrapperAbstract as Cache;
use Eureka\Component\Database\ExceptionNoData;
use Eureka\Component\Dependency\Container;

/**
 * DataMapper Mapper abstract class.
 *
 * @author  Romain Cottard
 */
abstract class MapperAbstract
{
    /**
     * @var string $dataClass Name of class use to instance DataMapper Data class.
     */
    protected $dataClass = '';

    /**
     * @var Cache $cache Cache instance. Not connected if cache is not used.
     */
    protected $cache = null;

    /**
     * @var \PDO $db Db sql instance
     */
    protected $db = null;

    /**
     * @var bool $isCacheEnabled If cache is enabled for Mapper class
     */
    protected $isCacheEnabled = false;

    /**
     * @var string $cacheConfig Name of config cache to use.
     */
    protected $cacheConfig = '';

    /**
     * @var string $table Table name.
     */
    protected $table = '';

    /**
     * @var array $fields List of fields
     */
    protected $fields = array();

    /**
     * @var array $primaryKeys List of primary keys fields
     */
    protected $primaryKeys = array();

    /**
     * @var array $keys List of keys fields
     */
    protected $keys = array();

    /**
     * @var array $primaryKeys List of primary keys
     */
    protected $dataNamesMap = array();

    /**
     * @var int $lastId Auto increment id of the last insert query.
     */
    protected $lastId = 0;

    /**
     * @var array $wheres List of where restriction for current query
     */
    protected $wheres = array();

    /**
     * @var array $sets List of sets clause for current query
     */
    protected $sets = array();

    /**
     * @var array $binds List of binding values
     */
    protected $binds = array();

    /**
     * @var array $having List of having restriction for current query
     */
    protected $havings = array();

    /**
     * @var array $order List of order by restriction for current query
     */
    protected $orders = array();

    /**
     * @var int $limit Max limit for current query.
     */
    protected $limit = null;

    /**
     * @var int $offset Start fetch result position for current query
     */
    protected $offset = null;

    /**
     * @var bool If true, does not throw an exception for not mapped fields (ie : COUNT()) in setDataValue
     */
    protected $ignoreNotMappedFields = false;

    /**
     * @var string $databaseConfig Database config name.
     */
    protected $databaseConfig = '';

    /**
     * MapperAbstract constructor.
     *
     * @param  \PDO $db
     */
    public function __construct(\PDO $db)
    {
        $this->db = $db;
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
     * @return array
     */
    public function getTable()
    {
        return $this->table;
    }

    /**
     * Create new instance of extended DataAbstract class & return it.
     *
     * @param  \stdClass $row
     * @param  bool      $exists
     * @return DataAbstract
     * @throws \LogicException
     */
    public function newDataInstance(\stdClass $row = null, $exists = false)
    {
        //~ Get dependency container & attach database object if necessary
        $container = Container::getInstance();
        $container->attach($this->getDatabaseConfig(), $this->db, Container::DATABASE);

        $data = new $this->dataClass($container);

        if (!($data instanceof DataAbstract)) {
            throw new \LogicException('Data object not instance of DataAbstract class!');
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
     * @param  array  $values List of values (integer)
     * @param  string $whereConcat Concat type with other where elements
     * @return self
     */
    public function addIn($field, $values, $whereConcat = 'AND')
    {
        if (!is_array($values)) {
            return $this;
        }

        $field = (0 < count($this->wheres) ? ' ' . $whereConcat . ' ' . $field : $field);

        //~ Bind values (more safety)
        $index  = 1;
        $fields = array();
        foreach ($values as $value) {

            $name = ':value_' . $index;

            $fields[]           = $name;
            $this->binds[$name] = (string) $value;

            $index++;
        }

        $this->wheres[] = $field . ' IN (' . implode(',', $fields) . ')';

        return $this;
    }

    /**
     * Add order clause.
     *
     * @param  string $order
     * @param  string $dir
     * @return self
     */
    public function addOrder($order, $dir = 'ASC')
    {
        $this->orders[] = $order . ' ' . $dir;

        return $this;
    }

    /**
     * Add where clause.
     *
     * @param  string         $field
     * @param  string|integer $value
     * @param  string         $sign
     * @param  string         $whereConcat
     * @return self
     */
    public function addWhere($field, $value, $sign = '=', $whereConcat = 'AND')
    {
        $fieldWhere = (0 < count($this->wheres) ? ' ' . $whereConcat . ' ' . $field : $field);
        $fieldBind  = ':' . strtolower($field);

        if (isset($this->binds[$fieldBind])) {
            $counter = 0;
            while ($counter < 20) {

                if (isset($this->binds[$fieldBind . '__' . $counter++])) {
                    continue;
                }

                $fieldBind .= '__' . $counter;
                break;
            };
        }

        $this->wheres[]          = $fieldWhere . ' ' . $sign . ' ' . $fieldBind;
        $this->binds[$fieldBind] = $value;

        return $this;
    }

    /**
     * Set limit & offset.
     *
     * @param  int $limit
     * @param  int $offset
     * @return self
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
     * @return self
     */
    public function bind(array $binds)
    {
        $this->binds = $binds;

        return $this;
    }

    /**
     * Clear query params
     *
     * @return self
     */
    public function clear()
    {
        $this->wheres  = array();
        $this->sets    = array();
        $this->havings = array();
        $this->orders  = array();
        $this->binds   = array();
        $this->limit   = null;
        $this->offset  = null;

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
     * Get fields to select
     *
     * @param  bool   $usePrefix
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
     * @param  DataAbstract $data
     * @param  bool         $forceCheck If force check (do not force for insert query)
     * @return string
     */
    public function getQueryFieldsSet(DataAbstract $data, $forceCheck = true)
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
     * @param  DataAbstract $data
     * @return string
     */
    public function getQueryFieldsOnDuplicateUpdate(DataAbstract $data)
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

        $statement = $this->db->prepare($query);
        $statement->execute($this->binds);

        $this->clear();

        return (int) $statement->fetchColumn(0);
    }

    /**
     * Check if value row exists in database..
     *
     * @param  string $field
     * @param  mixed  $value Value
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
     * @return array Array of model_base object for query.
     */
    public function query($query)
    {
        $statement = $this->db->prepare($query);
        $statement->execute($this->binds);

        $this->clear();

        $collection = array();

        while (false !== ($row = $statement->fetchObject())) {
            $collection[] = $this->newDataInstance($row, true);
        }

        return $collection;
    }

    /**
     * Fetch rows for specified query.
     *
     * @param  string $query
     * @return \stdClass[] Array of model_base object for query.
     */
    public function queryRows($query)
    {
        $statement = $this->db->prepare($query);
        $statement->execute($this->binds);

        $this->clear();

        $collection = array();

        while (false !== ($row = $statement->fetchObject())) {
            $collection[] = $row;
        }

        return $collection;
    }

    /**
     * Delete data from database.
     *
     * @param  DataAbstract $data
     * @return self
     * @throws \LogicException
     */
    public function delete(DataAbstract $data)
    {
        foreach ($this->primaryKeys as $key) {
            $this->addWhere($key, $this->getDataValue($data, $key));
        }

        $where = $this->getQueryWhere();

        if (empty($where)) {
            throw new \LogicException(__METHOD__ . '| Where restriction is empty for current DELETE query !');
        }

        $query     = 'DELETE FROM ' . $this->getTable() . ' ' . $this->getQueryWhere();
        $statement = $this->db->prepare($query);
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
     * Persist data in database.
     *
     * @param  DataAbstract $data
     * @return bool
     */
    public function persist(DataAbstract $data)
    {
        if ($data->exists()) {
            return $this->update($data);
        } else {
            return $this->insert($data);
        }
    }

    /**
     * Insert active row (or update row if it possible).
     *
     * @param  DataAbstract $data
     * @param  boolean      $forceUpdate If true, add on duplicate update clause to the insert query.
     * @return boolean State of insert
     * @throws \LogicException
     */
    public function insert(DataAbstract $data, $forceUpdate = false)
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

        if ($forceUpdate || $data->exists()) {
            $queryDuplicateUpdate = $this->getQueryFieldsOnDuplicateUpdate($data);

            if (empty($queryDuplicateUpdate)) {
                throw new \LogicException(__METHOD__ . '|ON DUPLICATE UPDATE clause cannot be empty !');
            }

            $queryDuplicateUpdate = ' ' . $queryDuplicateUpdate;
        }

        $query     = 'INSERT INTO ' . $this->getTable() . $querySet . $queryDuplicateUpdate;
        $statement = $this->db->prepare($query);
        $statement->execute($this->binds);

        //~ If has auto increment key (generaly, is a primary key), set value
        $lastInsertId = (int) $this->db->lastInsertId();
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
     * @param  $data
     * @return bool
     * @throws \LogicException
     */
    public function update(DataAbstract $data)
    {
        if (!$data->isUpdated()) {
            return false;
        }

        //~ Reset binded fields
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
        $statement = $this->db->prepare($query);
        $statement->execute($this->binds);

        //~ Reset some data
        $data->resetUpdated();

        //~ Clear
        $this->clear();
        $this->deleteCache($data);

        return true;
    }

    /**
     * Get first row corresponding of the primary keys.
     *
     * @param  integer $id
     * @return DataAbstract
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
     * @return DataAbstract
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
     * @return DataAbstract[] List of row.
     */
    public function select()
    {
        $query     = 'SELECT ' . $this->getQueryFields() . ' FROM ' . $this->getTable() . ' ' . $this->getQueryWhere() . ' ' . $this->getQueryOrderBy() . ' ' . $this->getQueryLimit();
        $statement = $this->db->prepare($query);
        $statement->execute($this->binds);

        $this->clear();

        $collection = array();

        while (false !== ($row = $statement->fetchObject())) {
            $collection[] = $this->newDataInstance($row, true);
        }

        return $collection;
    }

    /**
     * Select first rows corresponding to where clause.
     *
     * @return DataAbstract
     * @throws ExceptionNoData
     */
    public function selectOne()
    {
        $this->setLimit(1);

        $query = 'SELECT ' . $this->getQueryFields() . ' FROM ' . $this->getTable() . ' ' . $this->getQueryWhere() . ' ' . $this->getQueryOrderBy() . ' ' . $this->getQueryLimit();

        $statement = $this->db->prepare($query);
        $statement->execute($this->binds);

        $this->clear();

        if ($statement->rowCount() === 0) {
            throw new ExceptionNoData('No data for current query! (query: ' . $query . ', bind: ' . json_encode($this->binds) . ')', 10001);
        }

        //~ Create new object, set data & save it in cache
        return $this->newDataInstance($statement->fetchObject(), true);
    }

    /**
     * Delete cache
     *
     * @param  DataAbstract $data
     * @return self
     */
    protected function deleteCache(DataAbstract $data)
    {
        if ($this->isCacheEnabled) {
            $this->cache->remove($data->getCacheKey());
        }

        return $this;
    }

    /**
     * Get Data object from cache if is enabled.
     *
     * @param  DataAbstract $data
     * @return bool|DataAbstract
     */
    protected function getCache(DataAbstract $data)
    {
        if (!$this->isCacheEnabled) {
            return false;
        }

        return $this->cache->get($data->getCacheKey());
    }

    /**
     * Set data into cache if enabled.
     *
     * @param  DataAbstract $data
     * @return self
     */
    protected function setCache(DataAbstract $data)
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
     * @param  DataAbstract $data
     * @param  string       $field
     * @return bool
     * @throws \DomainException
     */
    protected function isDataUpdated($data, $field)
    {
        if (!isset($this->dataNamesMap[$field]['property'])) {
            throw new \DomainException('Field have not mapping with Data instance (field: ' . $field . ')');
        }

        $property = $this->dataNamesMap[$field]['property'];

        return $data->isUpdated($property);
    }

    /**
     * Get value from DataAbstract instance based on field value
     *
     * @param  DataAbstract $data
     * @param  string       $field
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
     * Set value into DataAbstract instance based on field value
     *
     * @param  DataAbstract $data
     * @param  string       $field
     * @param  mixed        $value
     * @return mixed
     * @throws \DomainException
     */
    protected function setDataValue($data, $field, $value)
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
     * @param  bool  $enableCache
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
     * Get insert query.
     *
     * @param  Image
     * @return string
     */
    public function getQueryInsert(DataAbstract $data)
    {
        //~ Reset binded fields
        $this->binds = array();

        $querySet = $this->getQueryFieldsSet($data, false);
        if (empty($querySet)) {
            throw new \LogicException(__METHOD__ . '|Set clause cannot be empty !');
        } else {
            $querySet = ' ' . $querySet;
        }

        foreach ($this->binds as $key => &$val) {
            if (null === $val) {
                $val = 'NULL';
            } else {
                $val = '"' . $val . '"';
            }
        }

        $query = 'INSERT INTO ' . $this->getTable() . $querySet;

        return (string) str_replace(array_keys($this->binds), array_values($this->binds), $query);
    }

    /**
     * Get database config name.
     *
     * @return string
     */
    protected function getDatabaseConfig()
    {
        return $this->databaseConfig;
    }
}
