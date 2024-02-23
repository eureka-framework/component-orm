<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Eureka\Component\Orm\Enumerator;

/**
 * Enumerator for JOIN types
 *
 * @author Romain Cottard
 */
class JoinType
{
    public const INNER = 'INNER';
    public const LEFT  = 'LEFT OUTER';
    public const RIGHT = 'RIGHT OUTER';
    public const FULL  = 'FULL OUTER';
}
