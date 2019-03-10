<?php declare(strict_types=1);

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eureka\Component\Orm\Generator\Compiler\Field;

use Eureka\Component\Orm\Generator\Compiler\AbstractFieldCompiler;
use Eureka\Component\Orm\Generator\Compiler\Context;

/**
 * Field Property Compiler class
 *
 * @author Romain Cottard
 */
class FieldPropertyCompiler extends AbstractFieldCompiler
{
    /**
     * FieldPropertyCompiler constructor.
     *
     * @param Field $field
     */
    public function __construct(Field $field)
    {
        parent::__construct(
            $field,
            [
                __DIR__ . '/../../Templates/FieldProperty.template' => false,
            ]
        );
    }

    /**
     * @param Context $context
     * @param bool $isAbstract
     * @return Context
     */
    protected function updateContext(Context $context, bool $isAbstract = false): Context
    {
        return $context->merge($this->getContext());
    }
}
