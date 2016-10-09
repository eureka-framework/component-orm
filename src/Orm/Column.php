<?php

/**
 * Copyright (c) 2010-2016 Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eureka\Component\Orm;

use Eureka\Component\Orm\Type;

/**
 * Sql column data class
 *
 * @author Romain Cottard
 */
class Column
{
    /**
     * @var string $name
     */
    protected $name = '';

    /**
     * @var string $dbPrefix
     */
    protected $dbPrefix = '';

    /**
     * @var mixed $default
     */
    protected $default = null;

    /**
     * @var bool $isNullable
     */
    protected $isNullable = false;

    /**
     * @var bool $isAutoIncrement
     */
    protected $isAutoIncrement = false;

    /**
     * @var bool $isPrimaryKey
     */
    protected $isPrimaryKey = false;

    /**
     * @var bool $isKey
     */
    protected $isKey = false;

    /**
     * @var Type\TypeInterface $type
     */
    protected $type = null;

    /**
     * Column constructor.
     *
     * @param string $dbPrefix
     * @param \stdClass $column
     */
    public function __construct(\stdClass $column, $dbPrefix = '')
    {
        $this->setData($column);
        $this->dbPrefix = $dbPrefix;
    }

    /**
     * Get property declaration.
     *
     * @return string
     */
    public function getProperty()
    {
        return '
    /**
     * @var ' . $this->getType()->getType() . ' $' . $this->getPropertyName() . '
     */
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
     * @return self
     */
    public function setAutoIncrementId(' . $varname . ')
    {
        return $this->' . $this->getMethodNameSet() . '(' . $varname . ');
    }';
        }

        return $autoinc . '
    /**
     * Set value for field "' . $this->getName() . '"
     *
     * @param  ' . $this->getType()->getType() . ' ' . $varname . '
     * @return self' . (!empty($exception) ? "\n     * @throws " . $exception : '') . '
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
        $name = $this->name;
        if ($withoutPrefix && stripos($name, $this->dbPrefix) === 0) {
            $name = substr($name, strlen($this->dbPrefix));
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
     * @return self
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
     * @return self
     */
    protected function setData(\stdClass $column)
    {
        $this->setName($column->Field);
        $this->setIsPrimaryKey(($column->Key === 'PRI'));
        $this->setIsKey(!empty($column->Key));
        $this->setType($column->Type);
        $this->setIsNullable(($column->Null === 'YES'));
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
        return str_replace('Type', 'Property', get_class($this->getType()));
    }

    /**
     * Get method name for getter.
     *
     * @return string
     */
    public function getMethodNameGet()
    {
        $methodName = str_replace(' ', '', ucwords(str_replace(array(
            '_is_', '_has_', '_in_', '_',
        ), ' ', strtolower($this->getName(true)))));

        $type = $this->getType();

        switch (true) {
            case ($type instanceof Type\TypeBool) && stripos($this->getName(), '_has_') !== false:
                $methodPrefix = 'has';
                break;
            case ($type instanceof Type\TypeBool) && stripos($this->getName(), '_is_') !== false:
                $methodPrefix = 'is';
                break;
            case ($type instanceof Type\TypeBool) && stripos($this->getName(), '_in_') !== false:
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
     * @return self
     */
    protected function setType($type)
    {
        $this->type = Type\Factory::create($type);

        return $this;
    }

    /**
     * Set if column can to be null.
     *
     * @param  bool $isNullable
     * @return self
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
     * @return self
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
     * @return self
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
     * @return self
     */
    protected function setDefault($default)
    {
        if ($this->isNullable() && $default === null) {
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
     * @return self
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
     * @return string
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
        if ($this->' . $this->getPropertyName() . ' < 0) {
            throw new \UnderflowException(\'Value of "' . $this->getPropertyName() . '" must be greater than 0\');
        }';
                }
                break;
        }

        return $check;
    }
}
