<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Eureka\Component\Orm\Query\Interfaces;

interface LimitAwareInterface
{
    public function setLimit(int $limit, ?int $offset = null): static;

    public function getQueryLimit(): string;
}
