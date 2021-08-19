<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Eureka\Component\Orm\Traits;

use Eureka\Component\Orm\AbstractMapper;
use Eureka\Component\Orm\EntityInterface;
use Eureka\Component\Orm\Enumerator\JoinRelation;
use Eureka\Component\Orm\Exception;
use Eureka\Component\Orm\Query;
use Eureka\Component\Orm\RepositoryInterface;
use PDO;

/**
 * DataMapper Mapper abstract class.
 *
 * @author Romain Cottard
 */
trait MapperTrait
{
    /** @var string $table */
    protected string $table = '';

    /** @var string[] $fields */
    protected array $fields = [];

    /** @var string[] $primaryKeys */
    protected array $primaryKeys = [];

    /** @var string[][] $entityNamesMap */
    protected array $entityNamesMap = [];

    /** @var int $lastId */
    protected int $lastId = 0;

    /** @var int $rowCount The number of rows affected by the last SQL statement */
    protected int $rowCount = 0;

    /** @var RepositoryInterface[] $mappers */
    protected array $mappers = [];

    /** @var array $joinConfigs */
    protected array $joinConfigs = [];

    /**
     * Get fields for Mapper
     *
     * @return array
     */
    public function getFields(): array
    {
        return $this->fields;
    }

    /**
     * Get primary keys.
     *
     * @return string[]
     */
    public function getPrimaryKeys(): array
    {
        return $this->primaryKeys;
    }

    /**
     * Get table name.
     *
     * @return string
     */
    public function getTable(): string
    {
        return $this->table;
    }

    /**
     * @param  string $field
     * @return array
     */
    public function getNamesMap(string $field): array
    {
        if (!isset($this->entityNamesMap[$field])) {
            throw new \OutOfRangeException('Specified field does not exist in data names map');
        }

        return $this->entityNamesMap[$field];
    }

    /**
     * @param  RepositoryInterface[] $mappers
     * @return self|RepositoryInterface
     */
    public function addMappers(array $mappers): RepositoryInterface
    {
        $this->mappers = array_merge($this->mappers, $mappers);

        return $this;
    }

    /**
     * @param string $name
     * @return RepositoryInterface
     * @throws Exception\UndefinedMapperException
     */
    public function getMapper(string $name): RepositoryInterface
    {
        if (!isset($this->mappers[$name])) {
            throw new Exception\UndefinedMapperException('Mapper does not exist! (mapper: ' . $name . ')');
        }

        return $this->mappers[$name];
    }

    /**
     * @return int
     */
    public function getLastId(): int
    {
        return $this->lastId;
    }

