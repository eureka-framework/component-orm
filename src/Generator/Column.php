<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eureka\Component\Orm\Generator;

use Eureka\Component\Orm\Generator\Type;

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

    /**
     * Column constructor.
     *
     * @param \stdClass $column
     * @param string[]|string $dbPrefixes
     */
    public function __construct(\stdClass $column, $dbPrefixes = [])
    {
        $this->setData($column);
        $this->dbPrefixes = is_string($dbPrefixes) ? [$dbPrefixes] : $dbPrefixes;
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
     * Get method setter.
     *
     * @return string
     */
    public function getSetter()
    {
        $varname = '$' . $this->getPropertyName();
        $autoinc = '';
        $cast    = $this->getType()->getCastMethod() . ' ';

        if ($this->isNullable()) {
            $forceCast = $varname . ' = (' . $varname . ' === null ? ' . $varname . ' : ' . $cast . $varname . ');';
        } else {
            $forceCast = $varname . ' = ' . $cast . $varname . ';';
        }

        list($forceCheck, $exception) = $this->getCheck();

        //~ Prepend with overridden method setAutoIncrementId() for Observer/Subject when we insert new data.
        if ($this->isAutoIncrement()) {
            $autoinc = '
    /**
     * Overridden method setAutoIncrementId().
     *
     * @param  ' . $this->getType()->getType() . ' ' . $varname . '
     * @return $this
     */
    public function setAutoIncrementId(' . $varname . ')
    {
        return $this->' . $this->getMethodNameSet() . '(' . $varname . ');
    }
';
        }

        return $autoinc . '
    /**
     * Set value for field "' . $this->getName() . '"
     *
     * @param  ' . $this->getType()->getType() . ' ' . $varname . '
     * @return $this' . (!empty($exception) ? "\n     * @throws " . $exception : '') . '
     */
    public function ' . $this->getMethodNameSet() . '(' . $varname . ')
    {
        ' . $forceCast . (!empty($forceCheck) ? "\n" . $forceCheck . "\n" : '') . '

        if ($this->exists() && $this->' . $this->getPropertyName() . ' !== ' . $varname . ') {
            $this->updated[\'' . $this->getPropertyName() . '\'] = true;
        }

        $this->' . $this->getPropertyName() . ' = ' . $varname . ';

        return $this;
    }';
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
     * @return mixed
     */
    public function getDefault($forceReturn = false)
    {
        $default = $this->default;

        if ($forceReturn && $this->default === '') {
            $default = $this->getType()->getEmptyValue();
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
}
