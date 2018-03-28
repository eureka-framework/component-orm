<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eureka\Component\Orm\Query;

use Eureka\Component\Orm\Query\Traits;

class UpdateBuilder extends AbstractQueryBuilder
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
        $this->resetWhere();

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getQuery()
    {
        //~ List of fields to update.
        $primaryKeys = $this->repository->getPrimaryKeys();

        //~ Check for updated fields.
        foreach ($this->repository->getFields() as $field) {
            $value = $this->repository->getDataValue($this->entity, $field);

            if (in_array($field, $primaryKeys)) {
                $this->addWhere($field, $value);
                continue;
            }

            $this->addSet($field, $value);
        }

        return 'UPDATE ' . $this->repository->getTable() . $this->getQuerySet() . $this->getQueryWhere(true);
    }
}
