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
     * Get cast db string ((int), (bool)...)
     *
     * @return string
     */
    public function getCastDb(): string;

    /**
     * Get cast method string ((int), (bool)...)
     *
     * @return string
     */
    public function getCastMethod(): string;

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
     * Get as string.
     *
     * @return string
     */
    public function __toString(): string;
}
