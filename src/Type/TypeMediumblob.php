<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eureka\Component\Orm\Type;

/**
 * Mapping type for MEDIUMBLOB SQL values
 *
 * @author Romain Cottard
 */
class TypeMediumblob extends TypeAbstract
{
    /**
     * Class constructor.
     */
    public function __construct()
    {
        $this->type       = 'string';
        $this->castDb     = '(string)';
        $this->castMethod = '(string)';
        $this->emptyValue = 'null';
    }
}
