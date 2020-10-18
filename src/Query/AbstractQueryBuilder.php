<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Eureka\Component\Orm\Query;

use Eureka\Component\Orm\EntityInterface;
use Eureka\Component\Orm\Exception\OrmException;
use Eureka\Component\Orm\RepositoryInterface;

/**
 * Class AbstractQueryBuilder
 *
 * @author Romain Cottard
 */
abstract class AbstractQueryBuilder implements QueryBuilderInterface
{
    /** @var array $binds List of binding values */
    protected array $bind = [];

    /** @var string $listIndexedByField */
    protected string $listIndexedByField = '';

    /** @var RepositoryInterface */
    protected RepositoryInterface $repository;

    /** @var EntityInterface|null */
    protected ?EntityInterface $entity;

    /**
     * Clear query params
     *
     * @return QueryBuilderInterface
     */
    abstract public function clear(): QueryBuilderInterface;

    /**
     * @return string
     * @throws OrmException
     */
    abstract public function getQuery(): string;

    /**
     * AbstractQueryBuilder constructor.
     *
     * @param RepositoryInterface $repository
     * @param EntityInterface|null $entity
     */
    public function __construct(RepositoryInterface $repository, EntityInterface $entity = null)
    {
        $this->repository = $repository;
        $this->entity     = $entity;
    }

    /**
     * @return QueryBuilderInterface
     */
    public function resetBind(): QueryBuilderInterface
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
    public function addBind(string $field, $value, bool $isUnique = false): string
    {
        $suffix = ($isUnique ? '_' . uniqid() : '');
        $name   = ':' . strtolower($field . $suffix);

        if (is_bool($value)) {
            $value = (int) $value;
        }

        $this->bind[$name] = $value;

        return $name;
    }

    /**
     * Get bind
     *
     * @return array
     */
    public function getBind(): array
    {
        return $this->bind;
    }

    /**
     * Set bind
     *
     * @param  array $bind Bound values
     * @return QueryBuilderInterface
     */
    public function bind(array $bind): QueryBuilderInterface
    {
        $this->bind = $bind;

        return $this;
    }

    /**
     * Get indexed by
     *
     * @return string
     */
    public function getListIndexedByField(): string
    {
        return $this->listIndexedByField;
    }

    /**
     * Set indexed by
     *
     * @param  string $field
     * @return QueryBuilderInterface
     */
    public function setListIndexedByField(string $field): QueryBuilderInterface
    {
        $this->listIndexedByField = $field;

        return $this;
    }
}
