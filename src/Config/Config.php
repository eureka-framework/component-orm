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
 * @author  Romain Cottard
 */
class Config implements ConfigInterface
{
    /** @var string $author Comment author name <email> */
    protected string $author = '';

    /** @var string $copyright Header file copyright */
    protected string $copyright = '';

    /** @var string $classname Base class name of generated classes. */
    protected string $classname = '';

    /** @var string $baseNamespaceForEntity */
    protected string $baseNamespaceForEntity = '';

    /** @var string $baseNamespaceForMapper */
    protected string $baseNamespaceForMapper = '';

    /** @var string $baseNamespaceForRepository */
    protected string $baseNamespaceForRepository = '';

    /** @var string $basePathForEntity */
    protected string $basePathForEntity = '';

    /** @var string $basePathForMapper */
    protected string $basePathForMapper = '';

    /** @var string $basePathForMapper */
    protected string $basePathForRepository = '';

    /** @var string $dbTable Table name */
    protected string $dbTable = '';

    /** @var string[] $dbPrefix Table prefix to remove from method name. */
    protected array $dbPrefix = [];

    /** @var string $cachePrefix Cache name prefix. */
    protected string $cachePrefix = '';

    /** @var array<array{
     *     eager_loading?: bool,
     *     config: string,
     *     relation: string,
     *     type: string,
     *     keys: array<bool|string>,
     *     instance?: ConfigInterface
     * }> $joinList List of joined config. */
    protected array $joinList = [];

    /** @var array{
     *          extended_validation?: array<array{type?: string, options?: array<string, string|int|float>}>|null,
     *          enabled?: bool,
     *          auto?: bool
     *      } $validation Validation config. */
    protected array $validation = [];

    /**
     * Init config & validate it.
     *
     * @param array{
     *  comment?: array{author?: string, copyright?: string},
     *  class?: array{classname?: string},
     *  namespace?: array{entity?: string, mapper?: string, repository?: string},
     *  path?: array{entity?: string, mapper?: string, repository?: string},
     *  cache?: array{prefix?: string},
     *  database?: array{table?: string, prefix?: string|string[]},
     *  validation?: array{
     *      extended_validation?: array<array{type?: string, options?: array<string, string|int|float>}>|null,
     *      enabled?: bool,
     *      auto?: bool
     *  }
     * } $config $config
     * @throws \InvalidArgumentException
     */
    public function __construct(array $config)
    {
        $this->init($config);
        $this->validate();
    }

    /**
     * Get Header file author.
     *
     * @return string
     */
    public function getAuthor(): string
    {
        return $this->author;
    }

    /**
     * Get Header file copyright
     *
     * @return string
     */
    public function getCopyright(): string
    {
        return $this->copyright;
    }

    /**
     * Get classname for the generated files
     *
     * @return string
     */
    public function getClassname(): string
    {
        return $this->classname;
    }

    /**
     * Get database table to generate.
     *
     * @return string
     */
    public function getDbTable(): string
    {
        return $this->dbTable;
    }

    /**
     * Get database table prefix(es).
     *
     * @return string[]
     */
    public function getDbPrefix(): array
    {
        return $this->dbPrefix;
    }

    /**
     * Get cache prefix for main data key.
     *
     * @return string
     */
    public function getCachePrefix(): string
    {
        return strtr(strtolower($this->cachePrefix), '_', '.');
    }

    /**
     * Get validation config.
     *
     * @return array{
     *          extended_validation?: array<array{type?: string, options?: array<string, string|int|float>}>|null,
     *          enabled?: bool,
     *          auto?: bool
     *      }
     */
    public function getValidation(): array
    {
        return $this->validation;
    }

    /**
     * Get Config object(s) for "joined" tables
     *
     * @return array<array{
     *     eager_loading?: bool,
     *     config: string,
     *     relation: string,
     *     type: string,
     *     keys: array<bool|string>,
     *     instance?: ConfigInterface
     * }>
     */
    public function getAllJoin(): array
    {
        return $this->joinList;
    }

    /**
     * @param array<array{
     *     eager_loading?: bool,
     *     config: string,
     *     relation: string,
     *     type: string,
     *     keys: array<bool|string>,
     *     instance?: ConfigInterface
     * }> $joinList
     * @return $this
     */
    public function setJoinList(array $joinList): ConfigInterface
    {
        $this->joinList = $joinList;

        return $this;
    }

