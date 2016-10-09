<?php

/**
 * Copyright (c) 2010-2016 Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eureka\Component\Orm\Type;

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
     * @param  string $sqlType
     * @return TypeInterface
     * @throws \RangeException
     * @throws \Exception
     */
    public static function create($sqlType)
    {

        $matches = array();
        if (!(bool) preg_match('`([a-z]+)\(?([0-9]*)\)? ?(.*)`', $sqlType, $matches)) {
            throw new \Exception();
        }

        $type    = (string) $matches[1];
        $display = (int) $matches[2];
        $other   = (string) $matches[3];

        switch (strtolower($type)) {
            //~ Special case for tinyint used as boolean value.
            case 'tinyint':
                $type = ($display === 1 ? new TypeBool() : new TypeTinyint());
                break;
            //~ Other case
            default:
                $classname = __NAMESPACE__ . '\Type' . ucfirst($type);

                if (!class_exists($classname)) {
                    throw new \RangeException('Sql type cannot be converted into php type! (type: ' . $type . ')');
                }

                $type = new $classname();
                break;
        }

        $type->setDisplay($display);

        switch (strtolower($other)) {
            case 'unsigned':
                $type->setIsUnsigned(true);
                break;
            default:
                $type->setOther($other);
        }

        return $type;
    }
}
