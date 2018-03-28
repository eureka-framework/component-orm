<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eureka\Component\Orm\Query;

use Eureka\Component\Orm\Query\Traits;

/**
 * Class Insert
 *
 * @author Romain Cottard
 */
class InsertBuilder extends AbstractQueryBuilder
{
    use Traits\WhereTrait, Traits\FieldTrait, Traits\SetTrait;

    /**
     * {@inheritdoc}
     */
    public function clear()
    {
        $this->resetBind();
        $this->resetField();
        $this->resetSet();

        return $this;
    }

    /**
     * {@inheritdoc}
     * @param bool $onDuplicateUpdate
     */
    public function getQuery($onDuplicateUpdate = false, $onDuplicateIgnore = false)
    {
        //~ Check for updated fields.
        foreach ($this->repository->getFields() as $field) {
            $this->addSet($field, $this->repository->getDataValue($this->entity, $field));
        }

        $onDuplicateIgnoreClause = '';
        if ($onDuplicateIgnore) {
            $onDuplicateIgnoreClause = 'IGNORE ';
        }

        if ($onDuplicateUpdate) {
            $this->appendUpdateValues();
        }

        return 'INSERT ' . $onDuplicateIgnoreClause . 'INTO ' . $this->repository->getTable() . $this->getQuerySet() . $this->getQueryDuplicateUpdate();
    }

    /**
     * Append update values
     *
     * @return $this
     */
    private function appendUpdateValues()
    {
        //~ List of fields to update.
        $primaryKeys = $this->repository->getPrimaryKeys();

        //~ Check for updated fields.
        foreach ($this->repository->getFields() as $field) {
            $value = $this->repository->getDataValue($this->entity, $field);

            if (in_array($field, $primaryKeys)) {
                continue;
            }

            $this->addUpdate($field, $value);
        }

        return $this;
    }
}
