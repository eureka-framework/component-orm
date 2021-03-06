<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Eureka\Component\Orm;

use Eureka\Component\Orm\Query\QueryBuilder;
use Eureka\Component\Orm\Query\QueryBuilderInterface;
use Eureka\Component\Orm\Query\SelectBuilder;

/**
 * DataMapper Mapper abstract class.
 *
 * @author  Romain Cottard
 */
interface MapperInterface extends CacheAwareInterface, ConnectionAwareInterface, EntityAwareInterface
{
    /**
     * @param  AbstractMapper[] $mappers
     * @return RepositoryInterface
     */
    public function addMappers(array $mappers): RepositoryInterface;

    /**
     * @param string $name
     * @return RepositoryInterface
     */
    public function getMapper(string $name): RepositoryInterface;

    /**
     * Return fields for current table.
     *
     * @return string[]
     */
    public function getFields(): array;

    /**
     * Return the primary keys
     *
     * @return string[]
     */
    public function getPrimaryKeys(): array;

    /**
     * Return a map of names (set, get and property) for a db field
     *
     * @param  string $field
     * @return string[]
     * @throws \OutOfRangeException
     */
    public function getNamesMap(string $field): array;

    /**
     * Return fields for current table.
     *
     * @return string
     */
    public function getTable(): string;

    /**
     *  Returns the number of rows affected by the last SQL statement
     *
     * @return int
     */
    public function rowCount(): int;

    /**
     * Return autoincrement id of the last insert query.
     *
     * @return int
     */
    public function getLastId(): int;

    /**
     * Get the higher value for the primary key
     *
     * @return int
     * @throws \LogicException
     */
    public function getMaxId(): int;

    /**
     * Count number of results for query.
     *
     * @param  QueryBuilder $queryBuilder
     * @param  string $field
     * @return int
     * @throws \DomainException
     */
    public function count(QueryBuilder $queryBuilder, string $field = '*'): int;

    /**
     * Check if value row exists in database..
     *
     * @param  SelectBuilder $queryBuilder
     * @return bool
     * @throws Exception\OrmException
     */
    public function rowExists(SelectBuilder $queryBuilder): bool;

    /**
     * Fetch rows for specified query.
     *
     * @param  QueryBuilderInterface $queryBuilder
     * @return EntityInterface[] Array of EntityInterface object for query.
     * @throws Exception\OrmException
     */
    public function query(QueryBuilderInterface $queryBuilder): array;

    /**
     * Fetch rows for specified query.
     *
     * @param  QueryBuilderInterface $queryBuilder
     * @return \stdClass[] Array of stdClass object for query.
     * @throws Exception\OrmException
     */
    public function queryRows(QueryBuilderInterface $queryBuilder): array;

    /**
     * Select all rows corresponding of where clause.
     *
     * @param  SelectBuilder $queryBuilder
     * @return EntityInterface[] List of row.
     * @throws Exception\OrmException
     */
    public function select(SelectBuilder $queryBuilder): array;

    /**
     * Select all rows corresponding of where clause.
     * Use eager loading to select joined entities.
     *
     * @param Query\SelectBuilder $queryBuilder
     * @param array $filters
     * @return array
     * @throws Exception\OrmException
     * @throws Exception\UndefinedMapperException
     */
    public function selectJoin(Query\SelectBuilder $queryBuilder, array $filters = []): array;

    /**
     * Select first rows corresponding to where clause.
     *
     * @param  SelectBuilder $queryBuilder
     * @return EntityInterface
     * @throws Exception\EntityNotExistsException
     * @throws Exception\InvalidQueryException
     * @throws Exception\OrmException
     */
    public function selectOne(SelectBuilder $queryBuilder);
}
