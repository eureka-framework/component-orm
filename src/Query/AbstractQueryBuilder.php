<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eureka\Component\Orm\Query;

use Eureka\Component\Orm\EntityInterface;
use Eureka\Component\Orm\RepositoryInterface;

/**
 * Class Query
 *
 * @author Romain Cottard
 */
abstract class AbstractQueryBuilder implements QueryBuilderInterface
{
    /** @var array $binds List of binding values */
    protected $bind = [];

    /** @var string $listIndexedByField */
    protected $listIndexedByField = '';

    /** @var \Eureka\Component\Orm\RepositoryInterface */
    protected $repository;

    /** @var \Eureka\Component\Orm\EntityInterface|null */
    protected $entity;

    /**
     * Clear query params
     *
     * @return $this
     */
    abstract public function clear();

    /**
     * @return string
     * @throws \Eureka\Component\Orm\Exception\OrmException
     */
    abstract public function getQuery();

    /**
     * AbstractQueryBuilder constructor.
     *
     * @param \Eureka\Component\Orm\RepositoryInterface $repository
     * @param \Eureka\Component\Orm\EntityInterface $entity
     */
    public function __construct(RepositoryInterface $repository, EntityInterface $entity = null)
    {
        $this->repository = $repository;
        $this->entity     = $entity;
    }

    /**
     * @return $this
     */
    public function resetBind()
    {
        $this->bind = [];

        return $this;
    }

    /**
     * @param  string $field
     * @param  mixed $value
     * @param  bool $isUnique
     * @return string Return bind name field
     */
    public function addBind($field, $value, $isUnique = false)
    {
        $suffix = ($isUnique ? '_' . uniqid() : '');
        $name   = ':' . strtolower($field . $suffix);

        $this->bind[$name] = $value;

        return $name;
    }

    /**
     * Get bind
     *
     * @return array
     */
    public function getBind()
    {
        return $this->bind;
    }

    /**
     * Set bind
     *
     * @param  array $bind Binded values
     * @return $this
     */
    public function bind(array $bind)
    {
        $this->bind = $bind;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getListIndexedByField()
    {
        return $this->listIndexedByField;
    }

    /**
     * {@inheritdoc}
     */
    public function setListIndexedByField($field)
    {
        $this->listIndexedByField = $field;

        return $this;
    }
}
