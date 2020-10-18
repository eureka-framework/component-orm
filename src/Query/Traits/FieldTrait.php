<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Eureka\Component\Orm\Query\Traits;

use Eureka\Component\Orm\Query\QueryBuilderInterface;
use Eureka\Component\Orm\RepositoryInterface;

/**
 * Trait FieldTrait
 *
 * @author Romain Cottard
 */
trait FieldTrait
{
    /** @var string[] $fields */
    private array $fields = [];

    /** @var string $from */
    private string $from = '';

    /** @var bool $calculateFoundRows */
    private bool $calculateFoundRows = false;

    /**
     * @return self|QueryBuilderInterface
     */
    protected function resetFields(): QueryBuilderInterface
    {
        $this->fields = [];

        return $this;
    }

    /**
     * @return self|QueryBuilderInterface
     */
    public function enableCalculateFoundRows(): QueryBuilderInterface
    {
        $this->calculateFoundRows = true;

        return $this;
    }

    /**
     * @return self|QueryBuilderInterface
     */
    public function disableCalculateFoundRows(): QueryBuilderInterface
    {
        $this->calculateFoundRows = false;

        return $this;
    }

    /**
     * @param  string $name
     * @param  string $alias
     * @param  bool $escape
     * @return void
     */
    public function addField(string $name, string $alias = '', bool $escape = true): void
    {
        $name = $escape ? '`' . $name . '`' : $name;
        $this->fields[] = $name . (!empty($alias) ? ' AS ' . '`' . $alias . '`' : '');
    }

    /**
     * @param  string $name
     * @param  string $alias
     * @return void
     */
    public function addFrom(string $name, string $alias = ''): void
    {
        $this->from = '`' . $name . '`' . (!empty($alias) ? ' AS ' . '`' . $alias . '`' : '');
    }

    /**
     * @param  string[] $fields
     * @return string
     */
    public function getQueryFieldsPersonalized(array $fields = []): string
    {
        if (empty($fields) || !is_array($fields)) {
            $fields = $this->fields;
        }

        if (!empty($fields) && is_array($fields)) {
            foreach ($fields as $field => $alias) {
                $this->addField($field, $alias);
            }
        }

        return ($this->calculateFoundRows ? 'SQL_CALC_FOUND_ROWS ' : '') . implode(', ', $this->fields);
    }

    /**
     * Get fields to select
     *
     * @param  RepositoryInterface $repository
     * @param  bool $isPrefixed Add table prefix in list of field
     * @param  bool $onlyPrimaryKeys Get only primary key(s) field(s)
     * @return string
     */
    public function getQueryFields(
        RepositoryInterface $repository,
        bool $isPrefixed = false,
        bool $onlyPrimaryKeys = false
    ): string {

        $calc = ($this->calculateFoundRows ? 'SQL_CALC_FOUND_ROWS ' : '');

        if (!empty($this->fields)) {
            $fields = $this->fields;
        } else {
            $fields = $this->getQueryFieldsList($repository, $isPrefixed, $onlyPrimaryKeys);
        }

        return $calc . implode(', ', $fields);
    }

    /**
     * Get fields to select
     *
     * @param RepositoryInterface $repository
     * @param bool $isPrefixed
     * @param bool $onlyPrimaryKeys
     * @param string|null $aliasPrefix
     * @param string|null $aliasSuffix
     * @return array
     */
    public function getQueryFieldsList(
        RepositoryInterface $repository,
        bool $isPrefixed = false,
        bool $onlyPrimaryKeys = false,
        ?string $aliasPrefix = null,
        ?string $aliasSuffix = null
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
     * @param  RepositoryInterface $repository
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
