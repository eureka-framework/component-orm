<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Eureka\Component\Orm\Generator\Compiler\Field;

use Eureka\Component\Orm\Generator\Type;
use Eureka\Component\Validation\Validator\IntegerValidator;

/**
 * Class FieldValidatorService
 *
 * @author Romain Cottard
 */
class FieldValidatorService
{
    /**
     * @param Field $field
     * @return array<mixed>
     */
    public function getValidatorOptions(Field $field): array
    {
        $validationConfig = $field->getValidation();

        $options = !empty($validationConfig['options']) ? $validationConfig['options'] : [];

        if ($field->hasValidationAuto()) {
            $options = $this->getValidatorOptionsFromType($field, $field->getType(), $options);
        }

        return $options;
    }

    public function getValidatorOptionsAsString(Field $field): string
    {
        $options = $this->getValidatorOptions($field);

        $return = [];
        foreach ($options as $name => $value) {
            $return[] = var_export($name, true) . ' => ' . var_export($value, true);
        }

        return '[' . implode(', ', $return) . ']';
    }

    /**
     * @param Field $field
     * @param Type\TypeInterface $type
     * @param array<mixed> $options
     * @return array<mixed>
     */
    private function getValidatorOptionsFromType(Field $field, Type\TypeInterface $type, array $options = []): array
    {
        $isNullable = $field->isNullable();
        $isUnsigned = $type->isUnsigned();

        switch (get_class($type)) {
            //~ Case Integers
            case Type\TypeBigint::class:
            case Type\TypeInt::class:
            case Type\TypeMediumint::class:
            case Type\TypeSmallint::class:
            case Type\TypeTinyint::class:
                $options = array_merge($this->getIntegerOptions(get_class($type), $isUnsigned), $options);
                break;
            //~ Case float
            case Type\TypeFloat::class:
            case Type\TypeDouble::class:
            case Type\TypeDecimal::class:
                $options = array_merge($this->getDecimalOptions($isUnsigned), $options);
                break;
            //~ Case Strings
            case Type\TypeLongtext::class:
            case Type\TypeMediumtext::class:
            case Type\TypeText::class:
            case Type\TypeTinytext::class:
            case Type\TypeVarchar::class:
            case Type\TypeVarbinary::class:
            case Type\TypeChar::class:
            case Type\TypeBinary::class:
            case Type\TypeBlob::class:
            case Type\TypeMediumblob::class:
            case Type\TypeLongblob::class:
            case Type\TypeTinyblob::class:
                $options = array_merge($this->getStringOptions(get_class($type), $type, $isNullable), $options);
                break;
            case Type\TypeBool::class:
            case Type\TypeDatetime::class:
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
     * @return array{min_range?: int|float, max_range?: int|float}
     */
    private function getIntegerOptions(string $typeClass, bool $isUnsigned): array
    {
        return match ($typeClass) {
            Type\TypeBigint::class => $isUnsigned ?
                IntegerValidator::BIGINT_UNSIGNED :
                IntegerValidator::BIGINT_SIGNED,
            Type\TypeInt::class => $isUnsigned ?
                IntegerValidator::INT_UNSIGNED :
                IntegerValidator::INT_SIGNED,
            Type\TypeMediumint::class => $isUnsigned ?
                IntegerValidator::MEDIUMINT_UNSIGNED :
                IntegerValidator::MEDIUMINT_SIGNED,
            Type\TypeSmallint::class => $isUnsigned ?
                IntegerValidator::SMALLINT_UNSIGNED :
                IntegerValidator::SMALLINT_SIGNED,
            Type\TypeTinyint::class => $isUnsigned ?
                IntegerValidator::TINYINT_UNSIGNED :
                IntegerValidator::TINYINT_SIGNED,
            default => [],
        };
    }

    /**
     * @param bool $isUnsigned
     * @return float[]
     */
    private function getDecimalOptions(bool $isUnsigned): array
    {
        return $isUnsigned ? ['min_range' => 0.0] : [];
    }

    /**
     * @param string $typeClass
     * @param Type\TypeInterface $type
     * @param bool $isNullable
     * @return int[]
     */
    private function getStringOptions(string $typeClass, Type\TypeInterface $type, bool $isNullable): array
    {
        $options = [];

        switch ($typeClass) {
            case Type\TypeLongtext::class:
                if (!$isNullable) {
                    $options['min_length'] = 0;
                }

                $options['max_length'] = 4294967295;
                break;
            case Type\TypeMediumtext::class:
                if (!$isNullable) {
                    $options['min_length'] = 0;
                }

                $options['max_length'] = 16777215;
                break;
            case Type\TypeText::class:
                if (!$isNullable) {
                    $options['min_length'] = 0;
                }

                $options['max_length'] = 65535;
                break;
            case Type\TypeTinytext::class:
                if (!$isNullable) {
                    $options['min_length'] = 0;
                }

                $options['max_length'] = 255;
                break;
            case Type\TypeVarchar::class:
            case Type\TypeVarbinary::class:
            case Type\TypeChar::class:
                if (!$isNullable) {
                    $options['min_length'] = 0;
                }

                $options['max_length'] = $type->getLength();
                break;
            default:
                //~ Do nothing
        }

        return $options;
    }
}
