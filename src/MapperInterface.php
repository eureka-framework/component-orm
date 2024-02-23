<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Eureka\Component\Orm;

/**
 * DataMapper Mapper abstract class.
 *
 * @author  Romain Cottard
 *
 * @template TEntity of EntityInterface
 */
interface MapperInterface
{
    /**
     * @param  array<class-string, RepositoryInterface> $mappers
     * @return static
     */
    public function addMappers(array $mappers): static;

    /**
     * @phpstan-param class-string $name
     * @phpstan-return RepositoryInterface
     */
    public function getMapper(string $name);

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
     * @param  Query\QueryBuilder $queryBuilder
     * @param  string $field
     * @return int
     * @throws \DomainException
     */
    public function count(Query\QueryBuilder $queryBuilder, string $field = '*'): int;

    /**
     * Check if value row exists in database.
     *
     * @param  Query\SelectBuilder $queryBuilder
     * @return bool
     * @throws Exception\OrmException
     */
    public function rowExists(Query\SelectBuilder $queryBuilder): bool;

    /**
     * Fetch rows for specified query.
     *
     * @param  Query\Interfaces\QueryBuilderInterface $queryBuilder
     * @return TEntity[] Array of EntityInterface object for query.
     * @throws Exception\OrmException
     */
    public function query(Query\Interfaces\QueryBuilderInterface $queryBuilder): array;

    /**
     * Fetch rows for specified query.
     *
     * @param  Query\Interfaces\QueryBuilderInterface $queryBuilder
     * @return \stdClass[] Array of stdClass object for query.
     * @throws Exception\OrmException
     */
    public function queryRows(Query\Interfaces\QueryBuilderInterface $queryBuilder): array;

    /**
     * Select all rows corresponding of where clause.
     *
     * @param  Query\SelectBuilder $queryBuilder
     * @return TEntity[] List of row.
     * @throws Exception\OrmException
     */
    public function select(Query\SelectBuilder $queryBuilder): array;

    /**
     * Select all rows corresponding of where clause.
     * Use eager loading to select joined entities.
     *
     * @param Query\SelectBuilder $queryBuilder
     * @param string[] $filters
     * @return TEntity[]
     * @throws Exception\OrmException
     * @throws Exception\UndefinedMapperException
     */
    public function selectJoin(Query\SelectBuilder $queryBuilder, array $filters = []): array;

    /**
     * Select first rows corresponding to where clause.
     *
     * @param  Query\SelectBuilder $queryBuilder
     * @return TEntity
     * @throws Exception\EntityNotExistsException
     * @throws Exception\InvalidQueryException
     * @throws Exception\OrmException
     */
    public function selectOne(Query\SelectBuilder $queryBuilder): EntityInterface;
}
