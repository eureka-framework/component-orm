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
use Eureka\Component\Orm\Query\QueryBuilderInterface;

/**
 * Class SetTrait
 *
 * @author Romain Cottard
 */
trait SetTrait
{
    /** @var string[] $setList List of set for current query (update or insert) */
    protected array $setList = [];

    /** @var string[] $setList List of set for current query (update or insert) */
    protected array $updateList = [];

    /**
     * @param  string $field
     * @param  mixed $value
     * @param  bool $isUnique
     * @return string Return bind name field
     */
    abstract public function addBind(string $field, $value, bool $isUnique = false): string;

    /**
     * Add set clause.
     *
     * @param string $field
     * @param string|int|null $value
     * @return self|QueryBuilderInterface
     */
    public function addSet(string $field, $value): QueryBuilderInterface
    {
        $bindName = $this->addBind($field, $value, true);

        $this->setList[] = '`' . $field . '` = ' . $bindName;

        return $this;
    }

    /**
     * Add set clause.
     *
     * @param  string $field
     * @param  string|int|null $value
     * @return self|QueryBuilderInterface
     */
    public function addUpdate(string $field, $value): QueryBuilderInterface
    {
        $bindName = $this->addBind($field, $value, true);

        $this->updateList[] = '`' . $field . '` = ' . $bindName;

        return $this;
    }

    /**
     * Get Set clause.
     *
     * @return string
     * @throws EmptySetClauseException
     */
    public function getQuerySet(): string
    {
        if (0 === count($this->setList)) {
            throw new EmptySetClauseException();
        }

        return ' SET ' . implode(', ', $this->setList);
    }

    /**
     * Get on duplicate update clause.
     *
     * @return string
     */
    public function getQueryDuplicateUpdate(): string
    {
        if (empty($this->updateList)) {
            return '';
        }

        return ' ON DUPLICATE KEY UPDATE ' . implode(', ', $this->updateList);
    }

    /**
     * @return self|QueryBuilderInterface
     */
    public function resetSet(): QueryBuilderInterface
    {
        $this->setList    = [];
        $this->updateList = [];

        return $this;
    }
}
