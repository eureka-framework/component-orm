<?php

/**
 * Copyright (c) 2010-2016 Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eureka\Component\Orm\Type;

/**
 * Mapping type for DECIMAL SQL values
 *
 * @author Romain Cottard
 * @version 2.0.0
 */
class TypeDecimal extends TypeAbstract
{
    /**
     * Class constructor.
     */
    public function __construct()
    {
        $this->type       = 'float';
        $this->castDb     = '(float)';
        $this->castMethod = '(float)';
        $this->emptyValue = '0.0';
    }
}
