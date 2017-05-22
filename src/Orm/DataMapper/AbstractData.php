<?php

/**
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eureka\Component\Orm\DataMapper;

use Doctrine\DBAL\Connection as ConnectionInterface;

/**
 * DataMapper Data abstract class.
 *
 * @author  Romain Cottard
 */
abstract class AbstractData implements DataInterface
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
     * @var ConnectionInterface $db DatabaseInterface
     */
    protected $db = null;

    /**
     * Return cache key for the current data instance.
     *
     * @return string
     */
    abstract public function getCacheKey();

    /**
     * AbstractData constructor.
     *
     * @param ConnectionInterface $db
     */
    public function __construct(ConnectionInterface $db)
    {
        $this->db = $db;
    }

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
     * If the dataset is new.
     *
     * @return bool
     */
    public function hasAutoIncrement()
    {
        return $this->hasAutoIncrement;
    }

    /**
     * If the dataset exists.
     *
     * @return bool
     */
    public function exists()
    {
        return $this->exists;
    }

    /**
     * If the dataset exists.
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
     * Get connection.
     *
     * @return ConnectionInterface
     */
    public function getConnection()
    {
        return $this->db;
    }

    /**
     * Get object as array.
     *
     * @return array
     */
    public function toArray()
    {
        static $exludedProperties = ['db', 'hasAutoIncrement', 'exists', 'updated'];

        $array = [];

        foreach ($this as $name => $value) {
            if (in_array($name, $exludedProperties)) {
                continue;
            }

            $array[$name] = $value;
        }

        return $array;
    }
}
