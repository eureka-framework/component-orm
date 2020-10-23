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
 * Class GroupTrait
 *
 * @author Romain Cottard
 */
trait GroupTrait
{
    /** @var string[] $groupList List of groupBy for current query */
    protected array $groupList = [];

    /** @var string[] $havingList List of having restriction for current query */
    protected array $havingList = [];

    /**
     * @param  string $field
     * @param  mixed $value
     * @param  bool $isUnique
     * @return string Return bind name field
     */
    abstract public function addBind(string $field, $value, bool $isUnique = false): string;

    /**
     * Add groupBy clause.
     *
     * @param  string $field
     * @return self|QueryBuilderInterface
     */
    public function addGroupBy(string $field): QueryBuilderInterface
    {
        $this->groupList[] = $field;

        return $this;
    }

    /**
     * Add having clause.
     *
     * @param  string $field
     * @param  string|int $value
     * @param  string $sign
     * @param  string $havingConcat
     * @return self|QueryBuilderInterface
     */
    public function addHaving(string $field, $value, string $sign = '=', string $havingConcat = 'AND'): QueryBuilderInterface
    {
        $fieldHaving = (0 < count($this->havingList) ? ' ' . $havingConcat . ' ' . $field : $field);

        $bindName = $this->addBind($field, $value, true);
        $this->havingList[] = $fieldHaving . ' ' . $sign . ' ' . $bindName;

        return $this;
    }

    /**
     * Get GroupBy clause.
     *
     * @return string
     */
    public function getQueryGroupBy(): string
    {
        return (0 < count($this->groupList) ? ' GROUP BY ' . implode(', ', $this->groupList) . ' ' : '');
    }

    /**
     * Get Having clause.
     *
     * @return string
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

    /**
     * @return self|QueryBuilderInterface
     */
    public function resetGroup(): QueryBuilderInterface
    {
        $this->havingList = [];
        $this->groupList  = [];

        return $this;
    }
}
