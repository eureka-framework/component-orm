<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Eureka\Component\Orm\Query;

use Eureka\Component\Orm\EntityInterface;
use Eureka\Component\Orm\Exception\EmptySetClauseException;
use Eureka\Component\Orm\Exception\EmptyWhereClauseException;
use Eureka\Component\Orm\Exception\InvalidQueryException;
use Eureka\Component\Orm\Query\Interfaces\FieldAwareInterface;
use Eureka\Component\Orm\Query\Interfaces\WhereAwareInterface;
use Eureka\Component\Orm\Query\Traits;
use Eureka\Component\Orm\RepositoryInterface;

/**
 * Class UpdateBuilder
 *
 * @author Romain Cottard
 *
 * @template TRepository of RepositoryInterface
 * @template TEntity of EntityInterface
 * @extends AbstractQueryBuilder<TRepository, TEntity>
 */
class UpdateBuilder extends AbstractQueryBuilder implements FieldAwareInterface, WhereAwareInterface
{
    use Traits\FieldAwareTrait;
    use Traits\SetAwareTrait;
    use Traits\WhereAwareTrait;

    public function clear(): static
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
     * @throws InvalidQueryException
     */
    public function getQuery(): string
    {
        if ($this->entity === null) {
            throw new InvalidQueryException('Entity must be given to perform an update!');
        }

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
