<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eureka\Component\Orm;

use Eureka\Component\Database\Connection;

/**
 * DataMapper Data abstract class.
 *
 * @author Romain Cottard
 */
abstract class AbstractEntity implements EntityInterface
{
    /** @var bool $hasAutoIncrement If data has auto increment value. */
    protected $hasAutoIncrement = false;

    /** @var bool $exists If data already exists in db for example. */
    protected $exists = false;

    /** @var bool $isDeleted If entity must be deleted instead of persist. */
    protected $isDeleted = false;

    /** @var array $updated List of updated field */
    protected $updated = [];

    /** @var string[][] $mapperClasses */
    protected $mapperClasses = [];

    /** @var AbstractMapper[] mappers */
    protected $mappers = [];

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
        return $this;
    }

    /**
     * AbstractEntity constructor.
     *
     * @param \Eureka\Component\Orm\MapperInterface[]
     */
    public function __construct($mappers = [])
    {
        $this->setMappers($mappers);
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
     * Flag entity as deleted.
     *
     * @param  bool $isDeleted
     * @return $this
     */
    public function setIsDelete($isDeleted)
    {
        $this->isDeleted = (bool) $isDeleted;

        return $this;
    }

    /**
     * If entity is flagged as deleted.
     *
     * @return bool
     */
    public function isDelete()
    {
        return $this->isDeleted;
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
        $this->mappers[get_class($mapper)] = $mapper;

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
        $this->mappers = [];

        foreach ($mappers as $mapper) {
            $this->addMapper($mapper);
        }

        return $this;
    }
}
