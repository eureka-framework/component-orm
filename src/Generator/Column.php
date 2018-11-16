<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eureka\Component\Orm\Generator;

use Eureka\Component\Orm\Generator\Type;
use Eureka\Component\Validation\Validator\IntegerValidator;

/**
 * Sql column data class
 *
 * @author Romain Cottard
 */
class Column
{
    /** @var string $name Column name */
    protected $name = '';

    /** @var string[] $dbPrefixes Column prefix */
    protected $dbPrefixes = [];

    /** @var mixed $default Default value. */
    protected $default = null;

    /** @var bool $isNullable If column can be null. */
    protected $isNullable = false;

    /** @var bool $isAutoIncrement Is auto-increment column. */
    protected $isAutoIncrement = false;

    /** @var bool $isPrimaryKey Is primary key. */
    protected $isPrimaryKey = false;

    /** @var bool $isKey Is key (index, primary or unique). */
    protected $isKey = false;

    /** @var Type\TypeInterface $type Type instance. */
    protected $type = null;

    /** @var array $validation Validation config if provided. */
    protected $validation = [];

    /** @var bool $hasValidation */
    protected $hasValidation = false;

    /** @var bool $hasValidationAuto */
    protected $hasValidationAuto = false;

    /**
     * Column constructor.
     *
     * @param \stdClass $column
     * @param string[]|string $dbPrefixes
     * @param array $validation
     * @throws \Exception
     */
    public function __construct(\stdClass $column, $dbPrefixes = [], array $validation = [])
    {
        $this->setData($column);
        $this->dbPrefixes = is_string($dbPrefixes) ? [$dbPrefixes] : $dbPrefixes;
        $this->validation = isset($validation['extended_validation'][$column->Field]) ? $validation['extended_validation'][$column->Field] : [];

        $this->hasValidation     = (isset($validation['enabled']) && (bool) $validation['enabled']);
        $this->hasValidationAuto = $this->hasValidation && (isset($validation['auto']) && (bool) $validation['auto']);
    }

    /**
     * Get property declaration.
     *
     * @return string
     */
    public function getProperty()
    {
        return '
    /** @var ' . $this->getType()
                ->getType() . ' $' . $this->getPropertyName() . ' Property ' . $this->getPropertyName() . ' */
    protected $' . $this->getPropertyName() . ' = ' . $this->getDefault(true) . ';';
    }

    /**
     * Get method getter.
     *
     * @return string
     */
    public function getGetter()
    {
        return '
    /**
     * Get value for field "' . $this->getName() . '"
     *
     * @return ' . $this->getType()->getType() . '
     */
    public function ' . $this->getMethodNameGet() . '()
    {
        return $this->' . $this->getPropertyName() . ';
    }';
    }

    /**
     * @return string
     */
    public function getGetterName()
    {
        return $this->getMethodNameGet();
    }

    /**
     * @return string
     */
    public function getSetterName()
    {
        return $this->getMethodNameSet();
    }

    /**
     * Get method setter.
     *
     * @return string
     */
    public function getSetter()
    {
        $varname = '$' . $this->getPropertyName();
        $autoinc = '';
        $type    = $this->getType();
        $cast    = $type->getCastMethod() . ' ';

        $validation = $this->getValidations($type, $varname);

        $glue = "\n        ";
        if ($this->isNullable() && !empty($validation)) {
            $forceCast = 'if (' . $varname . ' !== null) {
            ' . $varname . ' = ' . $cast . $varname . ';' . (!empty($validation) ? "\n$glue    " . implode("$glue    ", $validation) : '') . '
        }';
        } elseif ($this->isNullable()) {
            $forceCast = $varname . ' = (' . $varname . ' === null ? ' . $varname . ' : ' . $cast . $varname . ');';
        } else {
            $forceCast = $varname . ' = ' . $cast . $varname . ';' . (!empty($validation) ? "\n$glue" . implode($glue, $validation) : '');
        }

        list($forceCheck, $exception) = $this->getCheck();

