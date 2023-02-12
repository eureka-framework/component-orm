<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Eureka\Component\Orm\Query;

use Eureka\Component\Orm\Exception\EmptyWhereClauseException;
use Eureka\Component\Orm\Query\Interfaces\FieldAwareInterface;
use Eureka\Component\Orm\Query\Interfaces\GroupAwareInterface;
use Eureka\Component\Orm\Query\Interfaces\JoinAwareInterface;
use Eureka\Component\Orm\Query\Interfaces\LimitAwareInterface;
use Eureka\Component\Orm\Query\Interfaces\OrderAwareInterface;
use Eureka\Component\Orm\Query\Interfaces\WhereAwareInterface;
use Eureka\Component\Orm\Query\Traits;

/**
 * Class SelectBuilder
 *
 * @author Romain Cottard
 *
 * @template TRepository of \Eureka\Component\Orm\RepositoryInterface
 * @template TEntity of \Eureka\Component\Orm\EntityInterface
 * @implements FieldAwareInterface<TRepository>
 * @extends AbstractQueryBuilder<TRepository, TEntity>
 */
class SelectBuilder extends AbstractQueryBuilder implements
    FieldAwareInterface,
    GroupAwareInterface,
    JoinAwareInterface,
    LimitAwareInterface,
    OrderAwareInterface,
    WhereAwareInterface
{
    /** @use Traits\FieldAwareTrait<TRepository> */
    use Traits\FieldAwareTrait;
    use Traits\GroupAwareTrait;
    use Traits\JoinAwareTrait;
    use Traits\LimitAwareTrait;
    use Traits\OrderAwareTrait;
    use Traits\WhereAwareTrait;

    public function clear(bool $forNotCached = false): static
    {
        if (!$forNotCached) {
            $this->resetFields();
            $this->resetOrder();
        }

        $this->resetBind();
        $this->resetGroup();
        $this->resetWhere();
        $this->resetLimit();
        $this->resetJoin();

        return $this;
    }

    /**
     * @param bool $usePrefix
     * @param string $prefix
     * @param bool $onlyPrimaryKey
     * @return string
     * @throws EmptyWhereClauseException
     */
    public function getQuery(bool $usePrefix = false, string $prefix = '', bool $onlyPrimaryKey = false): string
    {
        return 'SELECT ' . $this->getQueryFields($this->repository, $usePrefix, $onlyPrimaryKey) .
            $this->getQueryFrom($this->repository) .
            $this->getQueryJoin() .
            $this->getQueryWhere() .
            $this->getQueryGroupBy() .
            $this->getQueryHaving() .
            $this->getQueryOrderBy() .
            $this->getQueryLimit();
    }
}
