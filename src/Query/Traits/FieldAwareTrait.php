<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Eureka\Component\Orm\Query\Traits;

use Eureka\Component\Orm\RepositoryInterface;

/**
 * @template TRepository of RepositoryInterface
 */
trait FieldAwareTrait
{
    /** @var string[] $fields */
    private array $fields = [];

    /** @var string $from */
    private string $from = '';

    /** @var bool $calculateFoundRows */
    private bool $calculateFoundRows = false;

    protected function resetFields(): static
    {
        $this->fields = [];

        return $this;
    }

    public function enableCalculateFoundRows(): static
    {
        $this->calculateFoundRows = true;

        return $this;
    }

    public function disableCalculateFoundRows(): static
    {
        $this->calculateFoundRows = false;

        return $this;
    }

    public function addField(string $name, string $alias = '', bool $escape = true): void
    {
        $name = $escape ? '`' . $name . '`' : $name;
        $this->fields[] = $name . (!empty($alias) ? ' AS ' . '`' . $alias . '`' : '');
    }

    public function setFrom(string $table, string $alias = ''): void
    {
        $this->from = '`' . $table . '`' . (!empty($alias) ? ' AS ' . '`' . $alias . '`' : '');
    }

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
    ): string {

        $calc = ($this->calculateFoundRows ? 'SQL_CALC_FOUND_ROWS ' : '');

        if (!empty($this->fields)) {
            $fields = $this->fields;
        } else {
            $fields = $this->getQueryFieldsList($repository, $isPrefixed, $onlyPrimaryKeys);
        }

        return $calc . \implode(', ', $fields);
    }

    /**
     * @param  string[] $fields
     * @return string
     */
    public function getQueryFieldsPersonalized(array $fields = []): string
    {
        if (!empty($fields)) {
            foreach ($fields as $field => $alias) {
                $this->addField($field, $alias);
            }
        }

        return ($this->calculateFoundRows ? 'SQL_CALC_FOUND_ROWS ' : '') . \implode(', ', $this->fields);
    }

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
    ): array {

        $fields = $onlyPrimaryKeys ? $repository->getPrimaryKeys() : $repository->getFields();

        if ($isPrefixed) {
            $table          = !empty($aliasPrefix) ? $aliasPrefix : $repository->getTable();
            $fields         = [];
            $fieldsToPrefix = $onlyPrimaryKeys ? $repository->getPrimaryKeys() : $repository->getFields();

            foreach ($fieldsToPrefix as $field) {
                $fields[] = $table . '.' . $field . (!empty($aliasSuffix) ? ' AS ' . $field . $aliasSuffix : '');
            }
        }

        return $fields;
    }

    /**
     * Get FROM clause
     *
     * @param  TRepository $repository
     * @return string
     */
    public function getQueryFrom(RepositoryInterface $repository): string
    {
        return ' FROM ' . $repository->getTable();
    }

    /**
     * Get FROM clause
     *
     * @return string
     */
    public function getQueryFromPersonalized(): string
    {
        return ' FROM ' . $this->from;
    }
}
