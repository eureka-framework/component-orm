<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Eureka\Component\Orm\Traits;

use Eureka\Component\Orm\EntityInterface;
use Eureka\Component\Orm\Enumerator\JoinRelation;
use Eureka\Component\Orm\Exception;
use Eureka\Component\Orm\Exception\ConnectionLostDuringTransactionException;
use Eureka\Component\Orm\Query;
use Eureka\Component\Orm\Query\Interfaces\QueryBuilderInterface;
use Eureka\Component\Orm\RepositoryInterface;
use PDO;

/**
 * DataMapper Mapper abstract class.
 *
 * @author Romain Cottard
 *
 * @template TRepository of RepositoryInterface
 * @template TEntity of EntityInterface
 */
trait MapperTrait
{
    use TableTrait;

    /** @var int $lastId */
    protected int $lastId = 0;

    /** @var int $rowCount The number of rows affected by the last SQL statement */
    protected int $rowCount = 0;

    /**
     * @var array<class-string, RepositoryInterface> $mappers
     */
    protected array $mappers = [];

    /**
     * @var array<array{
     *     mapper: class-string,
     *     type: string,
     *     relation: string,
     *     keys: array<string, bool|string>
     * }> $joinConfigs */
    protected array $joinConfigs = [];

    /**
     * @param  array<class-string, RepositoryInterface> $mappers
     * @return static
     */
    public function addMappers(array $mappers): static
    {
        $this->mappers = array_merge($this->mappers, $mappers);

        return $this;
    }

    /**
     * @phpstan-param class-string $name
     * @phpstan-return RepositoryInterface
     * @throws Exception\UndefinedMapperException
     */
    public function getMapper(string $name): RepositoryInterface
    {
        if (!isset($this->mappers[$name])) {
            throw new Exception\UndefinedMapperException('Mapper does not exist! (mapper: ' . $name . ')');
        }

        return $this->mappers[$name];
    }

    public function getLastId(): int
    {
        return $this->lastId;
    }

    public function setLastId(int $lastId): static
    {
        $this->lastId = $lastId;

        return $this;
    }

    /**
     * @return int
     * @throws Exception\ConnectionLostDuringTransactionException
     */
    public function getMaxId(): int
    {
        if (count($this->primaryKeys) > 1) {
            throw new \LogicException('Cannot use getMaxId() method for table with multiple primary keys !');
        }

        $field = reset($this->primaryKeys);

        $query     = 'SELECT MAX(' . $field . ') AS ' . $field . ' FROM ' . $this->getTable();

        $statement = $this->execute($query);

        return $statement->fetch(PDO::FETCH_OBJ)->{$field};
    }

    /**
     * @return int
     */
    public function rowCount(): int
    {
        return $this->rowCount;
    }

    /**
     *  Returns the number of rows affected by the last SQL SELECT statement with SQL_CALC_FOUND_ROWS enabled
     *
     * @return int
     * @throws ConnectionLostDuringTransactionException
     */
    public function rowCountOnSelect(): int
    {
        $statement = $this->execute('SELECT FOUND_ROWS()');
        /** @var int $count */
        $count = $statement->fetchColumn(0);
        return $count;
    }

    /**
     * Count number of rows corresponding to the query.
     *
     * @param Query\QueryBuilder $queryBuilder
     * @param string $field
     * @return int
     * @throws Exception\ConnectionLostDuringTransactionException
     * @throws Exception\EmptyWhereClauseException
     */
    public function count(Query\QueryBuilder $queryBuilder, string $field = '*'): int
    {
        $statement = $this->execute($queryBuilder->getQueryCount($field), $queryBuilder->getAllBind());

        $queryBuilder->clear();

        return (int) $statement->fetchColumn();
    }

    /**
     * @param Query\SelectBuilder $queryBuilder
     * @return bool
     * @throws Exception\InvalidQueryException
     * @throws Exception\OrmException
     */
    public function rowExists(Query\SelectBuilder $queryBuilder): bool
    {
        try {
            $this->selectOne($queryBuilder);

            return true;
        } catch (Exception\EntityNotExistsException) {
            return false;
        }
    }

