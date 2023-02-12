<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Eureka\Component\Orm\Traits;

/**
 * Table trait for data related to the database table.
 *
 * @author Romain Cottard
 */
trait TableTrait
{
    /** @var string $table */
    protected string $table = '';

    /** @var string[] $fields */
    protected array $fields = [];

    /** @var string[] $primaryKeys */
    protected array $primaryKeys = [];

    /** @var string[][] $entityNamesMap */
    protected array $entityNamesMap = [];

    /**
     * Get fields for Mapper
     *
     * @return string[]
     */
    public function getFields(): array
    {
        return $this->fields;
    }

    /**
     * Get primary keys.
     *
     * @return string[]
     */
    public function getPrimaryKeys(): array
    {
        return $this->primaryKeys;
    }

    /**
     * Get table name.
     *
     * @return string
     */
    public function getTable(): string
    {
        return $this->table;
    }

    /**
     * @param string $field
     * @return array<mixed>
     */
    public function getNamesMap(string $field): array
    {
        if (!isset($this->entityNamesMap[$field])) {
            throw new \OutOfRangeException('Specified field does not exist in data names map');
        }

        return $this->entityNamesMap[$field];
    }

    /**
     * Return getter name for a field.
     *
     * @param  string $field
     * @return string
     * @throws \OutOfRangeException
     */
    public function getGetterForField(string $field): string
    {
        return $this->getNamesMap($field)['get'];
    }

    /**
     * Return setter name for a field.
     *
     * @param  string $field
     * @return string
     * @throws \OutOfRangeException
     */
    public function getSetterForField(string $field): string
    {
        return $this->getNamesMap($field)['set'];
    }

    /**
     * Return property name for a field.
     *
     * @param  string $field
     * @return string
     * @throws \OutOfRangeException
     */
    public function getPropertyForField(string $field): string
    {
        return $this->getNamesMap($field)['property'];
    }
}
