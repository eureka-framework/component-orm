<?php  declare(strict_types=1);

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eureka\Component\Orm\Query;

use Eureka\Component\Orm\Exception\EmptySetClauseException;
use Eureka\Component\Orm\Query\Traits;

/**
 * Class InsertBuilder
 *
 * @author Romain Cottard
 */
class InsertBuilder extends AbstractQueryBuilder
{
    use Traits\WhereTrait, Traits\FieldTrait, Traits\SetTrait;

    /**
     * Clear query params
     *
     * @return QueryBuilderInterface
     */
    public function clear(): QueryBuilderInterface
    {
        $this->resetBind();
        $this->resetField();
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
        //~ Check for updated fields.
        foreach ($this->repository->getFields() as $field) {
            $this->addSet($field, $this->repository->getEntityValue($this->entity, $field));
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
     * @return QueryBuilderInterface
     */
    private function appendUpdateValues(): QueryBuilderInterface
    {
        //~ List of fields to update.
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
