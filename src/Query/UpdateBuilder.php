<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Eureka\Component\Orm\Query;

use Eureka\Component\Orm\Exception\EmptySetClauseException;
use Eureka\Component\Orm\Exception\EmptyWhereClauseException;
use Eureka\Component\Orm\Query\Traits;

/**
 * Class UpdateBuilder
 *
 * @author Romain Cottard
 */
class UpdateBuilder extends AbstractQueryBuilder
{
    use Traits\WhereTrait;
    use Traits\FieldTrait;
    use Traits\SetTrait;

    /**
     * @return QueryBuilderInterface
     */
    public function clear(): QueryBuilderInterface
    {
        $this->resetBind();
        $this->resetFields();
        $this->resetSet();
        $this->resetWhere();

        return $this;
    }

    /**
     * @return string
     * @throws EmptySetClauseException
     * @throws EmptyWhereClauseException
     */
    public function getQuery(): string
    {
        //~ List of fields to update.
        $primaryKeys = $this->repository->getPrimaryKeys();

        //~ Check for updated fields.
        foreach ($this->repository->getFields() as $field) {
            $value = $this->repository->getEntityValue($this->entity, $field);

            if (in_array($field, $primaryKeys)) {
                $this->addWhere($field, $value);
                continue;
            }

            $this->addSet($field, $value);
        }

        return 'UPDATE ' . $this->repository->getTable() . $this->getQuerySet() . $this->getQueryWhere(true);
    }
}
