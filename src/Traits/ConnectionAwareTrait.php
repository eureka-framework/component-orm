<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Eureka\Component\Orm\Traits;

use Eureka\Component\Database\Connection;
use Eureka\Component\Database\ConnectionFactory;
use Eureka\Component\Orm\RepositoryInterface;

/**
 * Connection Aware trait
 *
 * @author Romain Cottard
 */
trait ConnectionAwareTrait
{
    /** @var ConnectionFactory $connectionFactory Connection factory instance */
    private ConnectionFactory $connectionFactory;

    /** @var string $name Connection name */
    private string $name;

    /**
     * @param bool $forceReconnection
     * @return Connection
     */
    public function getConnection(bool $forceReconnection = false): Connection
    {
        return $this->connectionFactory->getConnection($this->name, $forceReconnection);
    }

    /**
     * Quote parameter according to the connection.
     *
     * @param  int|float|string|bool $value
     * @return string
     * @codeCoverageIgnore
     */
    public function quote($value): string
    {
        return $this->getConnection()->quote($value);
    }

    /**
     * Start new transaction.
     *
     * @return void
     * @codeCoverageIgnore
     */
    public function beginTransaction(): void
    {
        $this->getConnection()->beginTransaction();
    }

    /**
     * Commit transactions.
     *
     * @return void
     * @codeCoverageIgnore
     */
    public function commit(): void
    {
        $this->getConnection()->commit();
    }

    /**
     * Rollback transactions.
     *
     * @return void
     * @codeCoverageIgnore
     */
    public function rollBack(): void
    {
        $this->getConnection()->rollBack();
    }

    /**
     * Check if we are in transaction or not.
     *
     * @return bool
     * @codeCoverageIgnore
     */
    public function inTransaction(): bool
    {
        return $this->getConnection()->inTransaction();
    }

    /**
     * @param ConnectionFactory $connectionFactory
     * @return self|RepositoryInterface
     */
    protected function setConnectionFactory(ConnectionFactory $connectionFactory): RepositoryInterface
    {
        $this->connectionFactory = $connectionFactory;

        return $this;
    }

    /**
     * @param string $name
     * @return $this|RepositoryInterface
     */
    protected function setConnectionName(string $name): RepositoryInterface
    {
        $this->name = $name;

        return $this;
    }
}
