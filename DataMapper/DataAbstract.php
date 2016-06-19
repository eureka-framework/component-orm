<?php

/**
 * Copyright (c) 2010-2016 Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eureka\Component\Orm\DataMapper;

use Eureka\Component\Dependency;

/**
 * DataMapper Data abstract class.
 *
 * @author  Romain Cottard
 * @version 1.0.0
 */
abstract class DataAbstract
{
    /**
     * @var bool $hasAutoIncrement If data has auto increment value.
     */
    protected $hasAutoIncrement = false;

    /**
     * @var bool $exists If data already exists in db for example.
     */
    protected $exists = false;

    /**
     * @var array $updated List of updated field
     */
    protected $updated = array();

    /**
     * @var Dependency\ContainerInterface $dependencyContainer
     */
    protected $dependencyContainer = null;

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
     * DataAbstract constructor.
     *
     * @param Dependency\ContainerInterface $container
     */
    public function __construct(Dependency\ContainerInterface $container = null)
    {
        $this->initDependencyContainer($container);
    }

    /**
     * If the data set is new.
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
     * Initialize dependency container.
     *
     * @param  Dependency\ContainerInterface $container
     * @return $this
     */
    public function initDependencyContainer(Dependency\ContainerInterface $container = null)
    {
        if (null === $container) {
            $container = Dependency\Container::getInstance();
        }

        $this->dependencyContainer = $container;

        return $this;
    }
}
