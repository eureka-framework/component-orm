<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eureka\Component\Orm;

use Eureka\Component\Orm\Query\Factory;
use Eureka\Component\Orm\Query\QueryBuilder;
use Eureka\Component\Orm\Query\QueryBuilderInterface;
use Eureka\Component\Orm\Query\SelectBuilder;

/**
 * DataMapper Mapper abstract class.
 *
 * @author  Romain Cottard
 */
interface MapperInterface
{
    /**
     * @param  AbstractMapper[] $mappers
     * @return $this
     */
    public function addMappers($mappers);

    /**
     * Enable cache usage.
     *
     * @return static
     */
    public function enableCacheOnRead();

    /**
     * Disable cache usage.
     *
     * @return static
     */
    public function disableCacheOnRead();

    /**
     * Enable ignore mapped field when populate entity from request result.
     * Useful when have select join with fields from other(s) table(s).
     *
     * @return $this
     */
    public function enableIgnoreNotMappedFields();

    /**
     * Disable ignore mapped field when populate entity from request result.
     * It is the normal (strict) mode.
     *
     * @return $this
     */
    public function disableIgnoreNotMappedFields();

    /**
     * Return fields for current table.
     *
     * @return array
     */
    public function getFields();

    /**
     * Return the primary keys
     *
     * @return string[]
     */
    public function getPrimaryKeys();

    /**
     * Return a map of names (set, get and property) for a db field
     *
     * @param  string $field
     * @return array
     * @throws \OutOfRangeException
     */
    public function getNamesMap($field);

    /**
     * Return fields for current table.
     *
     * @return string
     */
    public function getTable();

    /**
     *  Returns the number of rows affected by the last SQL statement
     *
     * @return int
     */
    public function rowCount();

    /**
     * Get value from EntityInterface instance based on field value
     *
     * @param  EntityInterface $data
     * @param  string $field
     * @return mixed
     * @throws \DomainException
     */
    public function getDataValue($data, $field);

    /**
     * Return autoincrement id of the last insert query.
     *
     * @return int
     */
    public function getLastId();

    /**
     * Get the higher value for the primary key
     *
     * @return mixed
     * @throws \LogicException
     */
    public function getMaxId();

    /**
     * Count number of results for query.
     *
     * @param  QueryBuilder $queryBuilder
     * @param  string $field
     * @return int
     * @throws \DomainException
     */
    public function count(QueryBuilder $queryBuilder, $field = '*');

    /**
     * Check if value row exists in database..
     *
     * @param  SelectBuilder $queryBuilder
     * @return bool
     */
    public function rowExists(SelectBuilder $queryBuilder);

    /**
     * Fetch rows for specified query.
     *
     * @param  \Eureka\Component\Orm\Query\QueryBuilderInterface $queryBuilder
     * @return EntityInterface[] Array of EntityInterface object for query.
     */
    public function query(QueryBuilderInterface $queryBuilder);

    /**
     * Fetch rows for specified query.
     *
     * @param  QueryBuilderInterface $queryBuilder
     * @return \stdClass[] Array of stdClass object for query.
     */
    public function queryRows(QueryBuilderInterface $queryBuilder);

    /**
     * Select all rows corresponding of where clause.
     *
     * @param  SelectBuilder $queryBuilder
     * @return EntityInterface[] List of row.
     */
    public function select(SelectBuilder $queryBuilder);

    /**
     * Select first rows corresponding to where clause.
     *
     * @param  SelectBuilder $queryBuilder
     * @return EntityInterface
     * @throws Exception\EntityNotExistsException
     */
    public function selectOne(SelectBuilder $queryBuilder);

    /**
     * Apply the callback Function to each row, as a Data instance.
     * Where condition can be add before calling this method and will be applied to filter the data.
     *
     * @param  callable $callback Function to apply to each row. Must take a Data instance as unique parameter.
     * @param  SelectBuilder $queryBuilder
     * @param  string $key Primary key to iterate on.
     * @param  int $start First index; default 0.
     * @param  int $end Last index, -1 picks the max; default -1.
     * @param  int $batchSize Size for each iteration.
     * @return void
     * @throws \UnexpectedValueException
     */
    public function apply(callable $callback, SelectBuilder $queryBuilder, $key, $start = 0, $end = -1, $batchSize = 10000);

    /**
     * @param  string $type
     * @param  \Eureka\Component\Orm\EntityInterface|null $entity
     * @return \Eureka\Component\Orm\Query\DeleteBuilder|\Eureka\Component\Orm\Query\InsertBuilder|\Eureka\Component\Orm\Query\QueryBuilder|\Eureka\Component\Orm\Query\SelectBuilder|\Eureka\Component\Orm\Query\UpdateBuilder
     * @throws \Eureka\Component\Orm\Exception\OrmException
     */
    public function getQueryBuilder($type = Factory::TYPE_SELECT, EntityInterface $entity = null);

    /**
     * Check if data value is updated or not
     *
     * @param  EntityInterface $data
     * @param  string $field
     * @return bool
     * @throws \DomainException
     */
    public function isDataUpdated($data, $field);

    /**
     * Quote parameter according to the connection.
     *
     * @param  int|float|string|bool $value
     * @return string
     */
    public function quote($value);

    /**
     * Start new transaction.
     */
    public function beginTransaction();

    /**
     * Commit transactions.
     *
     * @return void
     */
    public function commit();

    /**
     * Rollback transactions.
     *
     * @return void
     */
    public function rollBack();

    /**
     * Check if we are in transaction or not.
     *
     * @return bool
     */
    public function inTransaction();
}
