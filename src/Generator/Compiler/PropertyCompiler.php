<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Eureka\Component\Orm\Generator\Compiler;

use Eureka\Component\Orm\Exception\GeneratorException;

/**
 * Property Compiler class
 *
 * @author Romain Cottard
 */
class PropertyCompiler extends AbstractCompiler
{
    public function __construct(
        private readonly string $name,
        private readonly string $typeHint,
        private readonly string $typeDoc,
        private readonly string|int|float|bool|null $defaultValue = null,
    ) {
        parent::__construct(
            [
                __DIR__ . '/../Templates/FieldProperty.template' => false,
            ],
        );
    }

    /**
     * Compile fields element.
     *
     * @return string[]
     * @throws GeneratorException
     */
    public function compile(): array
    {
        $rendered = [];
        foreach ($this->templates as $template => $isAbstract) {
            $name = basename($template, '.template');

            //~ Get context
            $context = $this->updateContext($this->getContext(), $isAbstract);

            //~ Render template
            $rendered[$name] = $this->renderTemplate($template, $context);
        }

        return array_values($rendered);
    }

    /**
     * @return Context
     */
    protected function getContext(): Context
    {
        return (new Context())->add('property.description', $this->name)
            ->add('property.type', $this->typeDoc)
            ->add('property.typehint', $this->typeHint)
            ->add('property.name', $this->name)
            ->add('property.varname', '$' . $this->name)
            ->add('property.default', $this->defaultValue)
        ;
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
