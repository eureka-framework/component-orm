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
 * Class BooleanValidator
 *
 * @author Romain Cottard
 */
class AbstractValidator
{
    /**
     * @param  array $options
     * @param  int|null $flags
     * @return array
     */
    protected function getOptions(array $options = [], $flags = FILTER_DEFAULT)
    {
        return $validatorOptions = [
            'options' => $options,
            'flags'   => FILTER_DEFAULT,
        ];
    }
}
