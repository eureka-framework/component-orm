<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Eureka\Component\Orm\Query\Traits;

/**
 * Class LimitTrait
 *
 * @author Romain Cottard
 */
trait LimitAwareTrait
{
    /** @var int|null $limit Max limit for current query. */
    protected ?int $limit = null;

    /** @var int|null $offset Start fetch result position for current query */
    protected ?int $offset = null;

    public function setLimit(int $limit, ?int $offset = null): static
    {
        $this->limit  = $limit;
        $this->offset = $offset;

        return $this;
    }

    public function getQueryLimit(): string
    {
        if ($this->limit !== null && $this->offset !== null) {
            return ' LIMIT ' . $this->offset . ', ' . $this->limit;
        } elseif (null !== $this->limit) {
            return ' LIMIT ' . $this->limit;
        }

        return '';
    }

    public function resetLimit(): static
    {
        $this->offset = 0;
        $this->limit  = 0;

        return $this;
    }
}
