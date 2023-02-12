<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Eureka\Component\Orm\Query\Interfaces;

use Eureka\Component\Orm\Enumerator\Order;

interface OrderAwareInterface
{
    public function addOrder(string $field, Order $dir = Order::Asc): static;

    public function getQueryOrderBy(): string;
}
