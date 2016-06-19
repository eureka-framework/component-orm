<?php

/**
 * Copyright (c) 2010-2016 Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eureka\Component\Orm\Config;

/**
 * Data Mapper config interface for db/table orm generator.
 *
 * @author  Romain Cottard
 * @version 2.0.0
 */
abstract class ConfigAbstract implements ConfigInterface
{
    /**
     * @var string $version
     */
    protected $version = '1.0.0';

    /**
     * @var bool $hasCache
     */
    protected $hasCache = false;

    /**
     * @var string $author
     */
    protected $author = '';

    /**
     * @var string $classname
     */
    protected $classname = '';

    /**
     * @var string $namespace
     */
    protected $namespace = '';

    /**
     * @var string $dbConfig Database config name.
     */
    protected $dbConfig = '';

    /**
     * @var string $dbTable Table name
     */
    protected $dbTable = '';

    /**
     * @var string $dbPrefix Table prefix to remove from method name.
     */
    protected $dbPrefix = '';

    /**
     * @var string $cacheName
     */
    protected $cacheName = '';

    /**
     * @var string $cachePrefix
     */
    protected $cachePrefix = '';

    /**
     * @var ConfigInterface $joinList
     */
    protected $joinList = array();

    /**
     * Initialize config.
     *
     * @param  array $config
     * @param  array $global
     * @return $this
     */
    abstract protected function init($config, $global);

    /**
     * Init config & validate it.
     *
     * @param  array $config
     * @param  array $global
     * @throws \Exception
     */
    public function __construct($config, $global)
    {
        $this->init($config, $global);
        $this->validate();
    }

    /**
     * Get Header file author.
     *
     * @return string
     */
    public function getAuthor()
    {
        return $this->author;
    }

    /**
     * Get Header file version
     *
     * @return string
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * Get namespace for generated files.
     *
     * @return string
     */
    public function getNamespace()
    {
        return $this->namespace;
    }

    /**
     * Get classname for the generated files
     *
     * @return string
     */
    public function getClassname()
    {
        return $this->classname;
    }

    /**
     * Get database config name (catalog, catalog_import...)
     *
     * @return string
     */
    public function getDbConfig()
    {
        return strtolower($this->dbConfig);
    }

    /**
     * Get database table to generate.
     *
     * @return string
     */
    public function getDbTable()
    {
        return $this->dbTable;
    }

    /**
     * Get database table prefix.
     *
     * @return string
     */
    public function getDbPrefix()
    {
        return $this->dbPrefix;
    }

    /**
     * Return true if cache is active, false in otherwise.
     *
     * @return bool
     */
    public function hasCache()
    {
        return $this->hasCache;
    }

    /**
     * Get cache name to use.
     *
     * @return string
     */
    public function getCacheName()
    {
        return $this->cacheName;
    }

    /**
     * Get cache prefix for main data key.
     *
     * @return string
     */
    public function getCachePrefix()
    {
        return $this->cachePrefix;
    }

    /**
     * Get Config object(s) for "joined" tables
     *
     * @return ConfigInterface[]
     */
    public function getAllJoin()
    {
        return $this->joinList;
    }

    /**
     * Check if config has required values.
     *
     * @return $this
     * @throws \Exception
     */
    protected function validate()
    {
        if (empty($this->author)) {
            throw new \Exception('Author is empty!');
        }

        if (empty($this->classname)) {
            throw new \Exception('Class name is empty!');
        }

        if (empty($this->namespace)) {
            throw new \Exception('Namespace is empty!');
        }

        if (empty($this->dbConfig)) {
            throw new \Exception('Database config name is empty!');
        }

        if (empty($this->dbTable)) {
            throw new \Exception('Database table name is empty!');
        }

        if (empty($this->cacheName)) {
            throw new \Exception('Cache name is empty!');
        }

        if (empty($this->cachePrefix)) {
            throw new \Exception('Cache prefix is empty!');
        }

        return $this;
    }
}
