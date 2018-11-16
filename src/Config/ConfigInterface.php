<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Eureka\Component\Orm\Config;

/**
 * Data Mapper config interface for db/table orm generator.
 *
 * @author Romain Cottard
 */
interface ConfigInterface
{
    /**
     * Get Header file author.
     *
     * @return string
     */
    public function getAuthor(): string;

    /**
     * Get Header file copyright
     *
     * @return string
     */
    public function getCopyright(): string;

    /**
     * Get base namespace for "data" files for generated files.
     *
     * @return string
     */
    public function getBaseNamespaceForData(): string;

    /**
     * Get base namespace for "mapper" files for generated files.
     *
     * @return string
     */
    public function getBaseNamespaceForMapper(): string;

    /**
     * Get base namespace for "repository" files for generated files.
     *
     * @return string
     */
    public function getBaseNamespaceForRepository(): string;

    /**
     * Get base path for "data" files for generated files.
     *
     * @return string
     */
    public function getBasePathForData(): string;

    /**
     * Get base path for "mapper" files for generated files.
     *
     * @return string
     */
    public function getBasePathForMapper(): string;

    /**
     * Get base path for "repository" files for generated files.
     *
     * @return string
     */
    public function getBasePathForRepository(): string;

    /**
     * Get classname for the generated files
     *
     * @return string
     */
    public function getClassname(): string;

    /**
     * Get database config name (catalog, catalog_import...)
     *
     * @return string
     */
    public function getDbConfig(): string;

    /**
     * Get database table to generate.
     *
     * @return string
     */
    public function getDbTable(): string;

    /**
     * Get database service name.
     *
     * @return string
     */
    public function getDbService(): string;

    /**
     * Get database table prefix.
     *
     * @return string
     */
    public function getDbPrefix(): string;

    /**
     * Return true if cache is active, false in otherwise.
     *
     * @return bool
     */
    public function hasCache(): bool;

    /**
     * Get cache prefix for main data key.
     *
     * @return string
     */
    public function getCachePrefix(): string;

    /**
     * Get validation config.
     *
     * @return array
     */
    public function getValidation(): array;

    /**
     * Get Config object(s) for "joined" tables
     *
     * @return ConfigInterface[]
     */
    public function getAllJoin(): array;

    /**
     * @param  \Eureka\Component\Orm\Config\ConfigInterface[] $joinList
     * @return $this
     */
    public function setJoinList(array $joinList): ConfigInterface;
}
