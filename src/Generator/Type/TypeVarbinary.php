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
 * Mapping type for VARBINARY SQL values
 *
 * @author Romain Cottard
 */
class TypeVarbinary extends TypeAbstract
{
    /**
     * Class constructor.
     */
    public function __construct()
    {
        $this->type          = 'string';
        $this->castDb        = '(string)';
        $this->castMethod    = '(string)';
        $this->emptyValue    = 'null';
        $this->validatorType = 'string';
    }
}
