<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eureka\Component\Orm\Query\Traits;

/**
 * Class LimitTrait
 *
 * @author Romain Cottard
 */
trait LimitTrait
{
    /** @var int $limit Max limit for current query. */
    protected $limit = null;

    /** @var int $offset Start fetch result position for current query */
    protected $offset = null;

    /**
     * Set limit & offset.
     *
     * @param  int $limit
     * @param  int $offset
     * @return $this
     */
    public function setLimit($limit, $offset = null)
    {
        $this->limit  = (int) $limit;
        $this->offset = $offset;

        return $this;
    }

    /**
     * Get limit clause.
     *
     * @return string
     */
    public function getQueryLimit()
    {
        if ($this->limit !== null && $this->offset !== null) {
            return 'LIMIT ' . $this->offset . ', ' . $this->limit;
        } else {
            if (null !== $this->limit) {
                return 'LIMIT ' . $this->limit;
            } else {
                return '';
            }
        }
    }

    /**
     * @return $this
     */
    public function resetLimit()
    {
        $this->offset = 0;
        $this->limit  = 0;

        return $this;
    }
}