        //~ Prepend with overridden method setAutoIncrementId() for Observer/Subject when we insert new data.
        if ($this->isAutoIncrement()) {
            $autoinc = '
    /**
     * Overridden method setAutoIncrementId().
     *
     * @param  ' . $type->getType() . ' ' . $varname . '
     * @return $this
     */
    public function setAutoIncrementId(int ' . $varname . '): EntityInterface
    {
        return $this->' . $this->getMethodNameSet() . '(' . $varname . ');
    }
';
        }

        return $autoinc . '
    /**
     * Set value for field "' . $this->getName() . '"
     *
     * @param  ' . $type->getType() . ' ' . $varname . '
     * @return $this' . (!empty($exception) ? "\n     * @throws " . $exception : '') . '
     */
    public function ' . $this->getMethodNameSet() . '(' . $varname . ')
    {
        ' . $forceCast . (!empty($forceCheck) ? "\n" . $forceCheck : '') . '

        if ($this->exists() && $this->' . $this->getPropertyName() . ' !== ' . $varname . ') {
            $this->updated[\'' . $this->getPropertyName() . '\'] = true;
        }

        $this->' . $this->getPropertyName() . ' = ' . $varname . ';

        return $this;
    }';
    }

    /**
     * @return string
     */
    public function getValidatorConfig()
    {
        $validation = [];

        $type = $this->getType();

        if (!empty($this->validation)) {
            $validation['type']    = $this->validation['type'];
            $validation['options'] = $this->getValidatorOptions(empty($this->validation['options']));
        } elseif (!empty($type->getValidatorType())) {
            $validation['type']    = $type->getValidatorType();
            $validation['options'] = $this->getValidatorOptions(empty($this->validation['options']));
        }

        if (empty($validation)) {
            return '';
        }

        $options = '[]';
        if (!empty($validation['options'])) {
            $options = '';
            foreach($validation['options'] as $name => $value) {
                $options .= '
        ' . $name . ': '  . var_export($value, true);
            }
        }

        return '
    ' . $this->getName() . ':
      type: \'' . $validation['type'] . '\'
      options: ' . $options . "\n";
    }

    /**
     * Get name.
     * Can remove table prefix.
     *
     * @param  bool $withoutPrefix
     * @return string
     */
    public function getName($withoutPrefix = false)
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
     * Get type.
     *
     * @return Type\TypeInterface
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Get default value for the column.
     *
     * @param  bool $forceReturn
     * @param  bool $originalType
     * @return mixed
     */
    public function getDefault($forceReturn = false, $originalType = false)
    {
        $default = $this->default;

        if ($forceReturn && $this->default === '') {
            $default = $this->getType()->getEmptyValue();
        }

        if ($originalType) {
            switch ($default)
            {
                case 'true':
                    $default = true;
                    break;
                case 'false':
                    $default = false;
                    break;
                case 'null':
                    $default = null;
                    break;
            }
        }

        return $default;
    }

    /**
     * Get if value can be null.
     *
     * @return bool
     */
    public function isNullable()
    {
        return $this->isNullable;
    }

    /**
     * Get if column is in primary key.
     *
     * @return bool
     */
    public function isPrimaryKey()
    {
        return $this->isPrimaryKey;
    }

    /**
     * Get if column is in key (primary, index, unique...)
     *
     * @return bool
     */
    public function isKey()
    {
        return $this->isKey;
    }

    /**
     * Get if value is auto incremented
     *
     * @return bool
     */
    public function isAutoIncrement()
    {
        return $this->isAutoIncrement;
    }

    /**
     * Set column name.
     *
     * @param  string $name
     * @return $this
     */
    protected function setName($name)
    {
        $this->name = (string) $name;

        return $this;
    }

    /**
     * Set column data from db query
     *
     * @param  \stdClass $column
     * @return $this
     * @throws \Exception
     */
    protected function setData(\stdClass $column)
    {
        $nullableField = 'Null'; // Avoid reformatting with php-cs-fixer

        $this->setName($column->Field);
        $this->setIsPrimaryKey(($column->Key === 'PRI'));
        $this->setIsKey(!empty($column->Key));
        $this->setType($column->Type, $column->Comment);
        $this->setIsNullable(($column->{$nullableField} === 'YES'));
        $this->setDefault($column->Default);
        $this->setExtra($column->Extra);

        return $this;
    }

