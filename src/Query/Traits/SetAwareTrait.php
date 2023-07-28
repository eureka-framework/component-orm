<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Eureka\Component\Orm\Query\Traits;

use Eureka\Component\Orm\Exception\EmptySetClauseException;

/**
 * Class SetTrait
 *
 * @author Romain Cottard
 */
trait SetAwareTrait
{
    /** @var string[] $setList List of set for current query (update or insert) */
    protected array $setList = [];

    /** @var string[] $updateList List of set for current query (update or insert) */
    protected array $updateList = [];

    public function addSet(string $field, string|int|float|bool|null $value): static
    {
        $bindName = $this->bind($field, $value, true);

        $this->setList[] = '`' . $field . '` = ' . $bindName;

        return $this;
    }

    public function addUpdate(string $field, string|int|float|bool|null $value): static
    {
        $bindName = $this->bind($field, $value, true);

        $this->updateList[] = '`' . $field . '` = ' . $bindName;

        return $this;
    }

    /**
     * @throws EmptySetClauseException
     */
    public function getQuerySet(): string
    {
        if (0 === \count($this->setList)) {
            throw new EmptySetClauseException();
        }

        return ' SET ' . \implode(', ', $this->setList);
    }

    /**
     * Get on duplicate update clause.
     */
    public function getQueryDuplicateUpdate(): string
    {
        if (empty($this->updateList)) {
            return '';
        }

        return ' ON DUPLICATE KEY UPDATE ' . \implode(', ', $this->updateList);
    }

    public function resetSet(): static
    {
        $this->setList    = [];
        $this->updateList = [];

        return $this;
    }
}
