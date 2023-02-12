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

/**
 * Class Factory
 *
 * @author Romain Cottard
 *
 * @template TRepository of RepositoryInterface
 * @template TEntity of EntityInterface
 */
class QueryBuilderFactory
{
    /**
     * @param TRepository $repository
     * @return QueryBuilder<TRepository, TEntity>
     */
    public function newQueryBuilder(RepositoryInterface $repository): QueryBuilder
    {
        return new QueryBuilder($repository);
    }

    /**
     * @param TRepository $repository
     * @return SelectBuilder<TRepository, TEntity>
     */
    public function newSelectBuilder(RepositoryInterface $repository): SelectBuilder
    {
        return new SelectBuilder($repository);
    }

    /**
     * @param TRepository $repository
     * @param TEntity|null $entity
     * @return DeleteBuilder<TRepository,TEntity>
     */
    public function newDeleteBuilder(RepositoryInterface $repository, ?EntityInterface $entity = null): DeleteBuilder
    {
        return new DeleteBuilder($repository, $entity);
    }

    /**
     * @param TRepository $repository
     * @param TEntity|null $entity
     * @return InsertBuilder<TRepository,TEntity>
     */
    public function newInsertBuilder(RepositoryInterface $repository, ?EntityInterface $entity = null): InsertBuilder
    {
        return new InsertBuilder($repository, $entity);
    }

    /**
     * @param TRepository $repository
     * @param TEntity|null $entity
     * @return UpdateBuilder<TRepository,TEntity>
     */
    public function newUpdateBuilder(RepositoryInterface $repository, ?EntityInterface $entity = null): UpdateBuilder
    {
        return new UpdateBuilder($repository, $entity);
    }
}
