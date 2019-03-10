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
use Eureka\Component\Validation\Validator\IntegerValidator;

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
            $exception = "\n     * @throws \Eureka\Component\Validation\Exception\ValidationException";
        }

        $context
            ->add('property.setter', $this->getNameForSetter($this->field))
            ->add('property.validation', implode("\n        ", $validation))
            ->add('property.exception', $exception)
        ;

        return $context;
    }

    /**
     * @param  \Eureka\Component\Orm\Generator\Type\TypeInterface $type
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

        $prependIndentation = '';
        //~ Open validation condition if necessary
        if ($this->field->isNullable() && !empty($validatorType)) {
            $prependIndentation = '    ';

            $validations[] = 'if (' . $varname . ' !== null) {';
        }

        if (!empty($validatorType) && strpos($validatorType, '\\') !== false) {
            $validations[] = $prependIndentation . '$validator = new \\' . trim($validatorType, '\\') . '();';
            $validations[] = $prependIndentation . '$validator->validate(' . $varname . ', ' . $this->getValidatorOptions($validations, empty($validations['options']), true) . ');';
        } elseif (!empty($validatorType)) {
            $validations[] = $prependIndentation . '$validator = $this->getValidatorFactory()->getValidator(\'' . $validatorType . '\');';
            $validations[] = $prependIndentation . '$validator->validate(' . $varname . ', ' . $this->getValidatorOptions($validations, empty($validations['options']), true) . ');';
        }

        //~ Close condition
        if ($this->field->isNullable() && !empty($validatorType)) {
            $validations[] = '}';
        }

        return $validations;
    }

    /**
     * @param array $validationConfig
     * @param bool $auto
     * @param bool $toString
     * @return array|string
     */
    private function getValidatorOptions(array $validationConfig, bool $auto = false, bool $toString = false)
    {
        if ($auto) {
            $options = $this->getValidatorOptionsFromType($this->field->getType());
        } else {
            $options = !empty($validationConfig['options']) ? $validationConfig['options'] : [];
        }

        if (!$toString) {
            return $options;
        }

        $return = [];
        foreach ($options as $name => $value) {
            $return[] = var_export($name, true ) . ' => ' . var_export($value, true);
        }

        return '[' . implode(', ', $return) . ']';
    }

    /**
     * @param  Type\TypeInterface $type
     * @return array
     */
    private function getValidatorOptionsFromType(Type\TypeInterface $type): array
    {
        $options    = [];
        $isNullable = $this->field->isNullable();
        $isUnsigned = $type->isUnsigned();

        switch (get_class($type)) {
            //~ Case Integers
            case Type\TypeBigint::class:
            case Type\TypeInt::class:
            case Type\TypeMediumint::class:
            case Type\TypeSmallint::class:
            case Type\TypeTinyint::class:
                $options = array_merge($options, $this->getIntegerOptions(get_class($type), $isNullable));
                break;
            //~ Case float
            case Type\TypeFloat::class:
            case Type\TypeDouble::class:
            case Type\TypeDecimal::class:
                $options = array_merge($options, $this->getDecimalOptions($isUnsigned));
                break;
            //~ Case Strings
            case Type\TypeLongtext::class:
            case Type\TypeMediumtext::class:
            case Type\TypeText::class:
            case Type\TypeTinytext::class:
            case Type\TypeVarchar::class:
            case Type\TypeChar::class:
                $options = array_merge($options, $this->getStringOptions(get_class($type), $type, $isNullable));
                break;
            case Type\TypeBool::class:
            case Type\TypeDateTime::class:
            case Type\TypeDate::class:
            case Type\TypeTime::class:
            case Type\TypeTimestamp::class:
            default:
                // Nothing to add
                break;
        }

        return $options;
    }

    /**
     * @param string $typeClass
     * @param bool $isUnsigned
     * @return array
     */
    private function getIntegerOptions(string $typeClass, bool $isUnsigned): array
    {
        switch ($typeClass) {
            case Type\TypeBigint::class:
                $options = $isUnsigned ? IntegerValidator::BIGINT_UNSIGNED : IntegerValidator::BIGINT_SIGNED;
                break;
            case Type\TypeInt::class:
                $options = $isUnsigned ? IntegerValidator::INT_UNSIGNED : IntegerValidator::INT_SIGNED;
                break;
            case Type\TypeMediumint::class:
                $options = $isUnsigned ? IntegerValidator::MEDIUMINT_UNSIGNED : IntegerValidator::MEDIUMINT_SIGNED;
                break;
            case Type\TypeSmallint::class:
                $options = $isUnsigned ? IntegerValidator::SMALLINT_UNSIGNED : IntegerValidator::SMALLINT_SIGNED;
                break;
            case Type\TypeTinyint::class:
                $options = $isUnsigned ? IntegerValidator::TINYINT_UNSIGNED : IntegerValidator::TINYINT_SIGNED;
                break;
            default:
                $options = [];
        }

        return $options;
    }

    /**
     * @param bool $isUnsigned
     * @return array
     */
    private function getDecimalOptions(bool $isUnsigned): array
    {
        return $isUnsigned ? ['min_range' => 0.0] : [];
    }

    /**
     * @param string $typeClass
     * @param Type\TypeInterface $type
     * @param bool $isNullable
     * @return array
     */
    private function getStringOptions(string $typeClass, Type\TypeInterface $type, bool $isNullable): array
    {
        $options = [];

        switch ($typeClass) {
            case Type\TypeLongtext::class:
                if (!$isNullable) {
                    $options['min_length'] = 1;
                }

                $options['max_length'] = 4294967295;
                break;
            case Type\TypeMediumtext::class:
                if (!$isNullable) {
                    $options['min_length'] = 1;
                }

                $options['max_length'] = 16777215;
                break;
            case Type\TypeText::class:
                if (!$isNullable) {
                    $options['min_length'] = 1;
                }

                $options['max_length'] = 65535;
                break;
            case Type\TypeTinytext::class:
                if (!$isNullable) {
                    $options['min_length'] = 1;
                }

                $options['max_length'] = 255;
                break;
            case Type\TypeVarchar::class:
            case Type\TypeChar::class:
                if (!$isNullable) {
                    $options['min_length'] = 1;
                }

                $options['max_length'] = $type->getLength();
                break;
            default:
                //~ Do nothing
        }

        return $options;
    }
}
