<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Eureka\Component\Orm\Query\Traits;

use Eureka\Component\Orm\Enumerator\ClauseConcat;
use Eureka\Component\Orm\Enumerator\Operator;

/**
 * Class GroupTrait
 *
 * @author Romain Cottard
 */
trait GroupAwareTrait
{
    /** @var string[] $groupList List of groupBy for current query */
    protected array $groupList = [];

    /** @var string[] $havingList List of having restriction for current query */
    protected array $havingList = [];

    /**
     * Add groupBy clause.
     */
    public function addGroupBy(string $field): static
    {
        $this->groupList[] = $field;

        return $this;
    }

    /**
     * Add having clause.
     */
    public function addHaving(
        string $field,
        string|int|float|bool|null $value,
        Operator $operator = Operator::Equal,
        ClauseConcat $clauseConcat = ClauseConcat::And
    ): static {
        $fieldHaving = (0 < count($this->havingList) ? ' ' . $clauseConcat->value . ' ' . $field : $field);

        $bindName = $this->bind($field, $value, true);
        $this->havingList[] = $fieldHaving . ' ' . $operator->value . ' ' . $bindName;

        return $this;
    }

    /**
     * Get GroupBy clause.
     */
    public function getQueryGroupBy(): string
    {
        return 0 < count($this->groupList) ? ' GROUP BY ' . implode(', ', $this->groupList) . ' ' : '';
    }

    /**
     * Get Having clause.
     */
    public function getQueryHaving(): string
    {
        $return = '';

        if (0 < count($this->havingList)) {
            $return =  ' HAVING ';
            foreach ($this->havingList as $having) {
                $return .= $having . ' ';
            }
        }

        return $return;
    }

    public function resetGroup(): static
    {
        $this->havingList = [];
        $this->groupList  = [];

        return $this;
    }
}
