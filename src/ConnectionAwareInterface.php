<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Eureka\Component\Orm;

/**
 * Connection Aware interface.
 *
 * @author Romain Cottard
 */
interface ConnectionAwareInterface
{
    /**
     * Quote parameter according to the connection.
     *
     * @param  int|float|string|bool|null $value
     * @return string
     */
    public function quote(int|float|string|bool|null $value): string;

    /**
     * Start new transaction.
     *
     * @return void
     */
    public function beginTransaction(): void;

    /**
     * Commit transactions.
     *
     * @return void
     */
    public function commit(): void;

    /**
     * Rollback transactions.
     *
     * @return void
     */
    public function rollBack(): void;

    /**
     * Check if we are in transaction or not.
     *
     * @return bool
     */
    public function inTransaction(): bool;
}
