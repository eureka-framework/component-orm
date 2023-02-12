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
use Eureka\Component\Orm\Exception\EmptyWhereClauseException;
use Eureka\Component\Orm\Exception\InvalidQueryException;

interface WhereAwareInterface
{
    /**
     * Add In list item.
     *
     * @param  string $field Field name
     * @param  array<string|int|float|bool|null> $values List of values
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
    ): static;

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
    ): static;

    /**
     * Add where clause (raw mode).
     *
     * @param  string $where
     * @param  ClauseConcat $whereConcat
     * @return static
     */
    public function addWhereRaw(string $where, ClauseConcat $whereConcat = ClauseConcat::And): static;

    /**
     * Add where clause.
     *
     * @param  string[] $keys
     * @param  Operator $operator
     * @param  ClauseConcat $whereConcat
     * @return static
     */
    public function addWhereKeysOr(
        array $keys,
        Operator $operator = Operator::Equal,
        ClauseConcat $whereConcat = ClauseConcat::Or
    ): static;

    /**
     * Get Where clause.
     *
     * @param  bool $throwExceptionForEmptyWhere
     * @return string
     * @throws EmptyWhereClauseException
     */
    public function getQueryWhere(bool $throwExceptionForEmptyWhere = false): string;
}
