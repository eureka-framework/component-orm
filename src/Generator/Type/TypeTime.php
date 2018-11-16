<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Eureka\Component\Orm\Generator\Type;

/**
 * Mapping type for TIME SQL values
 *
 * @author Romain Cottard
 */
class TypeTime extends TypeAbstract
{
    /**
     * Class constructor.
     */
    public function __construct()
    {
        $this->type          = 'string';
        $this->castDb        = '(string)';
        $this->castMethod    = '(string)';
        $this->emptyValue    = "'00:00:00'";
        $this->validatorType = 'time';
    }
}
