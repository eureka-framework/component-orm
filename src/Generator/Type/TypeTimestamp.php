<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eureka\Component\Orm\Generator\Type;

/**
 * Mapping type for TIMESTAMP SQL values
 *
 * @author Romain Cottard
 */
class TypeTimestamp extends TypeAbstract
{
    /**
     * Class constructor.
     */
    public function __construct()
    {
        $this->type           = 'string';
        $this->castDb         = '(string)';
        $this->castMethod     = '(string)';
        $this->emptyValue     = "'0000-00-00 00:00:00'";
        $this->validatorClass = \Eureka\Component\Validation\Validator\DateTimeValidator::class;
    }
}
