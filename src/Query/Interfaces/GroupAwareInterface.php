<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Eureka\Component\Orm\Query\Interfaces;

use Eureka\Component\Orm\Enumerator\ClauseConcat;
use Eureka\Component\Orm\Enumerator\Operator;

interface GroupAwareInterface
{
    /**
     * Add groupBy clause.
     */
    public function addGroupBy(string $field): static;

    /**
     * Add having clause.
     */
    public function addHaving(
        string $field,
        string|int|float|bool|null $value,
        Operator $operator = Operator::Equal,
        ClauseConcat $clauseConcat = ClauseConcat::And
    ): static;

    /**
     * Get GroupBy clause.
     */
    public function getQueryGroupBy(): string;

    /**
     * Get Having clause.
     */
    public function getQueryHaving(): string;
}
