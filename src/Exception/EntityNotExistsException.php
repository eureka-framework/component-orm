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
 * Exception thrown if a entity not exists (entity can be a file, data in db...).
 *
 * @author Romain Cottard
 */
class EntityNotExistsException extends OrmException
{
}
