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
use Eureka\Component\Orm\Query\Interfaces\QueryBuilderInterface;
use Eureka\Component\Orm\RepositoryInterface;

abstract class AbstractQueryBuilder implements QueryBuilderInterface
{
    /** @var array<string|int|float|bool|null> $bind List of binding values */
    protected array $bind = [];

    protected string $listIndexedByField = '';

    abstract public function clear(): static;
    abstract public function getQuery(): string;

    public function __construct(
        protected readonly RepositoryInterface $repository,
        protected readonly ?EntityInterface $entity = null
    ) {
    }

    /**
     * @return static
     */
    public function resetBind(): static
    {
        $this->bind = [];

        return $this;
    }

    /**
     * @throws \Exception
     */
    public function bind(string $field, string|int|float|bool|null $value, bool $isUnique = false): string
    {
        $suffix = ($isUnique ? '_' . substr(bin2hex(random_bytes(13)), 0, 13) : '');
        $name   = ':' . strtolower(str_replace(['(', ')', ',', ' '], ['', '', '', '_'], $field . $suffix));

        if (is_bool($value)) {
            $value = (int) $value;
        }

        $this->bind[$name] = $value;

        return $name;
    }

    /**
     * Get bind
     *
     * @return array<string|int|float|bool|null>
     */
    public function getAllBind(): array
    {
        return $this->bind;
    }

    /**
     * Set bind
     *
     * @param  array<string|int|float|bool|null> $bind Bound values
     * @return QueryBuilderInterface
     */
    public function bindAll(array $bind): QueryBuilderInterface
    {
        $this->bind = $bind;

        return $this;
    }

    public function getListIndexedByField(): string
    {
        return $this->listIndexedByField;
    }

    public function setListIndexedByField(string $field): static
    {
        $this->listIndexedByField = $field;

        return $this;
    }
}
