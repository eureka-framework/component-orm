<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eureka\Component\Orm\Generator\Type;

/**
 * Mapping type for MEDIUMINT SQL values
 *
 * @author Romain Cottard
 */
class TypeMediumint extends TypeAbstract
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