    /**
     * Get property name for column in data class.
     *
     * @return string
     */
    public function getPropertyName()
    {
        return lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', strtolower($this->getName(true))))));
    }

    /**
     * Get property class name.
     *
     * @return string
     */
    protected function getPropertyClass()
    {
        return (string) str_replace('Type', 'Property', get_class($this->getType()));
    }

    /**
     * Get method name for getter.
     *
     * @return string
     */
    public function getMethodNameGet()
    {
        $toReplace  = array(
            '/^(is_)/i',
            '/^(has_)/i',
            '/^(in_)/i', // db_prefix is empty
            '/(_is_)/i',
            '/(_has_)/i',
            '/(_in_)/i', // db_prefix is not empty
            '/(_)/',
        );
        $methodName = str_replace(' ', '', ucwords(preg_replace($toReplace, ' ', strtolower($this->getName(true)))));

        $type = $this->getType();

        switch (true) {
            case ($type instanceof Type\TypeBool) && (stripos($this->getName(), '_has_') !== false || stripos($this->getName(), 'has_') === 0):
                $methodPrefix = 'has';
                break;
            case ($type instanceof Type\TypeBool) && (stripos($this->getName(), '_is_') !== false || stripos($this->getName(), 'is_') === 0):
                $methodPrefix = 'is';
                break;
            case ($type instanceof Type\TypeBool) && (stripos($this->getName(), '_in_') !== false || stripos($this->getName(), 'in_') === 0):
                $methodPrefix = 'in';
                break;
            default:
                $methodPrefix = 'get';
                break;
        }

        return $methodPrefix . $methodName;
    }

    /**
     * Get method name for setter.
     *
     * @return string
     */
    public function getMethodNameSet()
    {
        return 'set' . str_replace(' ', '', ucwords(str_replace('_', ' ', strtolower($this->getName(true)))));
    }

    /**
     * Set column type
     *
     * @param  string $type
     * @param  string $comment
     * @return $this
     * @throws \Exception
     */
    protected function setType($type, $comment)
    {
        $this->type = Type\Factory::create($type, $comment);

        return $this;
    }

    /**
     * Set if column can to be null.
     *
     * @param  bool $isNullable
     * @return $this
     */
    protected function setIsNullable($isNullable)
    {
        $this->isNullable = (bool) $isNullable;

        return $this;
    }

    /**
     * Set if column is in primary key
     *
     * @param  bool $isPrimaryKey
     * @return $this
     */
    protected function setIsPrimaryKey($isPrimaryKey)
    {
        $this->isPrimaryKey = (bool) $isPrimaryKey;

        return $this;
    }

