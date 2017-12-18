<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eureka\Component\Orm\DataMapper;

use Eureka\Component\Container\Container;
use Eureka\Component\Database\Connection;

/**
 * DataMapper Data abstract class.
 *
 * @author  Romain Cottard
 */
abstract class AbstractData implements DataInterface
{
    /** @var bool $hasAutoIncrement If data has auto increment value. */
    protected $hasAutoIncrement = false;

    /** @var bool $exists If data already exists in db for example. */
    protected $exists = false;

    /** @var array $updated List of updated field */
    protected $updated = [];

    /** @var string[][] $mapperClasses */
    protected $mapperClasses = [];

    /** @var AbstractMapper[] mappers */
    protected static $mappers = [];

    /** @var Connection[] $connections */
    protected static $connections = [];

    /**
     * Return cache key for the current data instance.
     *
     * @return string
     */
    abstract public function getCacheKey();

    /**
     * Set auto increment value.
     * Must be overridden to use internal property setter method, according to the data class definition.
     *
     * @param  integer $id
     * @return $this
     */
    public function setAutoIncrementId($id)
    {
        $id = (int) $id;

        if ($id <= 0) {
            throw new \UnderflowException('Auto-incremented ID must be greater than 0!');
        }

        return $this;
    }

    /**
     * AbstractData constructor.
     *
     * @param Connection $connection
     */
    public function __construct(Connection $connection)
    {
        self::$connections[$connection->getName()] = $connection;
    }

    /**
     * If the dataset is new.
     *
     * @return bool
     */
    public function hasAutoIncrement()
    {
        return $this->hasAutoIncrement;
    }

    /**
     * If the data set exists.
     *
     * @return bool
     */
    public function exists()
    {
        return $this->exists;
    }

    /**
     * If the data set exists.
     *
     * @param  bool $exists
     * @return $this
     */
    public function setExists($exists)
    {
        $this->exists = (bool) $exists;

        return $this;
    }

    /**
     * If at least one data has been updated.
     * If property name is specified, check only property.
     *
     * @param  string $property
     * @return bool
     */
    public function isUpdated($property = null)
    {
        if (null === $property) {
            return (count($this->updated) > 0);
        }

        return (isset($this->updated[$property]) && $this->updated[$property] === true);
    }

    /**
     * Reset updated list of properties
     *
     * @return $this
     */
    public function resetUpdated()
    {
        $this->updated = array();

        return $this;
    }

    /**
     * Empties the join* fields
     *
     * @return void
     */
    public function resetLazyLoadedData()
    {
        $attributes = get_object_vars($this);
        foreach ($attributes as $attribute => $value) {
            if (strpos($attribute, 'join') === 0) {
                $this->$attribute = null;
            }
        }
    }

    /**
     * Add mapper for lazy loading joins.
     *
     * @param  AbstractMapper $mapper
     * @return $this
     */
    public function addMapper(AbstractMapper $mapper)
    {
        self::$mappers[get_class($mapper)] = $mapper;

        return $this;
    }

    /**
     * Set mapper list for lazy loading joins.
     *
     * @param  AbstractMapper[] $mappers
     * @return $this
     */
    public function setMappers(array $mappers)
    {
        self::$mappers = [];

        foreach ($mappers as $mapper) {
            $this->addMapper($mapper);
        }

        return $this;
    }

    /**
     * @param  string $config
     * @return Connection
     * @throws \Eureka\Component\Container\Exception\NotFoundException
     */
    protected function getConnection($config)
    {
        if (!isset(self::$connections[$config])) {
            self::$connections[$config] = Container::getInstance()
                ->get('database')
                ->getConnection($config);
        }

        return self::$connections[$config];
    }

    /**
     * Initialize mappers.
     * This is a temporary method. Will be replaced by call of add/set mappers methods
     * in code.
     *
     * @return $this
     * @throws \Eureka\Component\Container\Exception\NotFoundException
     */
    protected function initMappers()
    {
        foreach ($this->mapperClasses as $mapperData) {
            $mapperClass  = $mapperData['class'];
            $mapperConfig = $mapperData['config'];

            //~ Skip already created mapper
            if (isset(self::$mappers[$mapperClass])) {
                continue;
            }

            $this->addMapper(new $mapperClass($this->getConnection($mapperConfig)));
        }

        return $this;
    }

    /**
     * Get object as array.
     *
     * @return array
     */
    public function toArray()
    {
        static $excludedProperties = ['db', 'hasAutoIncrement', 'exists', 'updated'];

        $array = [];

        foreach ($this as $name => $value) {
            if (in_array($name, $excludedProperties)) {
                continue;
            }

            $array[$name] = $value;
        }

        return $array;
    }
}
