<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eureka\Component\Orm\Query\Traits;

use Eureka\Component\Orm\RepositoryInterface;

/**
 * Class WhereTrait
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
     * @return $this
     */
    protected function resetField()
    {
        $this->fields = [];

        return $this;
    }

    /**
     * @return $this
     */
    public function enableCalculateFoundRows()
    {
        $this->calculateFoundRows = true;

        return $this;
    }

    /**
     * @return void
     */
    public function disableCalculateFoundRows()
    {
        $this->calculateFoundRows = false;
    }

    /**
     * @param  string $name
     * @param  string $alias
     * @return void
     */
    public function addField($name, $alias = '')
    {
        $this->fields[] = '`' . $name . '`' . (!empty($alias) ? ' AS ' . '`' . $alias . '`' : '');
    }

    /**
     * @param  string $name
     * @param  string $alias
     * @return void
     */
    public function addFrom($name, $alias = '')
    {
        $this->from = '`' . $name . '`' . (!empty($alias) ? ' AS ' . '`' . $alias . '`' : '');
    }

    /**
     * @param  string[] $fields
     * @return string
     */
    public function getQueryFieldsPersonalized($fields = [])
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
    public function getQueryFields(RepositoryInterface $repository, $isPrefixed = false, $onlyPrimaryKeys = false)
    {
        $fields         = [];
        $table          = $repository->getTable();
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
    public function getQueryFrom(RepositoryInterface $repository)
    {
        return ' FROM ' . $repository->getTable();
    }

    /**
     * Get FROM clause
     *
     * @return string
     */
    public function getQueryFromPersonalized()
    {
        return ' FROM ' . $this->from;
    }
}
