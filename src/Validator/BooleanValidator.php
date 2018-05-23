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
class BooleanValidator extends AbstractValidator implements ValidatorInterface
{
    /**
     * {@inheritdoc}
     */
    public function validate($value, array $options = [], $flags = null)
    {
        if ($flags === null) {
            $flags = !isset($options['default']) ? FILTER_NULL_ON_FAILURE : FILTER_DEFAULT;
        }

        $filteredValue = filter_var($value, FILTER_VALIDATE_BOOLEAN, $this->getOptions($options, $flags));

        if (null === $filteredValue) {
            throw new ValidatorException('Given value is not a valid boolean!', 1000);
        }

        return $filteredValue;
    }
}
