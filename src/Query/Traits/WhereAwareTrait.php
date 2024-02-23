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
use Eureka\Component\Orm\Exception\EmptyWhereClauseException;
use Eureka\Component\Orm\Exception\InvalidQueryException;

/**
 * Class WhereTrait
 *
 * @author Romain Cottard
 */
trait WhereAwareTrait
{
    /** @var string[] $whereList List of where restriction for current query */
    protected array $whereList = [];

    /**
     * Add In list item.
     *
     * @param  string $field Field name
     * @param  array<string|int|float|bool|null> $values List of values (integer)
     * @param  ClauseConcat $whereConcat Concat type with other where elements
     * @param  bool $not Whether the condition should be NOT IN instead of IN
     * @return static
     * @throws InvalidQueryException
     */
    public function addIn(
        string $field,
        array $values,
        ClauseConcat $whereConcat = ClauseConcat::And,
        bool $not = false
    ): static {
        if (empty($values)) {
            throw new InvalidQueryException('Values for addIn must be non empty!');
        }

        $field = (0 < \count($this->whereList) ? ' ' . $whereConcat->value . ' ' . $field : $field);

        //~ Bind values (more safety)
        $fields = [];
        foreach ($values as $value) {
            $bindName = $this->bind('value', $value, true);
            $fields[] = $bindName;
        }

        $this->whereList[] = $field . ($not ? ' NOT' : '') . ' IN (' . \implode(',', $fields) . ')';

        return $this;
    }

    /**
     * Add where clause.
     *
     * @param  string $field
     * @param  string|int|float|bool|null $value
     * @param  Operator $operator
     * @param  ClauseConcat $whereConcat
     * @param  string $prefix
     * @return static
     */
    public function addWhere(
        string $field,
        string|int|float|bool|null $value,
        Operator $operator = Operator::Equal,
        ClauseConcat $whereConcat = ClauseConcat::And,
        string $prefix = ''
    ): static {
        $fieldWithPrefix = !empty($prefix) ? $prefix . '.' . $field : $field;
        if (0 < \count($this->whereList)) {
            $fieldWhere = $whereConcat->value . ' ' . $fieldWithPrefix;
        } else {
            $fieldWhere = $fieldWithPrefix;
        }

        if ($operator === Operator::Regexp) {
            $bindName = "'" . \addslashes((string) $value) . "'";
        } else {
            $bindName = $this->bind($field, $value, true);
        }

        $this->whereList[] = $fieldWhere . ' ' . $operator->value . ' ' . $bindName;

        return $this;
    }

    /**
     * Add where clause (raw mode).
     *
     * @param  string $where
     * @param  ClauseConcat $whereConcat
     * @return static
     */
    public function addWhereRaw(string $where, ClauseConcat $whereConcat = ClauseConcat::And): static
    {
        $fieldWhere        = (0 < \count($this->whereList) ? ' ' . $whereConcat->value . ' ' . $where : $where);
        $this->whereList[] = $fieldWhere;

        return $this;
    }

    /**
     * Add where clause.
     *
     * @param  array<string|int|float|bool|null> $keys
     * @param  Operator $operator
     * @param  ClauseConcat $whereConcat
     * @return static
     */
    public function addWhereKeysOr(
        array $keys,
        Operator $operator = Operator::Equal,
        ClauseConcat $whereConcat = ClauseConcat::Or
    ): static {
        $whereList = [];

        foreach ($keys as $field => $value) {
            $bindName = $this->bind($field, $value, true);

            $whereList[] = $field . ' ' . $operator->value . ' ' . $bindName;
        }

        $fieldWhere = ' (' . \implode(' AND ', $whereList) . ') ';
        $fieldWhere = (0 < count($this->whereList) ? ' ' . $whereConcat->value . ' ' . $fieldWhere : $fieldWhere);

        $this->whereList[] = $fieldWhere;

        return $this;
    }

    /**
     * Get Where clause.
     *
     * @param  bool $throwExceptionForEmptyWhere
     * @return string
     * @throws EmptyWhereClauseException
     */
    public function getQueryWhere(bool $throwExceptionForEmptyWhere = false): string
    {
        $return = '';

        if (0 < \count($this->whereList)) {
            $return = ' WHERE ' . implode(' ', $this->whereList) . ' ';
        } elseif ($throwExceptionForEmptyWhere) {
            throw new EmptyWhereClauseException();
        }

        return $return;
    }

    /**
     * @return static
     */
    public function resetWhere(): static
    {
        $this->whereList = [];

        return $this;
    }
}
