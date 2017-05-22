<?php

/**
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eureka\Component\Orm\DataMapper;

/**
 * DataMapper Data abstract class.
 *
 * @author  Romain Cottard
 */
interface DataInterface
{
    /**
     * Return cache key for the current data instance.
     *
     * @return string
     */
    public function getCacheKey();

    /**
     * Set auto increment value.
     * Must be overridden to use internal property setter method, according to the data class definition.
     *
     * @param  integer $id
     * @return $this
     */
    public function setAutoIncrementId($id);

    /**
     * If the dataset is new.
     *
     * @return bool
     */
    public function hasAutoIncrement();

    /**
     * If the dataset exists.
     *
     * @return bool
     */
    public function exists();

    /**
     * If at least one data has been updated.
     * If property name is specified, check only property.
     *
     * @param  string $property
     * @return bool
     */
    public function isUpdated($property = null);

    /**
     * Reset updated list of properties
     *
     * @return $this
     */
    public function resetUpdated();

    /**
     * Empties the join* fields
     *
     * @return void
     */
    public function resetLazyLoadedData();
}
