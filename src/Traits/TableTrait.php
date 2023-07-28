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

    /** @var array<string, array<string, string>> $entityNamesMap */
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
     * @return array<string, string>
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

    /**
     * @param array<string, array<string, string>> $nameMap
     * @return static
     */
    protected function setNamesMap(array $nameMap): static
    {
        $this->entityNamesMap = $nameMap;

        return $this;
    }

    /**
     * Set fields for mapper.
     *
     * @param  string[] $fields
     * @return static
     */
    protected function setFields(array $fields = []): static
    {
        $this->fields = $fields;

        return $this;
    }

    /**
     * Set primary keys.
     *
     * @param  string[] $primaryKeys
     * @return static
     */
    protected function setPrimaryKeys(array $primaryKeys): static
    {
        $this->primaryKeys = $primaryKeys;

        return $this;
    }

    /**
     * Set table name.
     *
     * @param  string $table
     * @return static
     */
    protected function setTable(string $table): static
    {
        $this->table = $table;

        return $this;
    }
}
