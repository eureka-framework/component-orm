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
use Eureka\Component\Orm\RepositoryInterface;

class QueryBuilderFactory
{
    public function newQueryBuilder(RepositoryInterface $repository): QueryBuilder
    {
        return new QueryBuilder($repository);
    }

    public function newSelectBuilder(RepositoryInterface $repository): SelectBuilder
    {
        return new SelectBuilder($repository);
    }

    public function newDeleteBuilder(RepositoryInterface $repository, ?EntityInterface $entity = null): DeleteBuilder
    {
        return new DeleteBuilder($repository, $entity);
    }

    public function newInsertBuilder(RepositoryInterface $repository, ?EntityInterface $entity = null): InsertBuilder
    {
        return new InsertBuilder($repository, $entity);
    }

    public function newUpdateBuilder(RepositoryInterface $repository, ?EntityInterface $entity = null): UpdateBuilder
    {
        return new UpdateBuilder($repository, $entity);
    }
}
