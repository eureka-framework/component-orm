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
 * Trait JoinTrait
 *
 * @author Romain Cottard
 */
trait JoinTrait
{
    /** @var string[] $joinList List of [LEFT|RIGHT] JOIN table ON ... */
    protected array $joinList = [];

    /**
     * @param string $joinType
     * @param string $joinTable
     * @param string $mainField
     * @param string $mainAlias
     * @param string $joinField
     * @param string $joinAlias
     * @return $this|QueryBuilderInterface
     */
    public function addJoin(
        string $joinType,
        string $joinTable,
        string $mainField,
        string $mainAlias,
        string $joinField,
        string $joinAlias
    ): QueryBuilderInterface {

        $using    = 'ON ' . $mainAlias . '.' . $mainField . ' = ' . $joinAlias . '.' . $joinField;
        $this->joinList[]  = ' ' . $joinType . ' JOIN ' . $joinTable . ' AS ' . $joinAlias . ' ' . $using;

        return $this;
    }

    /**
     * @return bool
     */
    public function hasJoin(): bool
    {
        return (count($this->joinList) > 0);
    }

    /**
     * Get Set clause.
     *
     * @return string
     */
    public function getQueryJoin(): string
    {
        if (!$this->hasJoin()) {
            return '';
        }

        return implode(' ', $this->joinList) . ' ';
    }

    /**
     * @return self|QueryBuilderInterface
     */
    public function resetJoin(): QueryBuilderInterface
    {
        $this->joinList = [];

        return $this;
    }
}
