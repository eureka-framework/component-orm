<?php declare(strict_types=1);

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eureka\Component\Orm\Query\Traits;

use Eureka\Component\Orm\Exception\EmptyWhereClauseException;
use Eureka\Component\Orm\Exception\InvalidQueryException;
use Eureka\Component\Orm\Query\QueryBuilderInterface;

/**
 * Class WhereTrait
 *
 * @author Romain Cottard
 */
trait WhereTrait
{
    /** @var string[] $whereList List of where restriction for current query */
    protected $whereList = [];

    /**
     * @param  string $field
     * @param  mixed $value
     * @param  bool $isUnique
     * @return string Return bind name field
     */
    abstract public function addBind(string $field, $value, bool $isUnique = false): string;

    /**
     * Add In list item.
     *
     * @param  string $field Field name
     * @param  array $values List of values (integer)
     * @param  string $whereConcat Concat type with other where elements
     * @param  bool $not Whether the condition should be NOT IN instead of IN
     * @return self|QueryBuilderInterface
     * @throws InvalidQueryException
     */
    public function addIn(string $field, array $values, string $whereConcat = 'AND', bool $not = false): QueryBuilderInterface
    {
        if (!is_array($values) || count($values) === 0) {
            throw new InvalidQueryException('Values for addIn must be an array, and non empty!');
        }

        $field = (0 < count($this->whereList) ? ' ' . $whereConcat . ' ' . $field : $field);

        //~ Bind values (more safety)
        $fields = [];
        foreach ($values as $value) {

            $bindName = $this->addBind('value', $value, true);
            $fields[] = $bindName;
        }

        $this->whereList[] = $field . ($not ? ' NOT' : '') . ' IN (' . implode(',', $fields) . ')';

        return $this;
    }

    /**
     * Add where clause.
     *
     * @param  string $field
     * @param  string|int $value
     * @param  string $sign
     * @param  string $whereConcat
     * @param  string $prefix
     * @return self|QueryBuilderInterface
     */
    public function addWhere(string $field, $value, string $sign = '=', string $whereConcat = 'AND', $prefix = ''): QueryBuilderInterface
    {
        $fieldWithPrefix = !empty($prefix) ? $prefix . '.' . $field : $field;
        $fieldWhere = (0 < count($this->whereList) ? ' ' . $whereConcat . ' ' . $fieldWithPrefix : $fieldWithPrefix);

        $bindName = $this->addBind($field, $value, true);
        $this->whereList[] = $fieldWhere . ' ' . $sign . $bindName;

        return $this;
    }

    /**
     * Add where clause.
     *
     * @param  string[] $keys
     * @param  string $sign
     * @param  string $whereConcat
     * @return self|QueryBuilderInterface
     */
    public function addWhereKeysOr(array $keys, string $sign = '=', string $whereConcat = 'OR'): QueryBuilderInterface
    {
        $whereList = [];

        foreach ($keys as $field => $value) {
            $bindName = $this->addBind($field, $value, true);

            $whereList[] = $field . ' ' . $sign . ' ' . $bindName;
        }

        $fieldWhere = ' (' . implode(' AND ', $whereList) . ') ';
        $fieldWhere = (0 < count($this->whereList) ? ' ' . $whereConcat . ' ' . $fieldWhere : $fieldWhere);

        $this->whereList[] = $fieldWhere;

        return $this;
    }

    /**
     * Get Where clause.
     *
     * @param  bool $throwExceptionForEmptyWhere
     * @return string
     * @throws EmptyWhereClauseException
     */
    public function getQueryWhere(bool $throwExceptionForEmptyWhere = false): string
    {
        $return = '';

        if (0 < count($this->whereList)) {
            $return = ' WHERE ';
            foreach ($this->whereList as $where) {
                $return .= $where . ' ';
            }
        } elseif ($throwExceptionForEmptyWhere) {
            throw new EmptyWhereClauseException();
        }

        return $return;
    }

    /**
     * @return self|QueryBuilderInterface
     */
    public function resetWhere(): QueryBuilderInterface
    {
        $this->whereList = [];

        return $this;
    }
}
