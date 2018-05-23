<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eureka\Component\Orm\Validator;

use Eureka\Component\Orm\Exception\ValidatorException;
use Eureka\Component\Orm\ValidatorInterface;

/**
 * Class IntegerValidator
 *
 * @author Romain Cottard
 */
class IntegerValidator extends AbstractValidator implements ValidatorInterface
{
    const TINYINT_SIGNED     = ['min_range' => -128, 'max_range' => 127];
    const TINYINT_UNSIGNED   = ['min_range' => 0, 'max_range' => 255];
    const SMALLINT_SIGNED    = ['min_range' => -32768, 'max_range' => 32767];
    const SMALLINT_UNSIGNED  = ['min_range' => 0, 'max_range' => 65535];
    const MEDIUMINT_SIGNED   = ['min_range' => -8388608, 'max_range' => 8388607];
    const MEDIUMINT_UNSIGNED = ['min_range' => 0, 'max_range' => 16777215];
    const INT_SIGNED         = ['min_range' => -2147483648, 'max_range' => 2147483647];
    const INT_UNSIGNED       = ['min_range' => 0, 'max_range' => 4294967295];
    const BIGINT_SIGNED      = ['min_range' => -9223372036854775808, 'max_range' => 9223372036854775807];
    const BIGINT_UNSIGNED    = ['min_range' => 0, 'max_range' => 18446744073709551615];

    /**
     * {@inheritdoc}
     */
    public function validate($value, array $options = [], $flags = null)
    {
        $filteredValue = filter_var($value, FILTER_VALIDATE_INT, $this->getOptions($options, $flags));

        if (false === $filteredValue) {
            throw new ValidatorException('Given value is not a valid email!', 1002);
        }

        return $filteredValue;
    }
}
