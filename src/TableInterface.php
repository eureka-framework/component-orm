<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Eureka\Component\Orm;

/**
 * Table trait for data related to the database table.
 *
 * @author  Romain Cottard
 */
interface TableInterface
{
    /**
     * Return fields for current table.
     *
     * @return string[]
     */
    public function getFields(): array;

    /**
     * Return the primary keys
     *
     * @return string[]
     */
    public function getPrimaryKeys(): array;

    /**
     * Return a map of names (set, get and property) for a db field
     *
     * @param  string $field
     * @return string[]
     * @throws \OutOfRangeException
     */
    public function getNamesMap(string $field): array;

    /**
     * Return getter name for a field.
     *
     * @param  string $field
     * @return string
     * @throws \OutOfRangeException
     */
    public function getGetterForField(string $field): string;

    /**
     * Return setter name for a field.
     *
     * @param  string $field
     * @return string
     * @throws \OutOfRangeException
     */
    public function getSetterForField(string $field): string;

    /**
     * Return property name for a field.
     *
     * @param  string $field
     * @return string
     * @throws \OutOfRangeException
     */
    public function getPropertyForField(string $field): string;

    /**
     * Return fields for current table.
     *
     * @return string
     */
    public function getTable(): string;
}
