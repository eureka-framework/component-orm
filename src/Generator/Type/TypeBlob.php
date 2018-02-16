<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eureka\Component\Orm\Generator\Type;

/**
 * Mapping type for BLOB SQL values
 *
 * @author Romain Cottard
 */
class TypeBlob extends TypeAbstract
{
    /**
     * Class constructor.
     */
    public function __construct()
    {
        $this->type       = 'string';
        $this->castDb     = '(string)';
        $this->castMethod = '(string)';
        $this->emptyValue = "''";
    }
}
