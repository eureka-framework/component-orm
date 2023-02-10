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
 * Sql field data class
 *
 * @author Romain Cottard
 */
class Field
{
    /** @var string $name field name */
    protected string $name = '';

    /** @var string[] $dbPrefixes field prefix */
    protected array $dbPrefixes = [];

    /** @var mixed $default Default value. */
    protected $default = null;

    /** @var bool $isNullable If field can be null. */
    protected bool $isNullable = false;

    /** @var bool $isAutoIncrement Is auto-increment field. */
    protected bool $isAutoIncrement = false;

    /** @var bool $isPrimaryKey Is primary key. */
    protected bool $isPrimaryKey = false;

    /** @var bool $isKey Is key (index, primary or unique). */
    protected bool $isKey = false;

    /** @var Type\TypeInterface $type Type instance. */
    protected Type\TypeInterface $type;

    /** @var array $validation Validation config if provided. */
    protected array $validation = [];

    /** @var bool $hasValidation */
    protected bool $hasValidation = false;

    /** @var bool $hasValidationAuto */
    protected bool $hasValidationAuto = false;

    /**
     * Field constructor.
     *
     * @param \stdClass $field
     * @param array|string $dbPrefixes
     * @param array $validationConfig
     * @throws GeneratorException
     */
    public function __construct(\stdClass $field, $dbPrefixes = [], array $validationConfig = [])
    {
        $this->setData($field);

        $this->dbPrefixes = $dbPrefixes;
        $this->validation = $validationConfig['extended_validation'][$field->Field] ?? [];

        $this->hasValidation     = (bool) ($validationConfig['enabled'] ?? false);
        $this->hasValidationAuto = $this->hasValidation && (bool) ($validationConfig['auto'] ?? false);
    }

    /**
     * Get name.
     * Can remove table prefix.
     *
     * @param  bool $withoutPrefix
     * @return string
     */
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


    /**
     * Get default value.
     *
     * @return mixed
     */
    public function getDefaultValue()
    {
        return $this->default;
    }

    /**
     * Get type.
     *
     * @return Type\TypeInterface
     */
    public function getType(): Type\TypeInterface
    {
        return $this->type;
    }

    /**
     * @return array
     */
    public function getValidation(): array
    {
        return $this->validation;
    }

    /**
     * @return bool
     */
    public function hasValidation(): bool
    {
        return $this->hasValidation;
    }

    /**
     * @return bool
     */
    public function hasValidationAuto(): bool
    {
        return $this->hasValidationAuto;
    }

    /**
     * Get if value can be null.
     *
     * @return bool
     */
    public function isNullable(): bool
    {
        return $this->isNullable;
    }

    /**
     * Get if field is in primary key.
     *
     * @return bool
     */
    public function isPrimaryKey(): bool
    {
        return $this->isPrimaryKey;
    }

    /**
     * Get if field is in key (primary, index, unique...)
     *
     * @return bool
     * @codeCoverageIgnore
     */
    public function isKey(): bool
    {
        return $this->isKey;
    }

    /**
     * Get if value is auto incremented
     *
     * @return bool
     */
    public function isAutoIncrement(): bool
    {
        return $this->isAutoIncrement;
    }

    /**
     * Set field data from db query
     *
     * @param \stdClass $field
     * @return Field
     * @throws GeneratorException
     */
    protected function setData(\stdClass $field): self
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

    /**
     * Set field name.
     *
     * @param  string $name
     * @return $this
     */
    protected function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Set field type
     *
     * @param string $type
     * @param string $comment
     * @return Field
     * @throws GeneratorException
     */
    protected function setType(string $type, string $comment): self
    {
        $this->type = Type\Factory::create($type, $comment);

        return $this;
    }

    /**
     * Set if field can to be null.
     *
     * @param  bool $isNullable
     * @return $this
     */
    protected function setIsNullable(bool $isNullable): self
    {
        $this->isNullable = (bool) $isNullable;

        return $this;
    }

    /**
     * Set if field is in primary key
     *
     * @param  bool $isPrimaryKey
     * @return $this
     */
    protected function setIsPrimaryKey(bool $isPrimaryKey): self
    {
        $this->isPrimaryKey = (bool) $isPrimaryKey;

        return $this;
    }

    /**
     * Set if field has key (primary, index, unique...)
     *
     * @param  bool $isKey
     * @return $this
     */
    protected function setIsKey(bool $isKey): self
    {
        $this->isKey = (bool) $isKey;

        return $this;
    }

    /**
     * Set default value.
     *
     * @param  mixed $default
     * @return $this
     */
    protected function setDefaultValue($default): self
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

        switch ((string) $this->getType()) {
            case 'string':
                $this->default = "'" . trim($default, "'") . "'";
                break;
            case 'bool':
                $this->default = var_export((bool) $default, true);
                break;

            default:
                $this->default = $default;
        }

        return $this;
    }

    /**
     * Set extra info.
     *
     * @param  string $extra
     * @return $this
     */
    protected function setExtra(string $extra): self
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
