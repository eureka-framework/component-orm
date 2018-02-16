<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eureka\Component\Orm\Generator\Type;

/**
 * Type interface
 *
 * @author Romain Cottard
 */
abstract class TypeAbstract implements TypeInterface
{
    /** @var string $castDb String for cast in query. */
    protected $castDb = '';

    /** @var string $castMethod String for cast in method. */
    protected $castMethod = '';

    /** @var string $type Type name */
    protected $type = '';

    /** @var bool $isUnsigned If type is unsigned */
    protected $isUnsigned = false;

    /** @var int $display Number of "characters" displayed. For number, number of digit "displayed". 0 for unlimited. */
    protected $display = 0;

    /** @var string $other Other data. */
    protected $other = '';

    /** @var string $emptyValue String for empty value. */
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
     * @return bool
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
