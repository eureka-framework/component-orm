<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eureka\Component\Orm\DataMapper;

/**
 * DataMapper Data interface, should be implemented by all data objects
 *
 * @author  Romain Cottard
 */
interface DataInterface
{
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
}
