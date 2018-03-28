<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eureka\Component\Orm\Query\Traits;

/**
 * Class OrderTrait
 *
 * @author Romain Cottard
 */
trait OrderTrait
{
    /** @var string[] $orderList List of order by restriction for current query */
    private $orderList = [];

    /**
     * Add order clause.
     *
     * @param  string $order
     * @param  string $dir
     * @return $this
     */
    public function addOrder($order, $dir = 'ASC')
    {
        $this->orderList[] = $order . ' ' . $dir;

        return $this;
    }

    /**
     * Get OrderBy clause.
     *
     * @return string
     */
    public function getQueryOrderBy()
    {
        return (0 < count($this->orderList) ? 'ORDER BY ' . implode(',', $this->orderList) : '');
    }

    /**
     * @return $this
     */
    public function resetOrder()
    {
        $this->orderList = [];

        return $this;
    }
}
