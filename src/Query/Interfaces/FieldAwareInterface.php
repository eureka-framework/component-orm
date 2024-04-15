<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Eureka\Component\Orm\Query\Interfaces;

use Eureka\Component\Orm\RepositoryInterface;

/**
 * @template TRepository of RepositoryInterface
 */
interface FieldAwareInterface
{
    public function enableCalculateFoundRows(): static;

    public function disableCalculateFoundRows(): static;

    public function addField(string $name, string $alias = '', bool $escape = true): void;

    public function setFrom(string $table, string $alias = ''): void;

    /**
     * Get fields to select
     *
     * @param  TRepository $repository
     * @param  bool $isPrefixed Add table prefix in list of field
     * @param  bool $onlyPrimaryKeys Get only primary key(s) field(s)
     * @return string
     */
    public function getQueryFields(
        RepositoryInterface $repository,
        bool $isPrefixed = false,
        bool $onlyPrimaryKeys = false,
    ): string;

    /**
     * @param  string[] $fields
     * @return string
     */
    public function getQueryFieldsPersonalized(array $fields = []): string;

    /**
     * Get fields to select
     *
     * @param TRepository $repository
     * @param bool $isPrefixed
     * @param bool $onlyPrimaryKeys
     * @param string|null $aliasPrefix
     * @param string|null $aliasSuffix
     * @return string[]
     */
    public function getQueryFieldsList(
        RepositoryInterface $repository,
        bool $isPrefixed = false,
        bool $onlyPrimaryKeys = false,
        ?string $aliasPrefix = null,
        ?string $aliasSuffix = null,
    ): array;

    /**
     * Get FROM clause
     *
     * @param  TRepository $repository
     * @return string
     */
    public function getQueryFrom(RepositoryInterface $repository): string;

    /**
     * Get FROM clause
     *
     * @return string
     */
    public function getQueryFromPersonalized(): string;
}
