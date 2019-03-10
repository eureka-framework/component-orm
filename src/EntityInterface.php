<?php declare(strict_types=1);

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eureka\Component\Orm;

/**
 * DataMapper Data interface, should be implemented by all data objects
 *
 * @author  Romain Cottard
 */
interface EntityInterface
{
    /**
     * @return bool
     */
    public function hasAutoIncrement(): bool;

    /**
     * Set auto increment value.
     * Must be overridden to use internal property setter method, according to the data class definition.
     *
     * @param  int $id
     * @return $this
     */
    public function setAutoIncrementId(int $id);

    /**
     * Return cache key for the current data instance.
     *
     * @return string
     */
    public function getCacheKey(): string;

    /**
     * If the data set exists.
     *
     * @return bool
     */
    public function exists(): bool;

    /**
     * If at least one data has been updated.
     * If property name is specified, check only property.
     *
     * @param  bool $exists
     * @return $this
     */
    public function setExists(bool $exists);

    /**
     * If at least one data has been updated.
     * If property name is specified, check only property.
     *
     * @param  string $property
     * @return bool
     */
    public function isUpdated(string $property = null);

    /**
     * Reset updated list of properties
     *
     * @return $this
     */
    public function resetUpdated();
}
