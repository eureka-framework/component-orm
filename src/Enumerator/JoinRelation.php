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
 * Enumerator for JOIN relation numbers
 *
 * @author Romain Cottard
 */
class JoinRelation
{
    const MANY = 'many';
    const ONE  = 'one';
}
