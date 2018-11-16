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
     * {@inheritdoc}
     */
    public function getCastDb(): string
    {
        return $this->castDb;
    }

    /**
     * {@inheritdoc}
     */
    public function getCastMethod(): string
    {
        return $this->castMethod;
    }

    /**
     * {@inheritdoc}
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * {@inheritdoc}
     */
    public function getEmptyValue(): string
    {
        return $this->emptyValue;
    }

    /**
     * {@inheritdoc}
     */
    public function getLength(): int
    {
        return $this->length;
    }

    /**
     * {@inheritdoc}
     */
    public function getValidatorType(): string
    {
        return $this->validatorType;
    }

    /**
     * {@inheritdoc}
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
}
