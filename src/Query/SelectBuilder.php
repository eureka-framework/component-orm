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
use Eureka\Component\Orm\Query\Traits;

/**
 * Class SelectBuilder
 *
 * @author Romain Cottard
 */
class SelectBuilder extends AbstractQueryBuilder
{
    use Traits\FieldTrait;
    use Traits\GroupTrait;
    use Traits\LimitTrait;
    use Traits\OrderTrait;
    use Traits\WhereTrait;
    use Traits\JoinTrait;

    /**
     * @param bool $forNotCached
     * @return QueryBuilderInterface
     */
    public function clear(bool $forNotCached = false): QueryBuilderInterface
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
    public function getQuery(bool $usePrefix = false, string $prefix = '', $onlyPrimaryKey = false): string
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