    /**
     * Get base namespace for "entity" files for generated files.
     *
     * @return string
     */
    public function getBaseNamespaceForEntity(): string
    {
        return $this->baseNamespaceForEntity;
    }

    /**
     * Get base namespace for "mapper" files for generated files.
     *
     * @return string
     */
    public function getBaseNamespaceForMapper(): string
    {
        return $this->baseNamespaceForMapper;
    }

    /**
     * Get base namespace for "repository" files for generated files.
     *
     * @return string
     */
    public function getBaseNamespaceForRepository(): string
    {
        return $this->baseNamespaceForRepository;
    }

    /**
     * Get base path for "entity" files for generated files.
     *
     * @return string
     */
    public function getBasePathForEntity(): string
    {
        return rtrim($this->basePathForEntity, '/\\');
    }

    /**
     * Get base path for "mapper" files for generated files.
     *
     * @return string
     */
    public function getBasePathForMapper(): string
    {
        return rtrim($this->basePathForMapper, '/\\');
    }

    /**
     * Get base path for "repository" files for generated files.
     *
     * @return string
     */
    public function getBasePathForRepository(): string
    {
        return rtrim($this->basePathForRepository, '/\\');
    }

    /**
     * Initialize config.
     *
     * @param array{
     *  comment?: array{author?: string, copyright?: string},
     *  class?: array{classname?: string},
     *  namespace?: array{entity?: string, mapper?: string, repository?: string},
     *  path?: array{entity?: string, mapper?: string, repository?: string},
     *  cache?: array{prefix?: string},
     *  database?: array{table?: string, prefix?: string|string[]},
     *  validation?: array{
     *      extended_validation?: array<array{type?: string, options?: array<string, string|int|float>}>|null,
     *      enabled?: bool,
     *      auto?: bool
     *  },
     * } $config
     * @return $this
     */
    protected function init(array $config): ConfigInterface
    {
        //~ Comment
        $this->author    = $config['comment']['author'] ?? '';
        $this->copyright = $config['comment']['copyright'] ?? '';

        //~ Class
        $this->classname = $config['class']['classname'] ?? '';

        //~ Namespace
        $this->baseNamespaceForEntity     = $config['namespace']['entity'] ?? '';
        $this->baseNamespaceForMapper     = $config['namespace']['mapper'] ?? '';
        $this->baseNamespaceForRepository = $config['namespace']['repository'] ?? '';

        //~ Path
        $this->basePathForEntity     = $config['path']['entity'] ?? '';
        $this->basePathForMapper     = $config['path']['mapper'] ?? '';
        $this->basePathForRepository = $config['path']['repository'] ?? '';

        //~ Cache
        $this->cachePrefix = $config['cache']['prefix'] ?? '';

        //~ Db
        $this->dbTable   = $config['database']['table'] ?? '';
        if (!is_array($config['database']['prefix'] ?? [])) {
            $this->dbPrefix = [$config['database']['prefix']];
        } else {
            $this->dbPrefix = $config['database']['prefix'] ?? [];
        }

        //~ Validation
        $this->validation = !empty($config['validation']) ? $config['validation'] : [];

        return $this;
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
            throw new \InvalidArgumentException('Author is empty!'); // @codeCoverageIgnore
        }

        if (empty($this->copyright)) {
            throw new \InvalidArgumentException('Copyright is empty!'); // @codeCoverageIgnore
        }

        if (empty($this->classname)) {
            throw new \InvalidArgumentException('Class name is empty!'); // @codeCoverageIgnore
        }

        if (empty($this->dbTable)) {
            throw new \InvalidArgumentException('Database table name is empty!'); // @codeCoverageIgnore
        }

        if (empty($this->cachePrefix)) {
            throw new \InvalidArgumentException('Cache prefix is empty!'); // @codeCoverageIgnore
        }

        if (empty($this->baseNamespaceForEntity)) {
            throw new \InvalidArgumentException('Entity namespace is empty!'); // @codeCoverageIgnore
        }

        if (empty($this->baseNamespaceForMapper)) {
            throw new \InvalidArgumentException('Mapper namespace is empty!'); // @codeCoverageIgnore
        }

        if (empty($this->basePathForEntity)) {
            throw new \InvalidArgumentException('Entity base path is empty!'); // @codeCoverageIgnore
        }

        if (empty($this->basePathForMapper)) {
            throw new \InvalidArgumentException('Mapper path is empty!'); // @codeCoverageIgnore
        }

        return $this;
    }
}
