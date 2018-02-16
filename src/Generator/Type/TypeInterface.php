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
interface TypeInterface
{
    /**
     * Get cast db string ((int), (bool)...)
     *
     * @return string
     */
    public function getCastDb();

    /**
     * Get cast method string ((int), (bool)...)
     *
     * @return string
     */
    public function getCastMethod();

    /**
     * Get type string (int, bool...)
     *
     * @return string
     */
    public function getType();

    /**
     * Get empty value for this type.
     *
     * @return bool
     */
    public function getEmptyValue();

    /**
     * If type is unsigned
     *
     * @return bool
     */
    public function isUnsigned();
}
