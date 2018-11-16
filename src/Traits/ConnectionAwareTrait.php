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
use Eureka\Component\Orm\RepositoryInterface;

/**
 * Connection Aware trait
 *
 * @author Romain Cottard
 */
trait ConnectionAwareTrait
{
    /** @var \Eureka\Component\Database\Connection $connection Connection instance */
    protected $connection = null;

    /**
     * Quote parameter according to the connection.
     *
     * @param  int|float|string|bool $value
     * @return string
     */
    public function quote($value): string
    {
        return $this->connection->quote($value);
    }

    /**
     * Start new transaction.
     *
     * @return void
     */
    public function beginTransaction(): void
    {
        $this->connection->beginTransaction();
    }

    /**
     * Commit transactions.
     *
     * @return void
     */
    public function commit(): void
    {
        $this->connection->commit();
    }

    /**
     * Rollback transactions.
     *
     * @return void
     */
    public function rollBack(): void
    {
        $this->connection->rollBack();
    }

    /**
     * Check if we are in transaction or not.
     *
     * @return bool
     */
    public function inTransaction(): bool
    {
        return $this->connection->inTransaction();
    }

    /**
     * @param \Eureka\Component\Database\Connection $connection
     * @return $this
     */
    protected function setConnection(Connection $connection): RepositoryInterface
    {
        $this->connection = $connection;

        return $this;
    }
}
