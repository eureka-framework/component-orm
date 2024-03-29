<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Eureka\Component\Orm\Query;

use Eureka\Component\Orm\Exception\OrmException;
use Eureka\Component\Orm\Query\Interfaces\WhereAwareInterface;

class DeleteBuilder extends AbstractQueryBuilder implements WhereAwareInterface
{
    use Traits\WhereAwareTrait;

    public function clear(): static
    {
        $this->resetBind();
        $this->resetWhere();

        return $this;
    }

    /**
     * @return string
     * @throws OrmException
     */
    public function getQuery(): string
    {
        if ($this->entity !== null) {
            //~ List of fields to update.
            $primaryKeys = $this->repository->getPrimaryKeys();

            //~ Check for updated fields.
            foreach ($this->repository->getFields() as $field) {
                if (!in_array($field, $primaryKeys)) {
                    continue;
                }

                $this->addWhere($field, $this->repository->getEntityValue($this->entity, $field));
            }
        }

        return 'DELETE FROM ' . $this->repository->getTable() . $this->getQueryWhere(true);
    }
}
