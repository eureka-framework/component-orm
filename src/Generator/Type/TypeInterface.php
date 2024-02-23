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
interface TypeInterface
{
    /**
     * Get type string (int, bool...)
     *
     * @return string
     */
    public function getAsString(): string;

    /**
     * Get empty value for this type.
     *
     * @return string
     */
    public function getEmptyValue(): string;

    /**
     * Get length of type
     *
     * @return int
     */
    public function getLength(): int;

    /**
     * Get validator type|class name.
     *
     * @return string
     */
    public function getValidatorType(): string;

    /**
     * If type is unsigned
     *
     * @return bool
     */
    public function isUnsigned(): bool;


    /**
     * Number of characters length (string). For numbers, number of digit "displayed".
     *
     * @param  int $length
     * @return static
     */
    public function setLength(int $length): static;

    /**
     * Set is unsigned.
     *
     * @param  bool $isUnsigned
     * @return static
     */
    public function setIsUnsigned(bool $isUnsigned): static;

    /**
     * Set other data.
     *
     * @param  string $other
     * @return static
     */
    public function setOther(string $other): static;

    /**
     * Get as string.
     *
     * @return string
     */
    public function __toString(): string;
}
