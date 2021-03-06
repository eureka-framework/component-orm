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
 * Mapping type for DOUBLE SQL values
 *
 * @author Romain Cottard
 */
class TypeDouble extends TypeAbstract
{
    /**
     * Class constructor.
     */
    public function __construct()
    {
        $this->type          = 'float';
        $this->castDb        = '(float)';
        $this->castMethod    = '(float)';
        $this->emptyValue    = '0.0';
        $this->validatorType = 'float';
    }
}
