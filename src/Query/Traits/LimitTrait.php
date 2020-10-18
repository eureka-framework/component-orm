<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Eureka\Component\Orm\Query\Traits;

use Eureka\Component\Orm\Query\QueryBuilderInterface;

/**
 * Class LimitTrait
 *
 * @author Romain Cottard
 */
trait LimitTrait
{
    /** @var int|null $limit Max limit for current query. */
    protected ?int $limit = null;

    /** @var int|null $offset Start fetch result position for current query */
    protected ?int $offset = null;

    /**
     * Set limit & offset.
     *
     * @param  int $limit
     * @param  int $offset
     * @return self|QueryBuilderInterface
     */
    public function setLimit(int $limit, ?int $offset = null): QueryBuilderInterface
    {
        $this->limit  = $limit;
        $this->offset = $offset;

        return $this;
    }

    /**
     * Get limit clause.
     *
     * @return string
     */
    public function getQueryLimit(): string
    {
        if ($this->limit !== null && $this->offset !== null) {
            return ' LIMIT ' . $this->offset . ', ' . $this->limit;
        } elseif (null !== $this->limit) {
            return ' LIMIT ' . $this->limit;
        }

        return '';
    }

    /**
     * @return self|QueryBuilderInterface
     */
    public function resetLimit(): QueryBuilderInterface
    {
        $this->offset = 0;
        $this->limit  = 0;

        return $this;
    }
}
