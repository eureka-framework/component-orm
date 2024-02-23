<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Eureka\Component\Orm\Query\Interfaces;

interface JoinAwareInterface
{
    public function addJoin(
        string $joinType,
        string $joinTable,
        string $mainField,
        string $mainAlias,
        string $joinField,
        string $joinAlias
    ): static;

    public function hasJoin(): bool;

    public function getQueryJoin(): string;
}
