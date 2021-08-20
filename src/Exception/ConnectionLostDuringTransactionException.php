<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Eureka\Component\Orm\Exception;

/**
 * Exception thrown when connection is lost during a transaction.
 *
 * @author Romain Cottard
 */
class ConnectionLostDuringTransactionException extends OrmException
{
}
