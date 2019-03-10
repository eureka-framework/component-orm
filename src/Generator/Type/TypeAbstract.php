<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

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

    /** @var string $validatorType Validator type|class */
    protected $validatorType = '';

    /** @var array $validatorOptions Validator options */
    protected $validatorOptions = [];

    /**
     * @return string
     */
    public function getCastDb(): string
    {
        return $this->castDb;
    }

    /**
     * @return string
     */
    public function getCastMethod(): string
    {
        return $this->castMethod;
    }

    /**
     * @return string
     */
    public function getAsString(): string
    {
        return $this->type;
    }

    /**
     * @return string
     */
    public function getEmptyValue(): string
    {
        return $this->emptyValue;
    }

    /**
     * @return int
     */
    public function getLength(): int
    {
        return $this->length;
    }

    /**
     * @return string
     */
    public function getValidatorType(): string
    {
        return $this->validatorType;
    }

    /**
     * @return bool
     */
    public function isUnsigned(): bool
    {
        return $this->isUnsigned;
    }

    /**
     * Number of characters length (string). For numbers, number of digit "displayed".
     *
     * @param  int $length
     * @return $this
     */
    public function setLength(int $length): TypeInterface
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
    public function setIsUnsigned(bool $isUnsigned): TypeInterface
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
    public function setOther(string $other): TypeInterface
    {
        $this->other = $other;

        return $this;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->getAsString();
    }
}
