<?php

/**
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eureka\Component\Orm\Config;

/**
 * Data Mapper config interface for db/table orm generator.
 *
 * @author  Romain Cottard
 */
interface ConfigInterface
{
    /**
     * Get Header file author.
     *
     * @return string
     */
    public function getAuthor();

    /**
     * Get Header file version
     *
     * @return string
     */
    public function getVersion();

    /**
     * Get base namespace for "data" files for generated files.
     *
     * @return string
     */
    public function getBaseNamespaceForData();

    /**
     * Get base namespace for "mapper" files for generated files.
     *
     * @return string
     */
    public function getBaseNamespaceForMapper();

    /**
     * Get base path for "data" files for generated files.
     *
     * @return string
     */
    public function getBasePathForData();

    /**
     * Get base path for "mapper" files for generated files.
     *
     * @return string
     */
    public function getBasePathForMapper();

    /**
     * Get namespace for generated files.
     *
     * @return string
     */
    public function getNamespace();

    /**
     * Get classname for the generated files
     *
     * @return string
     */
    public function getClassname();

    /**
     * Get database config name (catalog, catalog_import...)
     *
     * @return string
     */
    public function getDbConfig();

    /**
     * Get database table to generate.
     *
     * @return string
     */
    public function getDbTable();

    /**
     * Get database table prefix.
     *
     * @return string
     */
    public function getDbPrefix();

    /**
     * Return true if cache is active, false in otherwise.
     *
     * @return bool
     */
    public function hasCache();

    /**
     * Get cache name to use.
     *
     * @return string
     */
    public function getCacheName();

    /**
     * Get cache prefix for main data key.
     *
     * @return string
     */
    public function getCachePrefix();

    /**
     * Get Config object(s) for "joined" tables
     *
     * @return ConfigInterface[]
     */
    public function getAllJoin();
}
