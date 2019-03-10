<?php declare(strict_types=1);

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
    private $fields = [];

    /** @var string $from */
    private $from = '';

    /** @var bool $calculateFoundRows */
    private $calculateFoundRows = false;

    /**
     * @return QueryBuilderInterface
     */
    protected function resetField(): QueryBuilderInterface
    {
        $this->fields = [];

        return $this;
    }

    /**
     * @return QueryBuilderInterface
     */
    public function enableCalculateFoundRows(): QueryBuilderInterface
    {
        $this->calculateFoundRows = true;

        return $this;
    }

    /**
     * @return void
     */
    public function disableCalculateFoundRows(): void
    {
        $this->calculateFoundRows = false;
    }

    /**
     * @param  string $name
     * @param  string $alias
     * @return void
     */
    public function addField(string $name, string $alias = ''): void
    {
        $this->fields[] = '`' . $name . '`' . (!empty($alias) ? ' AS ' . '`' . $alias . '`' : '');
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
    public function getQueryFields(RepositoryInterface $repository, bool $isPrefixed = false, bool $onlyPrimaryKeys = false): string
    {
        $fields = [];
        $table = $repository->getTable();
        $fieldsForQuery = $onlyPrimaryKeys ? $repository->getPrimaryKeys() : $repository->getFields();

        foreach ($fieldsForQuery as $field) {
            $fields[] = ($isPrefixed ? '`' . $table . '`.' : '') . '`' . $field . '`';
        }

        return ($this->calculateFoundRows ? 'SQL_CALC_FOUND_ROWS ' : '') . implode(', ', $fields);
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
