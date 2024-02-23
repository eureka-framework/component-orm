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
use Eureka\Component\Orm\Generator\Compiler\Field\Field;

/**
 * Class AbstractFieldCompiler
 *
 * @author Romain Cottard
 */
class AbstractFieldCompiler extends AbstractCompiler
{
    /** @var Field $field */
    protected Field $field;

    /**
     * AbstractFieldCompiler constructor.
     *
     * @param Field $field
     * @param array<string, bool> $templates
     */
    public function __construct(Field $field, array $templates)
    {
        parent::__construct($templates);

        $this->field = $field;
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

    protected function getContext(): Context
    {
        $name = $this->getPropertyName($this->field);

        return (new Context())
            ->add('property.description', $this->field->getName())
            ->add('property.type', $this->getTypeDoc())
            ->add('property.typehint', $this->getTypeHint())
            ->add('property.name', $name)
            ->add('property.varname', '$' . $name)
            ->add('property.default', $this->getDefault())
        ;
    }

    /**
     * Get type for documentation
     */
    private function getTypeDoc(): string
    {
        $typeDoc = (string) $this->field->getType();

        if ($this->field->isNullable()) {
            $typeDoc .= '|null';
        }

        return $typeDoc;
    }

    /**
     * Get type hint
     */
    private function getTypeHint(): string
    {
        $typeHint = (string) $this->field->getType();

        if ($this->field->isNullable()) {
            $typeHint = '?' . $typeHint;
        }

        return $typeHint;
    }

    /**
     * Get default value for the field.
     */
    protected function getDefault(): string|int|float|bool|null
    {
        $default = $this->field->getDefaultValue();

        if ($default === '') {
            $default = $this->field->getType()->getEmptyValue();
        }

        return $default;
    }
}
