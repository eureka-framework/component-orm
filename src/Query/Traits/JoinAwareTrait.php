<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Eureka\Component\Orm\Query\Traits;

/**
 * Trait JoinTrait
 *
 * @author Romain Cottard
 */
trait JoinAwareTrait
{
    /** @var string[] $joinList List of (LEFT|RIGHT|INNER) JOIN table ON ... */
    protected array $joinList = [];

    public function addJoin(
        string $joinType,
        string $joinTable,
        string $mainField,
        string $mainAlias,
        string $joinField,
        string $joinAlias
    ): static {

        $using    = 'ON ' . $mainAlias . '.' . $mainField . ' = ' . $joinAlias . '.' . $joinField;
        $this->joinList[]  = ' ' . $joinType . ' JOIN ' . $joinTable . ' AS ' . $joinAlias . ' ' . $using;

        return $this;
    }

    public function hasJoin(): bool
    {
        return \count($this->joinList) > 0;
    }

    public function getQueryJoin(): string
    {
        if (!$this->hasJoin()) {
            return '';
        }

        return \implode(' ', $this->joinList) . ' ';
    }

    public function resetJoin(): static
    {
        $this->joinList = [];

        return $this;
    }
}
