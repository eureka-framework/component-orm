<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Eureka\Component\Orm\Query\Interfaces;

use Eureka\Component\Orm\Exception\OrmException;

interface QueryBuilderInterface
{
    /**
     * Clear query params
     */
    public function clear(): static;

    /**
     * @param  string $field
     * @param  string|int|float|bool|null $value
     * @param  bool $isUnique
     * @return string Return bind name field
     */
    public function bind(string $field, string|int|float|bool|null $value, bool $isUnique = false): string;

    /**
     * @return array<string|int|float|bool|null>
     */
    public function getAllBind(): array;

    /**
     * @return string
     * @throws OrmException
     */
    public function getQuery(): string;

    /**
     * Get indexed by
     *
     * @return string
     */
    public function getListIndexedByField(): string;

    /**
     * Set indexed by
     *
     * @param  string $field
     * @return static
     */
    public function setListIndexedByField(string $field): static;
}
