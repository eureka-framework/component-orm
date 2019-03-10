<?php declare(strict_types=1);

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eureka\Component\Orm\Traits;

use Eureka\Component\Database\Connection;
use Eureka\Component\Orm\EntityInterface;
use Eureka\Component\Orm\Exception;
use Eureka\Component\Orm\Query;
use Eureka\Component\Orm\RepositoryInterface;

/**
 * DataMapper Mapper abstract class.
 *
 * @author Romain Cottard
 */
trait MapperTrait
{
    /** @var string $table */
    protected $table = '';

    /** @var string[] $fields */
    protected $fields = [];

    /** @var string[] $primaryKeys */
    protected $primaryKeys = [];

    /** @var string[] $entityNamesMap */
    protected $entityNamesMap = [];

    /** @var int $lastId */
    protected $lastId = 0;

    /** @var int $rowCount The number of rows affected by the last SQL statement */
    protected $rowCount = 0;

    /** @var \Eureka\Component\Orm\RepositoryInterface[] $mappers */
    protected $mappers = [];

    /** @var array $joinConfigs */
    protected $joinConfigs = [];

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
     * @param  \Eureka\Component\Orm\RepositoryInterface[] $mappers
     * @return RepositoryInterface
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
            throw new \LogicException(__METHOD__ . '|Cannot use getMaxId() method for table with multiple primary keys !');
        }

        $field = reset($this->primaryKeys);

        /** @var Connection $connection */
        $connection = $this->getConnection();

        $statement = $connection->prepare('SELECT MAX(' . $field . ') AS ' . $field . ' FROM ' . $this->getTable());
        $statement->execute();

        return $statement->fetch(Connection::FETCH_OBJ)->{$field};
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
     * @param  \Eureka\Component\Orm\Query\QueryBuilder $queryBuilder
     * @param  string $field
     * @return int
     */
    public function count(Query\QueryBuilder $queryBuilder, string $field = '*'): int
    {
        /** @var Connection $connection */
        $connection = $this->getConnection();

        $statement = $connection->prepare($queryBuilder->getQueryCount($field));
        $statement->execute($queryBuilder->getBind());

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
     * @param \Eureka\Component\Orm\Query\QueryBuilderInterface
     * @return array
     * @throws Exception\OrmException
     */
    public function query(Query\QueryBuilderInterface $queryBuilder): array
    {
        /** @var Connection $connection */
        $connection = $this->getConnection();

        /** @var Connection $this->connection */
        $indexedBy = $queryBuilder->getListIndexedByField();
        $statement = $connection->prepare($queryBuilder->getQuery());
        $statement->execute($queryBuilder->getBind());

        $collection = [];

        $queryBuilder->clear();

        $id = 0;
        while (false !== ($row = $statement->fetch(Connection::FETCH_OBJ))) {
            if ($indexedBy !== null && !isset($row->$indexedBy)) {
                throw new Exception\OrmException('List is supposed to be indexed by a column that does not exist: ' . $indexedBy);
            }

            $index              = $indexedBy !== null ? $row->$indexedBy : $id++;
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
        /** @var Connection $connection */
        $connection = $this->getConnection();

        $statement = $connection->prepare($queryBuilder->getQuery());
        $statement->execute($queryBuilder->getBind());

        $queryBuilder->clear();

        $collection = [];

        while (false !== ($row = $statement->fetch(Connection::FETCH_OBJ))) {
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
            /** @var \Eureka\Component\Orm\AbstractMapper $this */
            $collection = $this->selectFromCache($this->connection, $this, $queryBuilder);
        }

        if ($this->cacheSkipMissingItemQuery) {
            $this->cacheSkipMissingItemQuery = false;
            $queryBuilder->clear();

            return $collection;
        }

        /** @var Connection $connection */
        $connection = $this->getConnection();

        $statement = $connection->prepare($queryBuilder->getQuery());
        $statement->execute($queryBuilder->getBind());

        while (false !== ($row = $statement->fetch(Connection::FETCH_OBJ))) {
            /** @var EntityInterface $entity */
            $entity = $this->newEntity($row, true);
            $collection[$entity->getCacheKey()] = $entity;
            $this->setCacheEntity($entity);
        }

        $queryBuilder->clear();

        return $collection;
    }

    /**
     * Set fields for mapper.
     *
     * @param  array $fields
     * @return RepositoryInterface
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
     * @return RepositoryInterface
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
     * @return RepositoryInterface
     */
    protected function setTable(string $table): RepositoryInterface
    {
        $this->table = $table;

        return $this;
    }

    /**
     * @param array $nameMap
     * @return RepositoryInterface
     */
    protected function setNamesMap(array $nameMap): RepositoryInterface
    {
        $this->entityNamesMap = $nameMap;

        return $this;
    }

    /**
     * @param array $joinConfigs
     * @return RepositoryInterface
     */
    protected function setJoinConfigs(array $joinConfigs): RepositoryInterface
    {
        $this->joinConfigs = $joinConfigs;

        return $this;
    }
}
