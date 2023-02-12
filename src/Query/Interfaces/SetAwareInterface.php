<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Eureka\Component\Orm\Query\Interfaces;

use Eureka\Component\Orm\Exception\EmptySetClauseException;

interface SetAwareInterface
{
    public function addSet(string $field, string|int|float|bool|null $value): static;

    public function addUpdate(string $field, string|int|float|bool|null $value): static;

    /**
     * @throws EmptySetClauseException
     */
    public function getQuerySet(): string;

    /**
     * Get on duplicate update clause.
     */
    public function getQueryDuplicateUpdate(): string;
}
