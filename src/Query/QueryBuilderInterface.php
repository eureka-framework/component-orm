<?php declare(strict_types=1);

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eureka\Component\Orm\Query;

use Eureka\Component\Orm\Exception\OrmException;

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
     * @return QueryBuilderInterface
     */
    public function clear(): QueryBuilderInterface;

    /**
     * @return array
     */
    public function getBind(): array;

    /**
     * @return string
     * @throws OrmException
     */
    public function getQuery(): string;

    /**
     * Get indexed by
     *
     * @return string
     */
    public function getListIndexedByField(): string;

    /**
     * Set indexed by
     *
     * @param  string $field
     * @return QueryBuilderInterface
     */
    public function setListIndexedByField(string $field): QueryBuilderInterface;
}
