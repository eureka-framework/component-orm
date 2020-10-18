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
use Eureka\Component\Orm\Exception\OrmException;
use Eureka\Component\Orm\RepositoryInterface;

/**
 * Class Factory
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
     * @param  RepositoryInterface $repository
     * @param  ?EntityInterface $entity
     * @return DeleteBuilder|InsertBuilder|QueryBuilder|SelectBuilder|UpdateBuilder
     * @throws OrmException
     */
    public static function getBuilder(string$type, RepositoryInterface $repository, EntityInterface $entity = null)
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
