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
 * Class Field Getter Compiler
 *
 * @author Romain Cottard
 */
class FieldGetterCompiler extends AbstractFieldCompiler
{
    /**
     * FieldGetterCompiler constructor.
     *
     * @param Field $field
     */
    public function __construct(Field $field)
    {
        parent::__construct(
            $field,
            [
            __DIR__ . '/../../Templates/FieldGetter.template' => false
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
        $context
            ->add('property.getter', $this->getNameForGetter($this->field))
        ;

        return $context;
    }
}
