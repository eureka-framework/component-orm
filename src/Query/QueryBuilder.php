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

class QueryBuilder extends SelectBuilder
{
    /**
     * @param bool $usePrefix
     * @param string $prefix
     * @param bool $onlyPrimaryKey
     * @return string
     * @throws EmptyWhereClauseException
     */
    public function getQuery(bool $usePrefix = false, string $prefix = '', bool $onlyPrimaryKey = false): string
    {
        return 'SELECT ' . $this->getQueryFieldsPersonalized()
            . $this->getQueryFromPersonalized()
            . $this->getQueryWhere()
            . $this->getQueryGroupBy()
            . $this->getQueryHaving()
            . $this->getQueryOrderBy()
            . $this->getQueryLimit()
        ;
    }

    /**
     * Count number of results for query.
     *
     * @param string $field
     * @return string
     * @throws EmptyWhereClauseException
     */
    public function getQueryCount(string $field = '*'): string
    {
        return 'SELECT COUNT(' . $field . ') AS nb_results'
            . $this->getQueryFrom($this->repository)
            . $this->getQueryWhere()
            . $this->getQueryGroupBy()
        ;
    }
}
