<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Eureka\Component\Orm\Generator\Compiler\Field;

use Eureka\Component\Orm\Exception\GeneratorException;
use Eureka\Component\Orm\Generator\Type;

/**
 * @phpstan-type FieldType object{
 *      Field: string,
 *      Key: string,
 *      Type: string,
 *      Comment: string,
 *      Null: string,
 *      Default: string,
 *      Extra: string,
 *  }&\stdClass
 */
class Field
{
    protected string $name = '';
    protected string|int|float|bool|null $default = null;
    protected bool $isNullable = false;
    protected bool $isAutoIncrement = false;
    protected bool $isPrimaryKey = false;
    protected bool $isKey = false;
    protected bool $hasValidation = false;
    protected bool $hasValidationAuto = false;
    protected Type\TypeInterface $type;

    /** @var string[] $dbPrefixes field prefix */
    protected array $dbPrefixes = [];

    /** @var array{type?: string, options?: array<string, string|int|float>} $validation Validation config */
    protected array $validation = [];

    /**
     * Field constructor.
     *
     * @param FieldType $field
     * @param string[] $dbPrefixes
     * @param array{
     *          extended_validation?: array<array{type?: string, options?: array<string, string|int|float>}>,
     *          enabled?: bool,
     *          auto?: bool
     *      } $validationConfig
     * @throws GeneratorException
     */
    public function __construct(\stdClass $field, array $dbPrefixes = [], array $validationConfig = [])
    {
        $this->setData($field);

        $this->dbPrefixes = $dbPrefixes;

        $this->validation = ['type' => '', 'options' => []];
        if (isset($validationConfig['extended_validation'][$field->Field])) {
            $this->validation = $validationConfig['extended_validation'][$field->Field];
        }

        $this->hasValidation = $validationConfig['enabled'] ?? false;

        $isAuto = $validationConfig['auto'] ?? false;
        $this->hasValidationAuto = $this->hasValidation && $isAuto;
    }

    public function getName(bool $withoutPrefix = false): string
    {
        if (!$withoutPrefix) {
            return $this->name;
        }

        $name = $this->name;
        foreach ($this->dbPrefixes as $dbPrefixes) {
            if (stripos($name, $dbPrefixes . '_') === 0) {
                return substr($name, strlen($dbPrefixes));
            }
        }

        return $name;
    }

    public function getDefaultValue(): string|int|float|bool|null
    {
        return $this->default;
    }

    public function getType(): Type\TypeInterface
    {
        return $this->type;
    }

    /**
     * @return array{type?: string, options?: array<string, string|int|float>}
     */
    public function getValidation(): array
    {
        return $this->validation;
    }

    public function hasValidation(): bool
    {
        return $this->hasValidation;
    }

    public function hasValidationAuto(): bool
    {
        return $this->hasValidationAuto;
    }

    public function isNullable(): bool
    {
        return $this->isNullable;
    }

    public function isPrimaryKey(): bool
    {
        return $this->isPrimaryKey;
    }

    public function isAutoIncrement(): bool
    {
        return $this->isAutoIncrement;
    }

    /**
     * @param FieldType $field
     * @throws GeneratorException
     */
    protected function setData(\stdClass $field): static
    {
        $nullableField = 'Null';
        $defaultField  = 'Default';

        $this->setName($field->Field);
        $this->setIsPrimaryKey(($field->Key === 'PRI'));
        $this->setIsKey(!empty($field->Key));
        $this->setType($field->Type, $field->Comment);
        $this->setIsNullable(($field->{$nullableField} === 'YES'));
        $this->setDefaultValue($field->{$defaultField});
        $this->setExtra($field->Extra);

        return $this;
    }

    protected function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @throws GeneratorException
     */
    protected function setType(string $type, string $comment): static
    {
        $this->type = Type\Factory::create($type, $comment);

        return $this;
    }

    protected function setIsNullable(bool $isNullable): static
    {
        $this->isNullable = $isNullable;

        return $this;
    }

    protected function setIsPrimaryKey(bool $isPrimaryKey): static
    {
        $this->isPrimaryKey = $isPrimaryKey;

        return $this;
    }

    protected function setIsKey(bool $isKey): self
    {
        $this->isKey = $isKey;

        return $this;
    }

    protected function setDefaultValue(string|int|float|bool|null $default): static
    {
        $isTimeType = ($this->getType() instanceof Type\TypeTimestamp || $this->getType() instanceof Type\TypeDatetime);

        if ($this->isNullable() && $default === null) {
            $this->default = 'null';

            return $this;
        }

        //~ Handle date time that have default value as current timestamp but not nullable
        if ($isTimeType && ($default === 'CURRENT_TIMESTAMP' || $default === 'CURRENT_TIMESTAMP()')) {
            $this->default = $this->getType()->getEmptyValue();

            return $this;
        }

        if ($default === null) {
            $this->default = '';

            return $this;
        }

        $this->default = match ((string) $this->getType()) {
            'string' => "'" . trim((string) $default, "'") . "'",
            'bool'   => var_export((bool) $default, true),
            default  => $default,
        };

        return $this;
    }

    protected function setExtra(string $extra): static
    {
        if (empty($extra)) {
            return $this;
        }

        if ($extra === 'auto_increment') {
            $this->isAutoIncrement = true;
        }

        return $this;
    }
}
