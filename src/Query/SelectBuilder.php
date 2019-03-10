<?php declare(strict_types=1);

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eureka\Component\Orm\Query;

use Eureka\Component\Orm\Exception\EmptyWhereClauseException;
use Eureka\Component\Orm\Query\Traits;

class SelectBuilder extends AbstractQueryBuilder
{
    use Traits\FieldTrait, Traits\GroupTrait, Traits\LimitTrait, Traits\OrderTrait, Traits\WhereTrait;

    /**
     * @param bool $forNotCached
     * @return QueryBuilderInterface
     */
    public function clear(bool $forNotCached = false): QueryBuilderInterface
    {
        if (!$forNotCached) {
            $this->resetField();
            $this->resetOrder();
        }

        $this->resetBind();
        $this->resetGroup();
        $this->resetWhere();
        $this->resetLimit();

        return $this;
    }

    /**
     * @param bool $usePrefix
     * @param string $prefix
     * @return string
     * @throws EmptyWhereClauseException
     */
    public function getQuery(bool $usePrefix = false, string $prefix = ''): string
    {
        return 'SELECT ' . $this->getQueryFields($this->repository, $usePrefix) .
            $this->getQueryFrom($this->repository) .
            $this->getQueryWhere() .
            $this->getQueryGroupBy() .
            $this->getQueryHaving() .
            $this->getQueryOrderBy() .
            $this->getQueryLimit();
    }
}