    /**
     * @param QueryBuilderInterface $queryBuilder
     * @return TEntity[]
     * @throws Exception\OrmException
     */
    public function query(QueryBuilderInterface $queryBuilder): array
    {
        $indexedBy = $queryBuilder->getListIndexedByField();
        $statement = $this->execute($queryBuilder->getQuery(), $queryBuilder->getAllBind());

        $collection = [];

        $queryBuilder->clear();

        $id = 0;
        while (false !== ($row = $statement->fetch(PDO::FETCH_OBJ))) {
            /** @var \stdClass $row */
            if (!empty($indexedBy) && !isset($row->{$indexedBy})) {
                throw new Exception\OrmException(
                    'List is supposed to be indexed by a column that does not exist: ' . $indexedBy,
                );
            }

            $index              = !empty($indexedBy) ? $row->{$indexedBy} : $id++;
            $entity = $this->newEntity($row, true);
            $collection[$index] = $entity;
        }

        return $collection;
    }

    /**
     * @param QueryBuilderInterface $queryBuilder
     * @return \stdClass[]
     * @throws Exception\OrmException
     */
    public function queryRows(QueryBuilderInterface $queryBuilder): array
    {
        $statement = $this->execute($queryBuilder->getQuery(), $queryBuilder->getAllBind());

        $queryBuilder->clear();

        $collection = [];

        while (false !== ($row = $statement->fetch(PDO::FETCH_OBJ))) {
            /** @var \stdClass $row */
            $collection[] = $row;
        }

        return $collection;
    }

    /**
     * @param Query\SelectBuilder $queryBuilder
     * @return TEntity
     * @throws Exception\EntityNotExistsException
     * @throws Exception\InvalidQueryException
     * @throws Exception\OrmException
     */
    public function selectOne(Query\SelectBuilder $queryBuilder): EntityInterface
    {
        $queryBuilder->setLimit(1);

        $collection = $this->select($queryBuilder);

        if (empty($collection)) {
            throw new Exception\EntityNotExistsException('No data for current selection', 0);
        }

        return current($collection);
    }

    /**
     * @param Query\SelectBuilder $queryBuilder
     * @return TEntity[]
     * @throws Exception\InvalidQueryException
     * @throws Exception\OrmException
     */
    public function select(Query\SelectBuilder $queryBuilder): array
    {
        $collection         = [];
        $listIndexedByField = $queryBuilder->getListIndexedByField();

        if ($this->isCacheEnabledOnRead) {
            /** @var TRepository $repository */
            $repository = $this;
            $collection = $this->selectFromCache($repository, $queryBuilder);
        }

        if ($this->cacheSkipMissingItemQuery) {
            $this->cacheSkipMissingItemQuery = false;
            $queryBuilder->clear();

            /** @var TEntity[] $collection */
            $collection = array_filter($collection);

            if (!empty($listIndexedByField)) {
                $collection = $this->setIndexFieldsOnCollection($listIndexedByField, $collection);
            }

            return $collection;
        }

        $statement = $this->execute($queryBuilder->getQuery(), $queryBuilder->getAllBind());

        while (false !== ($row = $statement->fetch(PDO::FETCH_OBJ))) {
            /** @var \stdClass $row */
            $entity                             = $this->newEntity($row, true);
            $collection[$entity->getCacheKey()] = $entity;
            $this->setCacheEntity($entity->getCacheKey(), $row);
        }

        /** @var TEntity[] $collection */
        $collection = array_filter($collection);

        if (!empty($listIndexedByField)) {
            $collection = $this->setIndexFieldsOnCollection($listIndexedByField, $collection);
        }

        $queryBuilder->clear();

        return $collection;
    }

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
    public function selectJoin(Query\SelectBuilder $queryBuilder, array $filters = []): array
    {
        $configs = $this->getJoinsConfig($filters);
        $list    = $this->getRawResultsWithJoin($queryBuilder, $configs);
        [$collection, $relations] = $this->getCollectionAndRelations($list, $configs);

        //~ Resolve all relations
        foreach ($collection as $hash => $data) {
            foreach ($configs as $name => $join) {
                if ($join['relation'] === JoinRelation::MANY) {
                    $setter = 'setAll' . $name;
                    $data->$setter($relations[$name][$hash]);
                } elseif ($join['relation'] === JoinRelation::ONE && !empty($relations[$name][$hash])) {
                    $setter = 'set' . $name;
                    $data->$setter(reset($relations[$name][$hash]));
                }
            }
        }

        return array_values($collection);
    }

    /**
     * @param array<array{
     *     mapper: class-string,
     *     type: string,
     *     relation: string,
     *     keys: array<string, bool|string>
     * }> $joinConfigs
     * @return static
     */
    protected function setJoinConfigs(array $joinConfigs): static
    {
        $this->joinConfigs = $joinConfigs;

        return $this;
    }

