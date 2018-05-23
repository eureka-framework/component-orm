<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eureka\Component\Orm\Enumerator;

/**
 * Enumerator for JOIN types
 *
 * @author Romain Cottard
 */
class JoinType
{
    const INNER = 'INNER';
    const LEFT  = 'LEFT OUTER';
    const RIGHT = 'RIGHT OUTER';
    const FULL  = 'FULL OUTER';

}
