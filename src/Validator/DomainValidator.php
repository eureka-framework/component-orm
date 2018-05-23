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
 * Class DomainValidator
 *
 * @author Romain Cottard
 */
class DomainValidator extends AbstractValidator implements ValidatorInterface
{
    /**
     * {@inheritdoc}
     */
    public function validate($value, array $options = [], $flags = null)
    {
        $filteredValue = filter_var($value, FILTER_VALIDATE_DOMAIN, $this->getOptions($options, $flags));

        if (false === $filteredValue) {
            throw new ValidatorException('Given value is not a valid email!', 1001);
        }

        return $filteredValue;
    }
}
