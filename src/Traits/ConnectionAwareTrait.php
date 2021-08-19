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

    private bool $inTransaction = false;

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
        $this->inTransaction = true;
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
        $this->inTransaction = false;
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
        $this->inTransaction = false;
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

    /**
     * @param string $query
     * @param array|null $bind
     * @return \PDOStatement
     */
    protected function execute(string $query, ?array $bind = null): \PDOStatement
    {
        $connection = $this->getConnection();
        try {
            $statement = $connection->prepare($query);
            $statement->execute($bind);
        } catch (\PDOException $exception) {
            if (!$this->isConnectionLost($exception)) {
                throw $exception;
            }

            //~ Force reconnection
            $connection = $this->getConnection(true);
            $statement  = $connection->prepare($query);
            $statement->execute($bind);
        }

        return $statement;
    }

    /**
     * @param string $query
     * @param array|null $bind
     * @return bool
     */
    protected function executeWithResult(string $query, ?array $bind = null): bool
    {
        $connection = $this->getConnection();
        try {
            $statement = $connection->prepare($query);
            return $statement->execute($bind);
        } catch (\PDOException $exception) {
            if (!$this->isConnectionLost($exception)) {
                throw $exception;
            }

            //~ Force reconnection
            $connection = $this->getConnection(true);
            $statement  = $connection->prepare($query);
            return $statement->execute($bind);
        }
    }

    /**
     * @param \PDOException $exception
     * @return bool
     */
    protected function isConnectionLost(\PDOException $exception): bool
    {
        // Only keep SQLState HY000 with ErrorCode 2006 | 2013 (MySQL server has gone away)
        return ($exception->errorInfo[0] === 'HY000' && in_array($exception->errorInfo[1], [2006, 2013]));
    }
}
