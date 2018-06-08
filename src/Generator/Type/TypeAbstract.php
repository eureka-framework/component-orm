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

    /** @var int $length Number of characters length (string). For numbers, number of digit "displayed". 0 for unlimited. */
    protected $length = 0;

    /** @var string $other Other data. */
    protected $other = '';

    /** @var string $emptyValue String for empty value. */
    protected $emptyValue = '';

    /** @var string $validatorClass Validator class */
    protected $validatorClass = '';

    /** @var array $validatorOptions Validator options */
    protected $validatorOptions = [];

    /**
     * {@inheritdoc}
     */
    public function getCastDb()
    {
        return $this->castDb;
    }

    /**
     * {@inheritdoc}
     */
    public function getCastMethod()
    {
        return $this->castMethod;
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * {@inheritdoc}
     */
    public function getEmptyValue()
    {
        return $this->emptyValue;
    }

    /**
     * {@inheritdoc}
     */
    public function getLength()
    {
        return $this->length;
    }

    /**
     * {@inheritdoc}
     */
    public function getValidatorClass()
    {
        return $this->validatorClass;
    }

    /**
     * {@inheritdoc}
     */
    public function isUnsigned()
    {
        return $this->isUnsigned;
    }

    /**
     * Number of characters length (string). For numbers, number of digit "displayed".
     *
     * @param  int $length
     * @return $this
     */
    public function setLength($length)
    {
        $this->length = (int) $length;

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
