<?php

/**
 * Copyright (c) 2010-2016 Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eureka\Component\Orm\Type;

/**
 * Mapping type for SMALLINT SQL values
 *
 * @author Romain Cottard
 */
class TypeSmallint extends TypeAbstract
{
    /**
     * Class constructor.
     */
    public function __construct()
    {
        $this->type       = 'int';
        $this->castDb     = '(int)';
        $this->castMethod = '(int)';
        $this->emptyValue = '0';
    }
}