    /**
     * @return int
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
     * Count number of rows corresponding to the query.
     *
     * @param  Query\QueryBuilder $queryBuilder
     * @param  string $field
     * @return int
     */
    public function count(Query\QueryBuilder $queryBuilder, string $field = '*'): int
    {
        $statement = $this->execute($queryBuilder->getQueryCount($field), $queryBuilder->getBind());

        $queryBuilder->clear();

        return (int) $statement->fetchColumn(0);
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
        } catch (Exception\EntityNotExistsException $exception) {
            return false;
        }
    }

    /**
     * @param Query\QueryBuilderInterface $queryBuilder
     * @return array
     * @throws Exception\OrmException
     */
    public function query(Query\QueryBuilderInterface $queryBuilder): array
    {
        $indexedBy = $queryBuilder->getListIndexedByField();
        $statement = $this->execute($queryBuilder->getQuery(), $queryBuilder->getBind());

        $collection = [];

        $queryBuilder->clear();

        $id = 0;
        while (false !== ($row = $statement->fetch(PDO::FETCH_OBJ))) {
            if (!empty($indexedBy) && !isset($row->{$indexedBy})) {
                throw new Exception\OrmException(
                    'List is supposed to be indexed by a column that does not exist: ' . $indexedBy
                );
            }

            $index              = !empty($indexedBy) ? $row->{$indexedBy} : $id++;
            $collection[$index] = $this->newEntity($row, true);
        }

        return $collection;
    }

    /**
     * @param Query\QueryBuilderInterface $queryBuilder
     * @return array
     * @throws Exception\OrmException
     */
    public function queryRows(Query\QueryBuilderInterface $queryBuilder): array
    {
        $statement = $this->execute($queryBuilder->getQuery(), $queryBuilder->getBind());

        $queryBuilder->clear();

        $collection = [];

        while (false !== ($row = $statement->fetch(PDO::FETCH_OBJ))) {
            $collection[] = $row;
        }

        return $collection;
    }

    /**
     * @param Query\SelectBuilder $queryBuilder
     * @return EntityInterface
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
     * @return array
     * @throws Exception\InvalidQueryException
     * @throws Exception\OrmException
     */
    public function select(Query\SelectBuilder $queryBuilder): array
    {
        $collection = [];

        if ($this->isCacheEnabledOnRead) {
            /** @var AbstractMapper $this */
            $collection = $this->selectFromCache($this, $queryBuilder);
        }

        if ($this->cacheSkipMissingItemQuery) {
            $this->cacheSkipMissingItemQuery = false;
            $queryBuilder->clear();

            return $collection;
        }

        $statement = $this->execute($queryBuilder->getQuery(), $queryBuilder->getBind());

        while (false !== ($row = $statement->fetch(PDO::FETCH_OBJ))) {
            $entity                             = $this->newEntity($row, true);
            $collection[$entity->getCacheKey()] = $entity;
            $this->setCacheEntity($entity->getCacheKey(), $row);
        }

        $queryBuilder->clear();

        return $collection;
    }

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
     * Set fields for mapper.
     *
     * @param  array $fields
     * @return self|RepositoryInterface
     */
    protected function setFields(array $fields = []): RepositoryInterface
    {
        $this->fields = $fields;

        return $this;
    }

    /**
     * Set primary keys.
     *
     * @param  array $primaryKeys
     * @return self|RepositoryInterface
     */
    protected function setPrimaryKeys(array $primaryKeys): RepositoryInterface
    {
        $this->primaryKeys = $primaryKeys;

        return $this;
    }

    /**
     * Set table name.
     *
     * @param  string $table
     * @return self|RepositoryInterface
     */
    protected function setTable(string $table): RepositoryInterface
    {
        $this->table = $table;

        return $this;
    }

    /**
     * @param array $nameMap
     * @return self|RepositoryInterface
     */
    protected function setNamesMap(array $nameMap): RepositoryInterface
    {
        $this->entityNamesMap = $nameMap;

        return $this;
    }

    /**
     * @param array $joinConfigs
     * @return self|RepositoryInterface
     */
    protected function setJoinConfigs(array $joinConfigs): RepositoryInterface
    {
        $this->joinConfigs = $joinConfigs;

        return $this;
    }

    /**
     * Get list of joins config filters if filters is provided.
     *
     * @param  array $filters
     * @return array
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
     * @param array $joinConfigs
     * @return array
     * @throws Exception\OrmException
     * @throws Exception\UndefinedMapperException
     */
    private function getRawResultsWithJoin(Query\SelectBuilder $queryBuilder, array $joinConfigs): array
    {
        /** @var RepositoryInterface $this */
        //~ Add main fields to query builder
        foreach ($queryBuilder->getQueryFieldsList($this, true) as $field) {
            $queryBuilder->addField($field, '', false);
        }

        $index = 0;

        foreach ($joinConfigs as $join) {
            $mapper = $this->getMapper($join['mapper']);

            $aliasPrefix = $mapper->getTable() . '_' . $index++;
            $aliasSuffix = '_' . $aliasPrefix;

            $keyLeft  = key($join['keys']);
            $keyRight = current($join['keys']);

            $keyRight = $keyRight === true ? $keyLeft : $keyRight;

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
                $aliasPrefix
            );
        }

        return $this->queryRows($queryBuilder);
    }

    /**
     * @param \stdClass[] $list
     * @param array $joinConfigs
     * @return array
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
            foreach ($joinConfigs as $name => $join) {
                $mapper      = $this->getMapper($join['mapper']);
                $aliasSuffix = '_' . $mapper->getTable() . '_' . $index++;

                $mapper->enableIgnoreNotMappedFields();

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
}
