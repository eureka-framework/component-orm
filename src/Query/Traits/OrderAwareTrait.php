<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Eureka\Component\Orm\Query\Traits;

use Eureka\Component\Orm\Enumerator\Order;

/**
 * Class OrderTrait
 *
 * @author Romain Cottard
 */
trait OrderAwareTrait
{
    /** @var string[] $orderList List of order by restriction for current query */
    private array $orderList = [];

    public function addOrder(string $field, Order $dir = Order::Asc): static
    {
        $this->orderList[] = $field . ' ' . $dir->value;

        return $this;
    }

    public function getQueryOrderBy(): string
    {
        return (0 < count($this->orderList) ? ' ORDER BY ' . implode(',', $this->orderList) : '');
    }

    public function resetOrder(): static
    {
        $this->orderList = [];

        return $this;
    }
}
