<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eureka\Component\Orm\Query;

/**
 * QueryBuilderInterface
 *
 * @author Romain Cottard
 */
interface QueryBuilderInterface
{
    /**
     * Clear query params
     *
     * @return $this
     */
    public function clear();

    /**
     * @return array
     */
    public function getBind();

    /**
     * @return string
     * @throws \Eureka\Component\Orm\Exception\OrmException
     */
    public function getQuery();

    /**
     * Get indexed by
     *
     * @return string
     */
    public function getListIndexedByField();

    /**
     * Set indexed by
     *
     * @param  string $field
     * @return $this
     */
    public function setListIndexedByField($field);
}
