<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eureka\Component\Orm\Query;

use Eureka\Component\Orm\Query\Traits;

class DeleteBuilder extends AbstractQueryBuilder
{
    use Traits\WhereTrait;

    /**
     * {@inheritdoc}
     */
    public function clear()
    {
        $this->resetBind();
        $this->resetWhere();

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getQuery()
    {
        if ($this->entity !== null) {
            //~ List of fields to update.
            $primaryKeys = $this->repository->getPrimaryKeys();

            //~ Check for updated fields.
            foreach ($this->repository->getFields() as $field) {

                if (!in_array($field, $primaryKeys)) {
                    continue;
                }

                $this->addWhere($field, $this->repository->getDataValue($this->entity, $field));
            }
        }

        return 'DELETE FROM ' . $this->repository->getTable() . $this->getQueryWhere(true);
    }
}
