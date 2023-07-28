<?php

/*
 * Copyright (c) Deezer
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Eureka\Component\Orm\Enumerator;

enum ClauseConcat: string
{
    case And = 'AND';
    case Or  = 'OR';
}
