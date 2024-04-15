<?php

/*
 * Copyright (c) Deezer
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Eureka\Component\Orm\Enumerator;

enum Operator: string
{
    case Equal = '=';
    case NotEqual = '!=';
    case NotEqualAlias = '<>';
    case GreaterThan = '>';
    case GreaterThanOrEqual = '>=';
    case LesserThan = '<';
    case LesserThanOrEqual  = '<=';
    case NullSafeEqualTo = '<=>';
    case Like = 'LIKE';
    case Regexp = 'REGEXP';
    case Is = 'IS';
    case IsNot = 'IS NOT';
}
