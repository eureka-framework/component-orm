<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eureka\Component\Orm\Query;

use Eureka\Component\Orm\EntityInterface;
use Eureka\Component\Orm\Exception\OrmException;
use Eureka\Component\Orm\RepositoryInterface;

/**
 * Class Builder
 *
 * @author Romain Cottard
 */
class Factory
{
    const TYPE_SELECT = 'select';
    const TYPE_INSERT = 'insert';
    const TYPE_UPDATE = 'update';
    const TYPE_DELETE = 'delete';
    const TYPE_QUERY  = 'query';

    /**
     * @param  string $type
     * @param  \Eureka\Component\Orm\RepositoryInterface $repository
     * @param  \Eureka\Component\Orm\EntityInterface $entity
     * @return \Eureka\Component\Orm\Query\DeleteBuilder|\Eureka\Component\Orm\Query\InsertBuilder|\Eureka\Component\Orm\Query\QueryBuilder|\Eureka\Component\Orm\Query\SelectBuilder|\Eureka\Component\Orm\Query\UpdateBuilder
     * @throws \Eureka\Component\Orm\Exception\OrmException
     */
    public static function getBuilder($type, RepositoryInterface $repository, EntityInterface $entity = null)
    {
        switch ($type) {
            case self::TYPE_SELECT:
                return new SelectBuilder($repository, $entity);
            case self::TYPE_INSERT:
                return new InsertBuilder($repository, $entity);
            case self::TYPE_UPDATE:
                return new UpdateBuilder($repository, $entity);
            case self::TYPE_DELETE:
                return new DeleteBuilder($repository, $entity);
            case self::TYPE_QUERY:
                return new QueryBuilder($repository, $entity);
            default:
                throw new OrmException('Unknown query builder type!');
        }
    }
}
