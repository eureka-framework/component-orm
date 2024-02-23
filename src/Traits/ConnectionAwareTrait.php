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
use Eureka\Component\Orm\Exception\ConnectionLostDuringTransactionException;

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
     * @param  int|float|string|bool|null $value
     * @return string
     * @codeCoverageIgnore
     */
    public function quote(int|float|string|bool|null $value): string
    {
        return $this->getConnection()->quote((string) $value);
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
     * @throws ConnectionLostDuringTransactionException
     */
    public function commit(): void
    {
        try {
            $this->getConnection()->commit();
        } catch (\PDOException $exception) {
            if ($this->isConnectionLost($exception)) {
                $this->getConnection(true);
                throw new ConnectionLostDuringTransactionException('Cannot commit, connection lost', 1_002);
            }

            throw $exception;
        }
    }

    /**
     * Rollback transactions.
     *
     * @return void
     * @codeCoverageIgnore
     * @throws ConnectionLostDuringTransactionException
     */
    public function rollBack(): void
    {
        try {
            $this->getConnection()->rollBack();
        } catch (\PDOException $exception) {
            if ($this->isConnectionLost($exception)) {
                $this->getConnection(true);
                throw new ConnectionLostDuringTransactionException('Cannot rollback, connection lost', 1_003);
            }

            throw $exception;
        }
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
     * @return static
     */
    protected function setConnectionFactory(ConnectionFactory $connectionFactory): static
    {
        $this->connectionFactory = $connectionFactory;

        return $this;
    }

    /**
     * @param string $name
     * @return static
     */
    protected function setConnectionName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @param string $query
     * @param array<mixed>|null $bind
     * @return \PDOStatement
     * @throws ConnectionLostDuringTransactionException
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

            if ($this->inTransaction()) {
                // @codeCoverageIgnoreStart
                //~ Force reconnection to reset "inTransaction()" status & throw error specific error
                $this->getConnection(true);
                throw new ConnectionLostDuringTransactionException('Connection lost during a transaction.', 1_000);
                // @codeCoverageIgnoreEnd
            }

            $connection = $this->getConnection(true); // Force reconnection
            $statement  = $connection->prepare($query);
            $statement->execute($bind);
        }

        return $statement;
    }

    /**
     * @param string $query
     * @param array<mixed>|null $bind
     * @return bool
     * @throws ConnectionLostDuringTransactionException
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

            if ($this->inTransaction()) {
                // @codeCoverageIgnoreStart
                //~ Force reconnection to reset "inTransaction()" status & throw error specific error
                $this->getConnection(true);
                throw new ConnectionLostDuringTransactionException('Connection lost during a transaction.', 1_001);
                // @codeCoverageIgnoreEnd
            }

            $connection = $this->getConnection(true); // Force reconnection
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
        $sqlState = $exception->errorInfo[0] ?? 'UNKNW';
        $sqlCode  = $exception->errorInfo[1] ?? 1;
        return $sqlState === 'HY000' && in_array($sqlCode, [2006, 2013]);
    }
}
