<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eureka\Component\Orm\Query;

use Eureka\Component\Orm\Query\Traits;

class SelectBuilder extends AbstractQueryBuilder
{
    use Traits\FieldTrait, Traits\GroupTrait, Traits\LimitTrait, Traits\OrderTrait, Traits\WhereTrait;

    /**
     * {@inheritdoc}
     */
    public function clear($forNotCached = false)
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
     * {@inheritdoc}
     */
    public function getQuery($usePrefix = false, $prefix = '')
    {
        return 'SELECT ' . $this->getQueryFields($this->repository, $usePrefix, $prefix) .
            $this->getQueryFrom($this->repository) .
            $this->getQueryWhere() .
            $this->getQueryGroupBy() .
            $this->getQueryHaving() .
            $this->getQueryOrderBy() .
            $this->getQueryLimit();
    }
}