    /**
     * Set if column has key (primary, index, unique...)
     *
     * @param  bool $isKey
     * @return $this
     */
    protected function setIsKey($isKey)
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
    protected function setDefault($default)
    {
        if ($this->isNullable() && $default === null || ($default === 'CURRENT_TIMESTAMP' && $this->getType() instanceof Type\TypeTimestamp)) {
            $this->default = 'null';

            return $this;
        }

        if ($default === null) {
            $this->default = '';

            return $this;
        }

        switch ($this->getType()->getType()) {
            case 'string':
                $this->default = "'" . $default . "'";
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
    protected function setExtra($extra)
    {
        if (empty($extra)) {
            return $this;
        }

        switch ($extra) {
            case 'auto_increment':
                $this->isAutoIncrement = true;
                break;
        }

        return $this;
    }

    /**
     * Get check condition for numeric values & underflow if necessary.
     *
     * @return array
     */
    protected function getCheck()
    {
        $check = array(0 => '', 1 => '');
        $type  = $this->getType();

        switch ($type->getType()) {
            case 'int':
            case 'float':
                if ($type->isUnsigned()) {
                    $check[1] = '\UnderflowException';
                    $check[0] = '
        if ($' . $this->getPropertyName() . ' < 0) {
            throw new \UnderflowException(\'Value of "' . $this->getPropertyName() . '" must be greater or equal to 0\');
        }';
                }
                break;
        }

        return $check;
    }

    /**
     * @param  bool $auto
     * @param  bool $toString
     * @return array|string
     */
    protected function getValidatorOptions($auto = false, $toString = false)
    {
        if ($auto) {
            $options = $this->getValidatorOptionsFromType($this->getType());
        } else {
            $options = !empty($this->validation['options']) ? $this->validation['options'] : [];
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
     * @param  \Eureka\Component\Orm\Generator\Type\TypeInterface $type
     * @param  string $varname
     * @return string[]
     */
    private function getValidations(Type\TypeInterface $type, $varname)
    {
        $validation = [];

        if ($this->hasValidation && !empty($this->validation)) {
            $validatorType = $this->validation['type'];
        } elseif ($this->hasValidationAuto && !empty($type->getValidatorType())) {
            $validatorType = $type->getValidatorType();
        }

        if (!empty($validatorType) && strpos($validatorType, '\\') !== false) {
            $validation[] = '$validator = new \\' . trim($validatorType, '\\') . '();';
            $validation[] = '$validator->validate(' . $varname . ', ' . $this->getValidatorOptions(empty($this->validation['options']), true) . ');';
        } elseif (!empty($validatorType)) {
            $validation[] = '$validator = $this->getValidator(\'' . $validatorType . '\');';
            $validation[] = '$validator->validate(' . $varname . ', ' . $this->getValidatorOptions(empty($this->validation['options']), true) . ');';
        }

        return $validation;
    }

    /**
     * @param  Type\TypeInterface $type
     * @return array
     */
    private function getValidatorOptionsFromType(Type\TypeInterface $type)
    {
        $options = [];

        switch (get_class($type))
        {
            //~ Case Integers
            case Type\TypeBigint::class:
                $options = array_merge($options, $type->isUnsigned() ? IntegerValidator::BIGINT_UNSIGNED : IntegerValidator::BIGINT_SIGNED);
                break;
            case Type\TypeInt::class:
                $options = array_merge($options, $type->isUnsigned() ? IntegerValidator::INT_UNSIGNED : IntegerValidator::INT_SIGNED);
                break;
            case Type\TypeMediumint::class:
                $options = array_merge($options, $type->isUnsigned() ? IntegerValidator::MEDIUMINT_UNSIGNED : IntegerValidator::MEDIUMINT_SIGNED);
                break;
            case Type\TypeSmallint::class:
                $options = array_merge($options, $type->isUnsigned() ? IntegerValidator::SMALLINT_UNSIGNED : IntegerValidator::SMALLINT_SIGNED);
                break;
            case Type\TypeTinyint::class:
                $options = array_merge($options, $type->isUnsigned() ? IntegerValidator::TINYINT_UNSIGNED : IntegerValidator::TINYINT_SIGNED);
                break;
            //~ Case float
            case Type\TypeFloat::class:
            case Type\TypeDouble::class:
            case Type\TypeDecimal::class:
                $options = array_merge($options, $type->isUnsigned() ? ['min_range' => 0.0] : []);
                break;
            //~ Case Strings
            case Type\TypeLongtext::class:
                if (!$this->isNullable()) {
                    $options['min_length'] = 1;
                }

                $options['max_length'] = 4294967295;
                break;
            case Type\TypeMediumtext::class:
                if (!$this->isNullable()) {
                    $options['min_length'] = 1;
                }

                $options['max_length'] = 16777215;
                break;
            case Type\TypeText::class:
                if (!$this->isNullable()) {
                    $options['min_length'] = 1;
                }

                $options['max_length'] = 65535;
                break;
            case Type\TypeTinytext::class:
                if (!$this->isNullable()) {
                    $options['min_length'] = 1;
                }

                $options['max_length'] = 255;
                break;
            case Type\TypeVarchar::class:
            case Type\TypeChar::class:
                if (!$this->isNullable()) {
                    $options['min_length'] = 1;
                }

                $options['max_length'] = $type->getLength();
                break;
            //~ Case "boolean"
            case Type\TypeBool::class:
                //~ Case Date / Time
            case Type\TypeDateTime::class:
            case Type\TypeDate::class:
            case Type\TypeTime::class:
            case Type\TypeTimestamp::class:
                //~ Default
            default:
                // Nothing to add
                break;
        }

        return $options;
    }
}
