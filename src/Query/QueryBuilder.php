<?php declare(strict_types=1);

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eureka\Component\Orm\Query;

use Eureka\Component\Orm\Exception\EmptyWhereClauseException;

class QueryBuilder extends SelectBuilder
{
    /**
     * @param bool $usePrefix
     * @param string $prefix
     * @return string
     * @throws EmptyWhereClauseException
     */
    public function getQuery(bool $usePrefix = false, string $prefix = ''): string
    {
        return 'SELECT ' . $this->getQueryFieldsPersonalized() .
            $this->getQueryFromPersonalized() .
            $this->getQueryWhere() .
            $this->getQueryGroupBy() .
            $this->getQueryHaving() .
            $this->getQueryOrderBy() .
            $this->getQueryLimit();
    }

    /**
     * Count number of results for query.
     *
     * @param string $field
     * @return int
     * @throws
     */
    public function getQueryCount($field = '*')
    {
        return 'SELECT COUNT(`' . $field . '`) AS NB_RESULTS FROM ' . $this->getQueryFromPersonalized() . ' ' . $this->getQueryWhere();
    }
}
