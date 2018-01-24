<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eureka\Component\Orm\DataMapper;

use Eureka\Component\Database\DatabaseException;
use Eureka\Component\Database\Connection;
use Eureka\Component\Orm\Exception\EntityNotExistsException;
use Eureka\Component\Orm\Exception\InsertFailedException;
use Eureka\Component\Orm\Exception\InvalidQueryException;
use Eureka\Component\Orm\Exception\OrmException;
use Psr\Cache\CacheItemPoolInterface;

/**
 * DataMapper Mapper abstract class.
 *
 * @author Romain Cottard
 */
abstract class AbstractMapper
{
    /** @var string $dataClass Name of class use to instance DataMapper Data class. */
    protected $dataClass = '';

    /** @var CacheItemPoolInterface $cache Cache instance. Not connected if cache is not used. */
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

    /** @var AbstractData $data Data instance. */
    protected $data = null;

    /** @var string[] $wheres List of where restriction for current query */
    protected $wheres = [];

    /** @var string[] $sets List of sets clause for current query */
    protected $sets = [];

    /** @var array $binds List of binding values */
    protected $binds = [];

    /** @var string[] $groupBy List of groupBy for current query */
    protected $groupBy = [];

    /** @var string[] $having List of having restriction for current query */
    protected $having = [];

    /** @var string[] $order List of order by restriction for current query */
    protected $orders = [];

    /** @var int $limit Max limit for current query. */
    protected $limit = null;

    /** @var int $offset Start fetch result position for current query */
    protected $offset = null;

    /** @var bool If true, does not throw an exception for not mapped fields (ie : COUNT()) in setDataValue */
    protected $ignoreNotMappedFields = false;

    /** @var int $totalNumberOfRows Total number of row for the last query (only available if db::hasCountRows() is true) */
    protected $totalNumberOfRows = 0;

    /** @var bool $cacheSkipMissingItemQuery If skip query after select from cache (when has no missing item) */
    protected $cacheSkipMissingItemQuery = false;

    /** @var string|null Precise if list returned by query() or select() is indexed by the value of one of the columns */
    protected $listIndexedByField = null;

    /**
     * AbstractMapper constructor.
     *
     * @param  Connection $connection
     * @param  CacheItemPoolInterface $cache
     * @param  bool $enableCacheOnRead
     */
    public function __construct(Connection $connection, CacheItemPoolInterface $cache = null, $enableCacheOnRead = false)
    {
        $this->connection = $connection;
        $this->cache = $cache;

        if ($enableCacheOnRead) {
            $this->enableCacheOnRead();
        }
    }

    /**
     * Enable cache usage on read (always active for writing when $cache is defined).
     *
     * @return $this
     */
    public function enableCacheOnRead()
    {
        $this->isCacheEnabledOnRead = true;

        return $this;
    }

    /**
     * Disable cache usage on read (always active for writing when $cache is defined).
     *
     * @return $this
     */
    public function disableCacheOnRead()
    {
        $this->isCacheEnabledOnRead = false;

        return $this;
    }

