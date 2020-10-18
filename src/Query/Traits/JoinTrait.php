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
     * @param string $type
     * @param string $table
     * @param string $leftField
     * @param string $leftPrefix
     * @param string $rightField
     * @param string $rightPrefix
     * @return self|QueryBuilderInterface
     */
    public function addJoin(
        string $type,
        string $table,
        string $leftField,
        string $leftPrefix,
        string $rightField,
        string $rightPrefix
    ): QueryBuilderInterface {

        $using    = 'ON ' . $leftPrefix . '.' . $leftField . ' = ' . $rightPrefix . '.' . $rightField;
        $this->joinList[]  = ' ' . $type . ' JOIN ' . $table . ' AS ' . $rightPrefix . ' ' . $using;

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
