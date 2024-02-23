<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Eureka\Component\Orm\Generator\Type;

use Eureka\Component\Orm\Exception\GeneratorException;

/**
 * Factory to instantiate type.
 *
 * @author Romain Cottard
 */
class Factory
{
    /**
     * Instantiate new type.
     *
     * @param string $sqlType
     * @param string $sqlComment
     * @return TypeInterface
     * @throws GeneratorException
     */
    public static function create(string $sqlType, string $sqlComment): TypeInterface
    {
        $matches = array();
        if (!(bool) preg_match('`^([a-z]+)\(?(\d*)\)? ?(.*)$`i', $sqlType, $matches)) {
            throw new GeneratorException('Invalid sql type');
        }

        $typeName = strtolower((string) $matches[1]);
        $length   = (int) $matches[2];
        $other    = strtolower((string) $matches[3]);

        if (strtolower($typeName) === 'tinyint') {
            //~ Special case for tinyint used as boolean value.
            $type = (($length === 1 || str_contains($sqlComment, 'ORMTYPE:bool'))
                ? new TypeBool()
                : new TypeTinyint())
            ;
        } else {
            /** @var class-string<TypeInterface> $classname */
            $classname = __NAMESPACE__ . '\Type' . ucfirst($typeName);

            if (!class_exists($classname)) {
                throw new GeneratorException("Sql type cannot be converted into php type! (type: $typeName)");
            }

            $type = new $classname();
        }

        $type->setLength($length);

        if (strtolower($other) === 'unsigned') {
            $type->setIsUnsigned(true);
        } else {
            $type->setOther($other);
        }

        return $type;
    }
}
