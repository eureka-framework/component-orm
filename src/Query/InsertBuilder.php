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
use Eureka\Component\Orm\Query\Interfaces\FieldAwareInterface;
use Eureka\Component\Orm\Query\Interfaces\SetAwareInterface;
use Eureka\Component\Orm\Query\Traits;

/**
 * Class InsertBuilder
 *
 * @author Romain Cottard
 *
 * @template TRepository of \Eureka\Component\Orm\RepositoryInterface
 * @template TEntity of EntityInterface
 *
 * @implements FieldAwareInterface<TRepository>
 * @extends AbstractQueryBuilder<TRepository, TEntity>
 */
class InsertBuilder extends AbstractQueryBuilder implements FieldAwareInterface, SetAwareInterface
{
    /** @use Traits\FieldAwareTrait<TRepository> */
    use Traits\FieldAwareTrait;
    use Traits\SetAwareTrait;

    public function clear(): static
    {
        $this->resetBind();
        $this->resetFields();
        $this->resetSet();

        return $this;
    }

    /**
     * @param bool $onDuplicateUpdate
     * @param bool $onDuplicateIgnore
     * @return string
     * @throws EmptySetClauseException
     */
    public function getQuery(bool $onDuplicateUpdate = false, bool $onDuplicateIgnore = false): string
    {
        //~ Build query automatically based on entity
        if ($this->entity instanceof EntityInterface) {
            $this->clear();

            foreach ($this->repository->getFields() as $field) {
                $this->addSet($field, $this->repository->getEntityValue($this->entity, $field));
            }
        }

        $onDuplicateIgnoreClause = '';
        if ($onDuplicateIgnore) {
            $onDuplicateIgnoreClause = 'IGNORE ';
        }

        if ($onDuplicateUpdate) {
            $this->appendUpdateValues();
        }

        return 'INSERT ' . $onDuplicateIgnoreClause .
            'INTO ' . $this->repository->getTable() .
            $this->getQuerySet() .
            $this->getQueryDuplicateUpdate()
        ;
    }

    /**
     * Append update values
     *
     * @return static
     */
    private function appendUpdateValues(): static
    {
        //~ if entity is not set, skip auto append update value
        if ($this->entity === null) {
            return $this;
        }

        //~ List of primary key to exclude from update.
        $primaryKeys = $this->repository->getPrimaryKeys();

        //~ Check for updated fields.
        foreach ($this->repository->getFields() as $field) {
            $value = $this->repository->getEntityValue($this->entity, $field);

            if (in_array($field, $primaryKeys)) {
                continue;
            }

            $this->addUpdate($field, $value);
        }

        return $this;
    }
}
