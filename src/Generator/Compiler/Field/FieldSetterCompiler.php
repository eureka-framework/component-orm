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
use Eureka\Component\Orm\Generator\Type;

/**
 * Field Setter compiler class
 *
 * @author Romain Cottard
 */
class FieldSetterCompiler extends AbstractFieldCompiler
{
    /**
     * FieldSetterCompiler constructor.
     *
     * @param Field $field
     */
    public function __construct(Field $field)
    {
        parent::__construct(
            $field,
            [
                __DIR__ . '/../../Templates/FieldSetter.template' => false,
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
        $exception  = '';
        $validation = $this->getValidations($this->field->getType(), '$' . $this->getPropertyName($this->field));

        if (!empty($validation)) {
            $exception = "\n     * @throws ValidationException";
        }

        $context
            ->add('property.setter', $this->getNameForSetter($this->field))
            ->add('property.validation', implode("\n        ", $validation))
            ->add('property.exception', $exception)
        ;

        return $context;
    }

    /**
     * @param  Type\TypeInterface $type
     * @param  string $varname
     * @return string[]
     */
    private function getValidations(Type\TypeInterface $type, string $varname): array
    {
        $validations      = [];
        $validationConfig = $this->field->getValidation();

        if ($this->field->hasValidation() && !empty($validationConfig)) {
            $validatorType = $validationConfig['type'];
        } elseif ($this->field->hasValidationAuto() && !empty($type->getValidatorType())) {
            $validatorType = $type->getValidatorType();
        }

        //~ Open validation condition if necessary
        if ($this->field->isNullable() && !empty($validatorType)) {
            $validations[] = 'if (' . $varname . ' !== null) {';
            $validations[] = '    $this->validateInput(\'' . $this->field->getName(). '\', ' . $varname . ');';
            $validations[] = '}';
        } elseif (!empty($validatorType)) {
            $validations[] = '$this->validateInput(\'' . $this->field->getName(). '\', ' . $varname . ');';
        }

        return $validations;
    }

    /**
     * @param Field $field
     * @return string
     */
    private function getAutoIncrementSetter(Field $field): string
    {
        if (!$field->isAutoIncrement()) {
            return '';
        }



        return '';
    }
}