    /**
     * Get list of joins config filters if filters is provided.
     *
     * @param  string[] $filters
     * @return array<array{
     *     mapper: class-string,
     *     type: string,
     *     relation: string,
     *     keys: array<string, bool|string>
     * }>
     */
    private function getJoinsConfig(array $filters = []): array
    {
        $joins = [];

        foreach ($this->joinConfigs as $name => $join) {
            if (!empty($filters) && !in_array($name, $filters)) {
                continue;
            }

            $joins[$name] = $join;
        }

        return $joins;
    }

    /**
     * @param Query\SelectBuilder $queryBuilder
     * @param array<array{
     *     mapper: class-string,
     *     type: string,
     *     relation: string,
     *     keys: array<string, bool|string>
     * }> $joinConfigs
     * @return \stdClass[]
     * @throws Exception\OrmException
     * @throws Exception\UndefinedMapperException
     */
    private function getRawResultsWithJoin(Query\SelectBuilder $queryBuilder, array $joinConfigs): array
    {
        /** @var TRepository $repository */
        $repository = $this;

        //~ Add main fields to query builder
        foreach ($queryBuilder->getQueryFieldsList($repository, true) as $field) {
            $queryBuilder->addField($field, '', false);
        }

        $index = 0;

        foreach ($joinConfigs as $join) {
            $mapper = $this->getMapper($join['mapper']);

            $aliasPrefix = $mapper->getTable() . '_' . $index++;
            $aliasSuffix = '_' . $aliasPrefix;

            $keyLeft  = (string) key($join['keys']);
            $keyRight = current($join['keys']);

            $keyRight = (string) ($keyRight === true ? $keyLeft : $keyRight);

            //~ Add joined fields to query builder
            foreach ($queryBuilder->getQueryFieldsList($mapper, true, false, $aliasPrefix, $aliasSuffix) as $field) {
                $queryBuilder->addField($field, '', false);
            }

            //~ Add join to query builder
            $queryBuilder->addJoin(
                $join['type'],
                $mapper->getTable(),
                $keyLeft,
                $this->getTable(),
                $keyRight,
                $aliasPrefix,
            );
        }

        return $this->queryRows($queryBuilder);
    }

    /**
     * @param \stdClass[] $list
     * @param array<array{
     *     mapper: class-string,
     *     type: string,
     *     relation: string,
     *     keys: array<string, bool|string>
     * }> $joinConfigs
     * @return array{
     *     0: array<string, TEntity>,
     *     1: array<string, array<string, array<int, TEntity>>>
     * }
     * @throws Exception\UndefinedMapperException
     */
    private function getCollectionAndRelations(array $list, array $joinConfigs): array
    {
        $this->enableIgnoreNotMappedFields();

        $collection = [];
        $relations  = [];
        $getters    = [];
        foreach ($this->getPrimaryKeys() as $primaryKey) {
            $map       = $this->getNamesMap($primaryKey);
            $getters[] = $map['get'];
        }

        foreach ($list as $row) {
            //~ Main entity
            $data = $this->newEntity($row, true);

            //~ Resolve one-many relations
            $ids = [];
            foreach ($getters as $getterPrimaryKey) {
                $ids[] = '|' . $data->$getterPrimaryKey();
            }
            $hash = md5(implode('|', $ids));

            if (!isset($collection[$hash])) {
                $collection[$hash] = $data;
            }

            //~ Build relation joined
            $index = 0;
            /** @var string $name */
            foreach ($joinConfigs as $name => $join) {
                $mapper      = $this->getMapper($join['mapper']);
                $aliasSuffix = '_' . $mapper->getTable() . '_' . $index++;

                $mapper->enableIgnoreNotMappedFields();

                /** @var TEntity $dataJoin */
                $dataJoin = $mapper->newEntitySuffixAware($row, $aliasSuffix, $join['type']);

                if (!isset($relations[$name][$hash])) {
                    $relations[$name][$hash] = [];
                }

                if ($dataJoin !== null) {
                    $relations[$name][$hash][] = $dataJoin;
                }

                $mapper->disableIgnoreNotMappedFields();
            }
        }

        $this->disableIgnoreNotMappedFields();

        return [$collection, $relations];
    }

    /**
     * @param string $listIndexedBy
     * @param TEntity[] $rawCollection
     * @return TEntity[]
     */
    private function setIndexFieldsOnCollection(string $listIndexedBy, array $rawCollection): array
    {
        $collection = [];
        $nameMap = $this->getNamesMap($listIndexedBy);
        $getter = $nameMap['get'];
        foreach ($rawCollection as $item) {
            $collection[$item->$getter()] = $item;
        }

        return $collection;
    }
}
