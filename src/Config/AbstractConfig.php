<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eureka\Component\Orm\Config;

/**
 * Abstract Data Mapper config class for db/table orm generator.
 *
 * @author Romain Cottard
 */
abstract class AbstractConfig implements ConfigInterface
{
    /** @var bool $hasCache If use cache in orm classes */
    protected $hasCache = false;

    /** @var string $author Comment author name <email> */
    protected $author = '';

    /** @var string $copyright Header file copyright */
    protected $copyright = '';

    /** @var string $classname Base class name of generated classes. */
    protected $classname = '';

    /** @var string $baseNamespaceForData */
    protected $baseNamespaceForData = '';

    /** @var string $baseNamespaceForMapper */
    protected $baseNamespaceForMapper = '';

    /** @var string $basePathForData */
    protected $basePathForData = '';

    /** @var string $basePathForMapper */
    protected $basePathForMapper = '';

    /** @var string $dbConfig Database config name. */
    protected $dbConfig = '';

    /** @var string $dbService Database service name. */
    protected $dbService = '';

    /** @var string $dbTable Table name */
    protected $dbTable = '';

    /** @var string $dbPrefix Table prefix to remove from method name. */
    protected $dbPrefix = '';

    /** @var string $cachePrefix Cache name prefix. */
    protected $cachePrefix = '';

    /** @var ConfigInterface[] $joinList List of joined config. */
    protected $joinList = [];

    /**
     * Initialize config.
     *
     * @param  array $config
     * @return $this
     */
    abstract protected function init($config);

    /**
     * Init config & validate it.
     *
     * @param  array $config
     * @throws \Exception
     */
    public function __construct($config)
    {
        $this->init($config);
        $this->validate();
    }

    /**
     * {@inheritdoc}
     */
    public function getAuthor()
    {
        return $this->author;
    }

    /**
     * {@inheritdoc}
     */
    public function getCopyright()
    {
        return $this->copyright;
    }

    /**
     * {@inheritdoc}
     */
    public function getClassname()
    {
        return $this->classname;
    }

    /**
     * {@inheritdoc}
     */
    public function getDbConfig()
    {
        return strtolower($this->dbConfig);
    }

    /**
     * {@inheritdoc}
     */
    public function getDbService()
    {
        return $this->dbService;
    }

    /**
     * {@inheritdoc}
     */
    public function getDbTable()
    {
        return $this->dbTable;
    }

    /**
     * {@inheritdoc}
     */
    public function getDbPrefix()
    {
        return $this->dbPrefix;
    }

    /**
     * {@inheritdoc}
     */
    public function hasCache()
    {
        return $this->hasCache;
    }

    /**
     * {@inheritdoc}
     */
    public function getCachePrefix()
    {
        return strtr(strtolower($this->cachePrefix), '_', '.');
    }

    /**
     * {@inheritdoc}
     */
    public function getAllJoin()
    {
        return $this->joinList;
    }

    /**
     * {@inheritdoc}
     */
    public function setJoinList($joinList)
    {
        $this->joinList = $joinList;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getBaseNamespaceForData()
    {
        return $this->baseNamespaceForData;
    }

    /**
     * {@inheritdoc}
     */
    public function getBaseNamespaceForMapper()
    {
        return $this->baseNamespaceForMapper;
    }

    /**
     * {@inheritdoc}
     */
    public function getBasePathForData()
    {
        return trim($this->basePathForData, '/\\');
    }

    /**
     * {@inheritdoc}
     */
    public function getBasePathForMapper()
    {
        return trim($this->basePathForMapper, '/\\');
    }

    /**
     * Check if config has required values.
     *
     * @return $this
     * @throws \InvalidArgumentException
     */
    protected function validate()
    {
        if (empty($this->author)) {
            throw new \InvalidArgumentException('Author is empty!');
        }

        if (empty($this->copyright)) {
            throw new \InvalidArgumentException('Copyright is empty!');
        }

        if (empty($this->classname)) {
            throw new \InvalidArgumentException('Class name is empty!');
        }

        if (empty($this->dbConfig) && empty($this->dbService)) {
            throw new \InvalidArgumentException('Database config & service name are empty!');
        }

        if (empty($this->dbTable)) {
            throw new \InvalidArgumentException('Database table name is empty!');
        }

        if (empty($this->cachePrefix)) {
            throw new \InvalidArgumentException('Cache prefix is empty!');
        }

        if (empty($this->baseNamespaceForData)) {
            throw new \InvalidArgumentException('Data namespace is empty!');
        }

        if (empty($this->baseNamespaceForMapper)) {
            throw new \InvalidArgumentException('Mapper namespace is empty!');
        }

        if (empty($this->basePathForData)) {
            throw new \InvalidArgumentException('Data path is empty!');
        }

        if (empty($this->basePathForMapper)) {
            throw new \InvalidArgumentException('Mapper path is empty!');
        }

        return $this;
    }
}
