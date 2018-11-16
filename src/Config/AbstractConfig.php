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

    /** @var string $baseNamespaceForRepository */
    protected $baseNamespaceForRepository = '';

    /** @var string $basePathForData */
    protected $basePathForData = '';

    /** @var string $basePathForMapper */
    protected $basePathForMapper = '';

    /** @var string $basePathForMapper */
    protected $basePathForRepository = '';

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

    /** @var array $validation Validation config. */
    protected $validation = [];

    /**
     * Initialize config.
     *
     * @param  array $config
     * @return $this
     */
    abstract protected function init($config): ConfigInterface;

    /**
     * Init config & validate it.
     *
     * @param  array $config
     * @throws \Exception
     */
    public function __construct(array $config)
    {
        $this->init($config);
        $this->validate();
    }

    /**
     * {@inheritdoc}
     */
    public function getAuthor(): string
    {
        return $this->author;
    }

    /**
     * {@inheritdoc}
     */
    public function getCopyright(): string
    {
        return $this->copyright;
    }

    /**
     * {@inheritdoc}
     */
    public function getClassname(): string
    {
        return $this->classname;
    }

    /**
     * {@inheritdoc}
     */
    public function getDbConfig(): string
    {
        return strtolower($this->dbConfig);
    }

    /**
     * {@inheritdoc}
     */
    public function getDbService(): string
    {
        return $this->dbService;
    }

    /**
     * {@inheritdoc}
     */
    public function getDbTable(): string
    {
        return $this->dbTable;
    }

    /**
     * {@inheritdoc}
     */
    public function getDbPrefix(): string
    {
        return $this->dbPrefix;
    }

    /**
     * {@inheritdoc}
     */
    public function hasCache(): bool
    {
        return $this->hasCache;
    }

    /**
     * {@inheritdoc}
     */
    public function getCachePrefix(): string
    {
        return strtr(strtolower($this->cachePrefix), '_', '.');
    }

    /**
     * {@inheritdoc}
     */
    public function getValidation(): array
    {
        return $this->validation;
    }

    /**
     * {@inheritdoc}
     */
    public function getAllJoin(): array
    {
        return $this->joinList;
    }

    /**
     * {@inheritdoc}
     */
    public function setJoinList(array $joinList): ConfigInterface
    {
        $this->joinList = $joinList;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getBaseNamespaceForData(): string
    {
        return $this->baseNamespaceForData;
    }

    /**
     * {@inheritdoc}
     */
    public function getBaseNamespaceForMapper(): string
    {
        return $this->baseNamespaceForMapper;
    }

    /**
     * {@inheritdoc}
     */
    public function getBaseNamespaceForRepository(): string
    {
        return $this->baseNamespaceForRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function getBasePathForData(): string
    {
        return trim($this->basePathForData, '/\\');
    }

    /**
     * {@inheritdoc}
     */
    public function getBasePathForMapper(): string
    {
        return trim($this->basePathForMapper, '/\\');
    }

    /**
     * {@inheritdoc}
     */
    public function getBasePathForRepository(): string
    {
        return trim($this->basePathForRepository, '/\\');
    }

    /**
     * Check if config has required values.
     *
     * @return $this
     * @throws \InvalidArgumentException
     */
    protected function validate(): ConfigInterface
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
