<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eureka\Component\Orm\Query;

class QueryBuilder extends SelectBuilder
{
    /**
     * {@inheritdoc}
     */
    public function getQuery($usePrefix = false, $prefix = '')
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
