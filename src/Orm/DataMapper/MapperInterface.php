<?php

/**
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eureka\Component\Orm\DataMapper;

/**
 * DataMapper Mapper abstract class.
 *
 * @author  Romain Cottard
 */
interface MapperInterface
{
    /**
     * Enable cache usage.
     *
     * @return static
     */
    public function enableCache();

    /**
     * Disable cache usage.
     *
     * @return static
     */
    public function disableCache();

    /**
     * Return fields for current table.
     *
     * @return array
     */
    public function getFields();

    /**
     * Return fields for current table.
     *
     * @return string
     */
    public function getTable();

    /**
     * Create new instance of extended DataInterface class & return it.
     *
     * @param  \stdClass $row
     * @param  bool      $exists
     * @return mixed
     * @throws \LogicException
     */
    public function newDataInstance(\stdClass $row = null, $exists = false);

    /**
     * Add In list item.
     *
     * @param  string $field Field name
     * @param  array  $values List of values (integer)
     * @param  string $whereConcat Concat type with other where elements
     * @param  bool   $not Whether the wondition should be NOT IN instead of IN
     * @return $this
     */
    public function addIn($field, $values, $whereConcat = 'AND', $not = false);

    /**
     * Add order clause.
     *
     * @param  string $order
     * @param  string $dir
     * @return $this
     */
    public function addOrder($order, $dir = 'ASC');

    /**
     * Add groupBy clause.
     *
     * @param  string $field
     * @return $this
     */
    public function addGroupBy($field);

    /**
     * Add having clause.
     *
     * @param  string         $field
     * @param  string|integer $value
     * @param  string         $sign
     * @param  string         $having_concat
     * @return $this
     */
    public function addHaving($field, $value, $sign = '=', $having_concat = 'AND');

    /**
     * Add where clause.
     *
     * @param  string         $field
     * @param  string|integer $value
     * @param  string         $sign
     * @param  string         $where_concat
     * @return $this
     */
    public function addWhere($field, $value, $sign = '=', $whereConcat = 'AND');

    /**
     * Add where clause.
     *
     * @param  string         $field
     * @param  string|integer $value
     * @param  string         $sign
     * @param  string         $where_concat
     * @return $this
     */
    public function addWhereRaw($field, $value, $fieldBind, $whereConcat = 'AND');

    /**
     * Set limit & offset.
     *
     * @param int $limit
     * @param int $offset
     * @return $this
     */
    public function setLimit($limit, $offset = null);

    /**
     * Set bind
     *
     * @param  array $binds Binded values
     * @return $this
     */
    public function bind(array $binds);

    /**
     * Clear query params
     *
     * @return $this
     */
    public function clear();

    /**
     * Return autoincrement id of the last insert query.
     *
     * @return int
     */
    public function getLastId();

    /**
     * Get fields to select
     *
     * @param  bool   $usePrefix
     * @param  string $prefix
     * @return string
     */
    public function getQueryFields($usePrefix = false, $prefix = '');

    /**
     * Build field list to update (only field with different value from db)
     *
     * @param  DataInterface $data
     * @param  bool         $forceCheck If force check (do not force for insert query)
     * @return string
     */
    public function getQueryFieldsSet(DataInterface $data, $forceCheck = true);

    /**
     * Build field list to update (only field with different value from db)
     *
     * @param  DataInterface $data
     * @return string
     */
    public function getQueryFieldsOnDuplicateUpdate(DataInterface $data);

    /**
     * Get limit clause.
     *
     * @return string
     */
    public function getQueryLimit();

    /**
     * Get OrderBy clause.
     *
     * @return string
     */
    public function getQueryOrderBy();

    /**
     * Get GroupBy clause.
     *
     * @return string
     */
    public function getQueryGroupBy();

    /**
     * Get Having clause.
     *
     * @return string
     */
    public function getQueryHaving();

    /**
     * Get Where clause.
     *
     * @return string
     */
    public function getQueryWhere();

    /**
     * Get the higher value for the primary key
     *
     * @return mixed
     * @throws LogicException
     */
    public function getMaxId();

    /**
     * Count number of results for query.
     *
     * @param string $field
     * @return integer
     * @throws \DomainException
     */
    public function count($field = '*');

    /**
     * Check if value row exists in database..
     *
     * @param  string $field
     * @param  mixed  $value Value
     * @return bool
     */
    public function rowExists($field, $value);

    /**
     * Fetch rows for specified query.
     *
     * @param  string $query
     * @return DataInterface[] Array of DataInterface object for query.
     */
    public function query($query);

    /**
     * Fetch rows for specified query.
     *
     * @param  string $query
     * @return \stdClass[] Array of stdClass object for query.
     */
    public function queryRows($query);

    /**
     * Delete data from database.
     *
     * @param  DataInterface $data
     * @return $this
     * @throws \LogicException
     */
    public function delete(DataInterface $data);

    /**
     * Persist data in database.
     *
     * @param  DataAbstract $data
     * @return bool
     */
    public function persist(DataAbstract $data);

    /**
     * Insert active row (or update row if it possible).
     *
     * @param  DataInterface $data
     * @param  boolean      $forceUpdate If true, add on duplicate update clause to the insert query.
     * @return boolean State of insert
     * @throws \LogicException
     */
    public function insert(DataInterface $data, $forceUpdate = false);

    /**
     * Update data into database
     *
     * @param  DataInterface $data
     * @return bool
     * @throws \LogicException
     */
    public function update(DataInterface $data);

    /**
     * Get first row corresponding of the primary keys.
     *
     * @param  integer $id
     * @return DataAbstract
     * @throws \LogicException
     */
    public function findById($id);

    /**
     * Get first row corresponding of the primary keys.
     *
     * @param  array $primaryKeys
     * @return DataAbstract
     * @throws \UnexpectedValueException
     */
    public function findByKeys($primaryKeys);

    /**
     * Select all rows corresponding of where clause.
     *
     * @return DataAbstract[] List of row.
     */
    public function select();

    /**
     * Select first rows corresponding to where clause.
     *
     * @return DataAbstract
     * @throws ExceptionNoData
     */
    public function selectOne();

    /**
     * Set value for ignoreNotMappedFields
     *
     * @param bool $value
     * @return $this
     */
    public function setIgnoreNotMappedFields($value);

    /**
     * Apply the callback Function to each row, as a Data instance.
     * Where condition can be add before calling this method and will be applied to filter the data.
     *
     * @param  callable $callback Function to apply to each row. Must take a Data instance as unique parameter.
     * @param  string   $key Primary key to iterate on.
     * @param  int      $start First index; default 0.
     * @param  int      $end Last index, -1 picks the max; default -1.
     * @return void
     * @throws UnexpectedValueException
     */
    public function apply(callable $callback, $key, $start = 0, $end = -1, $batchSize = 10000);

    /**
     * Return a map of names (set, get and property) for a db field
     *
     * @param  string $field
     * @return array
     * @throws OutOfRangeException
     */
    public function getNamesMap($field);

    /**
     * Return the primary keys
     *
     * @return string
     */
    public function getPrimaryKeys();
}
