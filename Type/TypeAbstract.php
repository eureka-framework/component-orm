<?php

/**
 * Copyright (c) 2010-2016 Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eureka\Component\Orm\Type;

/**
 * Type interface
 *
 * @author Romain Cottard
 * @version 2.0.0
 */
abstract class TypeAbstract implements TypeInterface
{
    /***
     * @var string $castDb
     */
    protected $castDb = '';
    /***
     * @var string $castMethod
     */
    protected $castMethod = '';

    /**
     * @var string $type
     */
    protected $type = '';

    /**
     * @var string $other
     */
    protected $isUnsigned = false;

    /**
     * @var int $display Number of "characters" displayed. For number, number of digit "displayed". 0 for unlimited.
     */
    protected $display = 0;

    /**
     * @var string $other
     */
    protected $other = '';

    /**
     * @var string $emptyValue
     */
    protected $emptyValue = '';

    /**
     * Get cast db string ((int), (bool)...)
     *
     * @return string
     */
    public function getCastDb()
    {
        return $this->castDb;
    }

    /**
     * Get cast method string ((int), (bool)...)
     *
     * @return string
     */
    public function getCastMethod()
    {
        return $this->castMethod;
    }

    /**
     * Get type string (int, bool...)
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Return empty value.
     *
     * @return mixed
     */
    public function getEmptyValue()
    {
        return $this->emptyValue;
    }

    /**
     * Get if type is unsigned.
     *
     * @return boolean
     */
    public function isUnsigned()
    {
        return $this->isUnsigned;
    }

    /**
     * Number of "characters" displayed. For number, number of digit "displayed".
     *
     * @param  int $display
     * @return $this
     */
    public function setDisplay($display)
    {
        $this->display = (int) $display;

        return $this;
    }

    /**
     * Set is unsigned.
     *
     * @param  bool $isUnsigned
     * @return $this
     */
    public function setIsUnsigned($isUnsigned)
    {
        $this->isUnsigned = (bool) $isUnsigned;

        return $this;
    }

    /**
     * Set other data.
     *
     * @param  string $other
     * @return $this
     */
    public function setOther($other)
    {
        $this->other = $other;

        return $this;
    }
}
