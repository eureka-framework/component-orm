<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eureka\Component\Orm\Query\Traits;

/**
 * Class GroupTrait
 *
 * @author Romain Cottard
 */
trait GroupTrait
{
    /** @var string[] $groupList List of groupBy for current query */
    protected $groupList = [];

    /** @var string[] $havingList List of having restriction for current query */
    protected $havingList = [];

    /**
     * @param  string $field
     * @param  mixed $value
     * @param  bool $isUnique
     * @return string Return bind name field
     */
    abstract public function addBind($field, $value, $isUnique = false);

    /**
     * Add groupBy clause.
     *
     * @param  string $field
     * @return $this
     */
    public function addGroupBy($field)
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
     * @return $this
     */
    public function addHaving($field, $value, $sign = '=', $havingConcat = 'AND')
    {
        $fieldHaving = (0 < count($this->havingList) ? ' ' . $havingConcat . ' ' . $field : $field);

        $bindName = $this->addBind($field, $value);
        $this->havingList[] = $fieldHaving . ' ' . $sign . $bindName;

        return $this;
    }

    /**
     * Get GroupBy clause.
     *
     * @return string
     */
    public function getQueryGroupBy()
    {
        return (0 < count($this->groupList) ? 'GROUP BY ' . implode(', ', $this->groupList) : '');
    }

    /**
     * Get Having clause.
     *
     * @return string
     */
    public function getQueryHaving()
    {
        $return = '';

        if (0 < count($this->havingList)) {
            $return = 'HAVING ';
            foreach ($this->havingList as $having) {
                $return .= $having . ' ';
            }
        }

        return $return;
    }

    /**
     * @return $this
     */
    public function resetGroup()
    {
        $this->havingList = [];
        $this->groupList  = [];

        return $this;
    }
}
