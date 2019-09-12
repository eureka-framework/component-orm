<?php declare(strict_types=1);

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eureka\Component\Orm\Query\Traits;

use Eureka\Component\Orm\Query\QueryBuilderInterface;

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
     * @param  string $field
     * @param  string $dir
     * @return self|QueryBuilderInterface
     */
    public function addOrder(string $field, string $dir = 'ASC'): QueryBuilderInterface
    {
        $this->orderList[] = $field . ' ' . $dir;

        return $this;
    }

    /**
     * Get OrderBy clause.
     *
     * @return string
     */
    public function getQueryOrderBy(): string
    {
        return (0 < count($this->orderList) ? 'ORDER BY ' . implode(',', $this->orderList) : '');
    }

    /**
     * @return self|QueryBuilderInterface
     */
    public function resetOrder(): QueryBuilderInterface
    {
        $this->orderList = [];

        return $this;
    }
}
