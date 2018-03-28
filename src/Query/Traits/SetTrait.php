<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eureka\Component\Orm\Query\Traits;

use Eureka\Component\Orm\Exception\EmptySetClauseException;

/**
 * Class SetTrait
 *
 * @author Romain Cottard
 */
trait SetTrait
{
    /** @var string[] $setList List of set for current query (update or insert) */
    protected $setList = [];

    /** @var string[] $setList List of set for current query (update or insert) */
    protected $updateList = [];

    /**
     * @param  string $field
     * @param  mixed $value
     * @param  bool $isUnique
     * @return string Return bind name field
     */
    abstract public function addBind($field, $value, $isUnique = false);

    /**
     * Add set clause.
     *
     * @param  string $field
     * @param  string|int|null $value
     * @return $this
     */
    public function addSet($field, $value)
    {
        $bindName = $this->addBind($field, $value, true);

        $this->setList[] = '`' . $field . '` = ' . $bindName;

        return $this;
    }

    /**
     * Add set clause.
     *
     * @param  string $field
     * @param  string|int|null $value
     * @return $this
     */
    public function addUpdate($field, $value)
    {
        $bindName = $this->addBind($field, $value, true);

        $this->updateList[] = '`' . $field . '` = ' . $bindName;

        return $this;
    }

    /**
     * Get Set clause.
     *
     * @return string
     * @throws \Eureka\Component\Orm\Exception\EmptySetClauseException
     */
    public function getQuerySet()
    {
        if (0 === count($this->setList)) {
            throw new EmptySetClauseException();
        }

        return ' SET ' . implode(', ', $this->setList);
    }

    /**
     * Get on duplicate update clause.
     *
     * @return string
     */
    public function getQueryDuplicateUpdate()
    {
        if (empty($this->updateList)) {
            return '';
        }

        return ' ON DUPLICATE KEY UPDATE ' . implode(', ', $this->updateList);
    }


    /**
     * @return $this
     */
    public function resetSet()
    {
        $this->setList = [];
        $this->updateList = [];

        return $this;
    }
}