    /**
     * Return fields for current table.
     *
     * @return string[]
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
     * Create new instance of extended AbstractData class & return it.
     *
     * @param  \stdClass $row
     * @param  bool $exists
     * @return AbstractData
     * @throws \LogicException
     */
    public function newDataInstance(\stdClass $row = null, $exists = false)
    {
        $data = new $this->dataClass($this->connection);

        if (!($data instanceof AbstractData)) {
            throw new \LogicException('Data object not instance of AbstractData class!');
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
     * @param  bool $not Whether the condition should be NOT IN instead of IN
     * @return $this
     * @throws InvalidQueryException
     */
    public function addIn($field, $values, $whereConcat = 'AND', $not = false)
    {
        if (!is_array($values) || count($values) === 0) {
            throw new InvalidQueryException('Values for addIn must be an array, and non empty!');
        }

        $field = (0 < count($this->wheres) ? ' ' . $whereConcat . ' ' . $field : $field);

        //~ Bind values (more safety)
        $fields = [];
        foreach ($values as $value) {
            $name = ':value_' . uniqid();

            $fields[]           = $name;
            $this->binds[$name] = (string) $value;
        }

        $this->wheres[] = $field . ($not ? ' NOT' : '') . ' IN (' . implode(',', $fields) . ')';

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
     * Add where clause.
     *
     * @param  string[] $keys
     * @param  string $sign
     * @param  string $whereConcat
     * @return $this
     */
    public function addWhereKeysOr($keys, $sign = '=', $whereConcat = 'OR')
    {
        $suffix = uniqid();
        $wheres = [];

        foreach ($keys as $key => $value) {
            $field     = strtolower($key);
            $fieldBind = ':' . $field . '_' . $suffix;

            $this->binds[$fieldBind] = $value;

            $wheres[] = $field . ' ' . $sign . ' ' . $fieldBind;
        }

        $fieldWhere = ' (' . implode(' AND ', $wheres) . ') ';
        $fieldWhere = (0 < count($this->wheres) ? ' ' . $whereConcat . ' ' . $fieldWhere : $fieldWhere);

        $this->wheres[] = $fieldWhere;

        return $this;
    }

    /**
     * Set limit & offset.
     *
     * @param  int $limit
     * @param  int $offset
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
        $this->wheres  = [];
        $this->sets    = [];
        $this->groupBy = [];
        $this->having  = [];
        $this->orders  = [];
        $this->binds   = [];
        $this->limit   = null;
        $this->offset  = null;
        $this->data    = null;
        $this->listIndexedByField = null;

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
     * @param  AbstractData $data
     * @return string
     */
    public function getQueryInsert(AbstractData $data)
    {
        //~ List of fields to update.
        $queryFields = array();

        //~ Check for updated fields.
        foreach ($this->fields as $field) {

            #~ Skip auto increment keys
            try {
                $map = $this->getNamesMap($field);

                if ($data->hasAutoIncrement() && $map['property'] === 'id') {
                    continue;
                }
            } catch (\OutOfRangeException $exception) {
                continue;
            }

            $queryFields[] = '`' . $field . '` = ' . $this->connection->quote($this->getDataValue($data, $field));
        }

        if (empty($queryFields)) {
            throw new \LogicException(__METHOD__ . '|Set clause cannot be empty !');
        }

        $querySet = ' SET ' . implode(', ', $queryFields);

        return $query = 'INSERT INTO ' . $this->getTable() . $querySet;
    }

    /**
     *
     * @param  AbstractData $data
     * @return string
     */
    public function getQueryUpdate(AbstractData $data)
    {
        //~ List of fields to update.
        $queryFields = array();
        $primaryKeys = $this->getPrimaryKeys();

        //~ Check for updated fields.
        foreach ($this->fields as $field) {
            if (in_array($field, $primaryKeys)) {
                continue;
            }
            $queryFields[] = '`' . $field . '` = ' . $this->connection->quote($this->getDataValue($data, $field));
        }

        if (empty($queryFields)) {
            throw new \LogicException(__METHOD__ . '|Set clause cannot be empty !');
        }

        $querySet = ' SET ' . implode(', ', $queryFields);

        //~ Check for keys
        $queryFields = [];
        foreach ($primaryKeys as $key) {
            $queryFields[] = '`' . $key . '` = ' . $this->connection->quote($this->getDataValue($data, $key));
        }

        if (empty($queryFields)) {
            throw new \LogicException('No primary(ies) key(s) defined for an update');
        }

        $queryWhere = ' WHERE ' . implode(' AND ', $queryFields);

        return $query = 'UPDATE ' . $this->getTable() . $querySet . $queryWhere;
    }

    /**
     * Get fields to select
     *
     * @param  bool $isPrefixed Add table prefix in list of field
     * @param  bool $onlyPrimaryKeys Get only primary key(s) field(s)
     * @return string
     */
    protected function getQueryFields($isPrefixed = false, $onlyPrimaryKeys = false)
    {
        $fields = $onlyPrimaryKeys ? $this->getPrimaryKeys() : $this->getFields();

        if ($isPrefixed) {
            $table          = $this->getTable();
            $fields         = [];
            $fieldsToPrefix = $onlyPrimaryKeys ? $this->getPrimaryKeys() : $this->getFields();

            foreach ($fieldsToPrefix as $field) {
                $fields[] = $table . '.' . $field;
            }
        }

        return implode(', ', $fields);
    }

    /**
     * Build field list to update (only field with different value from db)
     *
     * @param  AbstractData $data
     * @param  bool $forceCheck If force check (do not force for insert query)
     * @return string
     */
    public function getQueryFieldsSet(AbstractData $data, $forceCheck = true)
    {
        //~ List of fields to update.
        $queryFields = [];

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
     * @param  AbstractData $data
     * @return string
     */
    public function getQueryFieldsOnDuplicateUpdate(AbstractData $data)
    {
        if (!$data->isUpdated()) {
            return '';
        }

        //~ List of fields to update.
        $queryFields = [];

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
     * @throws \LogicException
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
     * Get total number of rows from the last query.
     *
     * @return int
     */
    public function getTotalNumberOfRows()
    {
        return $this->totalNumberOfRows;
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

        $query = 'SELECT COUNT(' . $field . ') AS NB_RESULTS FROM ' . $this->getTable() . ' ' . $this->getQueryWhere();

        $statement = $this->connection->prepare($query);
        $statement->execute($this->binds);

        $this->clear();

        return (int) $statement->fetchColumn(0);
    }

    /**
     * Check if value row exists in database..
     *
     * @param  array $fields
     * @return bool
     * @throws \Psr\Cache\InvalidArgumentException
     * @throws \Exception
     */
    public function rowExists($fields)
    {
        foreach ($fields as $field => $value) {
            $this->addWhere($field, $value);
        }

        try {
            $this->selectOne();

            return true;
        } catch (EntityNotExistsException $exception) {
            return false;
        }
    }

    /**
     * Fetch rows for specified query.
     * /!\ Smart Cache cannot used here !
     *
     * @param  string $query
     * @return AbstractData[] Array of model base object for query.
     * @throws OrmException
     * @throws \Exception
     */
    public function query($query)
    {
        $indexedBy = $this->getListIndexedByField();
        $statement = $this->connection->prepare($query);
        $statement->execute($this->binds);

        $collection = [];

        $this->clear();

        $id = 0;
        while (false !== ($row = $statement->fetch(Connection::FETCH_OBJ))) {
			if ($indexedBy !== null && !isset($list->data[0]->$indexedBy)) {
                throw new OrmException('List is supposed to be indexed by a column that does not exist: '.$indexedBy);
            }

            $index = $indexedBy !== null ? $row->$indexedBy : $id++;
            $collection[$index] = $this->newDataInstance($row, true);
        }

        return $collection;
    }

    /**
     * Fetch rows for specified query.
     * Return "raw" result set.
     * /!\ Smart Cache cannot used here !
     *
     * @param  string $query
     * @return \stdClass[] List of row from db query.
     * @throws \Exception
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
     * @param  AbstractData $data
     * @return bool
     * @throws \LogicException
     * @throws \Psr\Cache\InvalidArgumentException
     * @throws \Exception
     */
    public function delete(AbstractData $data)
    {
        foreach ($this->primaryKeys as $key) {
            $this->addWhere($key, $this->getDataValue($data, $key));
        }

        $where = $this->getQueryWhere();

        if (empty($where)) {
            throw new \LogicException(__METHOD__ . '| Where restriction is empty for current DELETE query !');
        }

        $query = 'DELETE FROM ' . $this->getTable() . ' ' . $this->getQueryWhere();

        $statement = $this->connection->prepare($query);
        $result    = $statement->execute($this->binds);

        //~ Reset some data
        $data->setExists(false);
        $data->resetUpdated();

        //~ Clear
        $this->clear();
        $this->deleteCache($data);

        return (bool) $result;
    }

    /**
     * Insert active row (or update row if it possible).
     *
     * @param  AbstractData $data
     * @param  bool $forceUpdate If true, add on duplicate update clause to the insert query.
     * @param  bool $forceIgnore If true, add IGNORE on insert query to avoid SQL errors if duplicate
     * @return bool State of insert
     * @throws InsertFailedException
     * @throws \LogicException
     * @throws \Psr\Cache\InvalidArgumentException
     * @throws \Exception
     */
    public function insert(AbstractData $data, $forceUpdate = false, $forceIgnore = false)
    {
        if ($data->exists() && !$data->isUpdated()) {
            return false;
        }

        //~ Reset binded fields
        $this->binds = [];

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

		if ($forceIgnore && $statement->rowCount() === 0) {
            throw new InsertFailedException(__METHOD__ . 'INSERT IGNORE could not insert (duplicate key or error)');
        }

        //~ If has auto increment key (commonly, is a primary key), set value
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
     * @param  AbstractData $data
     * @return bool
     * @throws \LogicException
     * @throws \Psr\Cache\InvalidArgumentException
     * @throws \Exception
     */
    public function update(AbstractData $data)
    {
        if (!$data->isUpdated()) {
            return false;
        }

        //~ Reset bound fields
        $this->binds = [];

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
        $result    = $statement->execute($this->binds);

        //~ Reset some data
        $data->resetUpdated();

        //~ Clear
        $this->clear();
        $this->deleteCache($data);

        return $result;
    }

    /**
     * Either insert or update an entity
     *
     * @param AbstractData|DataInterface $data
     * @param  bool $forceIgnore If true, add IGNORE to the insert query.
     * @return bool
     * @throws \Psr\Cache\InvalidArgumentException
     * @throws \Exception
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
     * @return AbstractData
     * @throws \LogicException
     * @throws \Psr\Cache\InvalidArgumentException
     * @throws \Exception
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
     * Get first row corresponding of the keys.
     *
     * @param  string[] $keys
     * @return AbstractData
     * @throws \Psr\Cache\InvalidArgumentException
     * @throws \Exception
     */
    public function findByKeys(array $keys)
    {
        foreach ($keys as $field => $value) {
            $this->addWhere($field, $value);
        }

        return $this->selectOne();
    }

    /**
     * Get rows corresponding of the keys.
     *
     * @param  string[] $keys
     * @return AbstractData[] List of row
     * @throws \Psr\Cache\InvalidArgumentException
     * @throws \Exception
     */
    public function findAllByKeys(array $keys)
    {
        foreach ($keys as $field => $value) {
            $this->addWhere($field, $value);
        }

        return $this->select();
    }

    /**
     * Select first rows corresponding to where clause.
     *
     * @return AbstractData
     * @throws EntityNotExistsException
     * @throws \Psr\Cache\InvalidArgumentException
     * @throws \Exception
     */
    public function selectOne()
    {
        $this->setLimit(1);

        $collection = $this->select();

        if (empty($collection)) {
            throw new EntityNotExistsException('No data for current selection', 0);
        }

        return current($collection);
    }

    /**
     * Select all rows corresponding of where clause.
     *
     * @return AbstractData[] List of row.
     * @throws \Psr\Cache\InvalidArgumentException
     * @throws \Exception
     */
    public function select()
    {
        $collection = [];

        if ($this->isCacheEnabledOnRead) {
            $collection = $this->selectFromCache();
        }

        $calcFoundRows = (!$this->isCacheEnabledOnRead && $this->connection->hasCountRows()) ? 'SQL_CALC_FOUND_ROWS ' : '';

        if ($this->cacheSkipMissingItemQuery) {
            $this->cacheSkipMissingItemQuery = false;
            $this->clear();

            return $collection;
        }

        $query = 'SELECT ' . $calcFoundRows . $this->getQueryFields() . ' FROM ' . $this->getTable() . ' ' . $this->getQueryWhere() . ' ' . $this->getQueryGroupBy() . ' ' . $this->getQueryHaving() . ' ' . $this->getQueryOrderBy() . ' ' . $this->getQueryLimit();

        $list = $this->queryRaw($query);

        //~ Save total number of row from query
        $this->totalNumberOfRows = $list->total;

        foreach ($list->data as $row) {
            $data = $this->newDataInstance($row, true);

            $collection[$data->getCacheKey()] = $data;

            $this->setCache($data);
        }

        return $collection;
    }

    /**
     * Try to get all entities from cache.
     * Return list of entities (for found) / null (for not found in cache)
     *
     * @return AbstractData[]
     * @throws \Psr\Cache\InvalidArgumentException
     * @throws \Exception
     */
    public function selectFromCache()
    {
        if (!$this->isCacheEnabledOnRead) {
            return [];
        }

        $primaryKeys = $this->getPrimaryKeys();

        if (count($primaryKeys) === 0) {
            return [];
        }

        $calcFoundRows = $this->connection->hasCountRows() ? 'SQL_CALC_FOUND_ROWS ' : '';

        $query = 'SELECT ' . $calcFoundRows . $this->getQueryFields(false, true) . ' FROM ' . $this->getTable() . ' ' . $this->getQueryWhere() . ' ' . $this->getQueryGroupBy() . ' ' . $this->getQueryHaving() . ' ' . $this->getQueryOrderBy() . ' ' . $this->getQueryLimit();

        try {
            $this->connection->setValues($this->binds);
            $list = $this->connection->query(Clean::query($query));
        } catch (\Exception $exception) {
            $this->clear();
            throw $exception;
        }

        //~ Save total number of row from query
        $this->totalNumberOfRows = $list->total;

        //~ Force reset
        $this->wheres  = [];
        $this->binds   = [];
        $this->having  = [];
        $this->groupBy = [];

        $collection = [];
        $addIn      = [];
        $values     = [];

        $hasOnePrimaryKey = count($primaryKeys) === 1;

        foreach ($list->data as $row) {

            $dataIdInstance = $this->newDataInstance($row, true);

            //~ Pre-fill collection to keep the order
            $collection[$dataIdInstance->getCacheKey()] = null;

            $data = $this->getCache($dataIdInstance);
            if ($data === null) {
                $values = $this->getDataPrimaryKeysValues($dataIdInstance);
                if ($hasOnePrimaryKey) {
                    $addIn[] = current($values);
                } else {
                    $this->addWhereKeysOr($values);
                }
            } else {
                $collection[$dataIdInstance->getCacheKey()] = $data;
            }
        }

        if (!empty($addIn) && !empty($values)) {
            $this->addIn(key($values), $addIn);
        }

        //~ When retrieve all data from cache, skip missing cache item query.
        if (count(array_filter($collection)) === count($list->data)) {
            $this->cacheSkipMissingItemQuery = true;
        }

        return $collection;
    }

    /**
     * Get Data object from cache if is enabled.
     *
     * @param  AbstractData $data
     * @return null|AbstractData
     * @throws \Psr\Cache\InvalidArgumentException
     */
    private function getCache(AbstractData $data)
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
     * @param  AbstractData $data
     * @return $this
     * @throws \Psr\Cache\InvalidArgumentException
     */
    protected function deleteCache(AbstractData $data)
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
     * @param  AbstractData $data
     * @return $this
     * @throws \Psr\Cache\InvalidArgumentException
     */
    private function setCache(AbstractData $data)
    {
        if (! $this->cache instanceof CacheItemPoolInterface) {
            return $this;
        }

        $cacheItem = $this->cache->getItem($data->getCacheKey());

        $cacheItem->set($data);

        $this->cache->save($cacheItem);

        return $this;
    }

    /**
     * Check if data value is updated or not
     *
     * @param  AbstractData $data
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
     * Get value from AbstractData instance based on field value
     *
     * @param  AbstractData $data
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
     * Get array "key" => "value" for primaries keys.
     *
     * @param  AbstractData $data
     * @return array
     */
    private function getDataPrimaryKeysValues(AbstractData $data)
    {
        $values = [];

        foreach ($this->getPrimaryKeys() as $key) {
            $getter       = $this->dataNamesMap[$key]['get'];
            $values[$key] = $data->{$getter}();
        }

        return $values;
    }

    /**
     * Set value into AbstractData instance based on field value
     *
     * @param  AbstractData $data
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
     * Apply the callback Function to each row, as a Data instance.
     * Where condition can be add before calling this method and will be applied to filter the data.
     *
     * @param  callable $callback Function to apply to each row. Must take a Data instance as unique parameter.
     * @param  string $key Primary key to iterate on.
     * @param  int $start First index; default 0.
     * @param  int $end Last index, -1 picks the max; default -1.
     * @param  int $batchSize
     * @return void
     * @throws \UnexpectedValueException
     * @throws \Eureka\Component\Orm\Exception\OrmException
     * @throws \Exception
     */
    public function apply(callable $callback, $key, $start = 0, $end = -1, $batchSize = 10000)
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
     * @return string[]
     * @throws \OutOfRangeException
     */
    public function getNamesMap($field)
    {
        if (!isset($this->dataNamesMap[$field])) {
            throw new \OutOfRangeException('Specified field does not exist in data names map');
        }

        return $this->dataNamesMap[$field];
    }

    /**
     * Return the primary keys
     *
     * @return string[]
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

    /**
     * @return null|string
     */
    public function getListIndexedByField()
    {
        return $this->listIndexedByField;
    }

    /**
     * @param null|string $listIndexedByField
     * @return $this
     */
    public function setListIndexedByField($listIndexedByField)
    {
        $this->listIndexedByField = $listIndexedByField;

        return $this;
    }
}
