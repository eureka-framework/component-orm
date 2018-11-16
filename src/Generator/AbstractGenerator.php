<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Eureka\Component\Orm\Generator;

use Eureka\Component\Database\Connection;
use Eureka\Component\Orm\Config;
use Eureka\Component\Orm\Exception\GeneratorException;

/**
 * Abstract generator class
 *
 * @author Romain Cottard
 */
class AbstractGenerator
{
    /** @var Config\ConfigInterface $config ORM configuration object. */
    protected $config = null;

    /** @var array $vars List of variables to replace in template */
    protected $vars = [];

    /** @var Column[] $columns List of columns to treat for current table. */
    protected $columns = [];

    /** @var bool $verbose Verbose active or not. */
    protected $verbose = true;

    /** @var bool $hasRepository Generate repository interface or not. */
    protected $hasRepository = false;

    /** @var string $rootDir */
    protected $rootDir = __DIR__ . '/../../..';

    /** @var Connection $connection */
    protected $connection = null;

    /** @var string $validatorsConfig */
    protected $validatorsConfig = '';

    /**
     * AbstractGenerator constructor.
     *
     * @param \Eureka\Component\Orm\Config\ConfigInterface $config
     */
    public function __construct(Config\ConfigInterface $config)
    {
        $this->config = $config;
    }

    /**
     * Set verbose mode
     *
     * @param  bool $verbose
     * @return $this
     */
    public function setVerbose(bool $verbose): AbstractGenerator
    {
        $this->verbose = (bool) $verbose;

        return $this;
    }

    /**
     * Set if has repository mode
     *
     * @param  bool $hasRepository
     * @return $this
     */
    public function setHasRepository(bool $hasRepository): AbstractGenerator
    {
        $this->hasRepository = (bool) $hasRepository;

        return $this;
    }

    /**
     * Set root directory
     *
     * @param  string $rootDir
     * @return $this
     */
    public function setRootDirectory(string $rootDir): AbstractGenerator
    {
        $this->rootDir = (string) $rootDir;

        return $this;
    }

    /**
     * Set database connection.
     *
     * @param  Connection $connection
     * @return $this
     */
    public function setConnection(Connection $connection): AbstractGenerator
    {
        $this->connection = $connection;

        return $this;
    }

    /**
     * @param  string $template
     * @param  $context
     * @param  bool $skipExisting
     * @return void
     * @throws \Eureka\Component\Orm\Exception\GeneratorException
     */
    protected function renderTemplate(string $template, $context, bool $skipExisting = fale): void
    {
        $content = $this->readTemplate($template);
        $content = str_replace($context->getKeys(), $context->getValues(), $content);
        $this->writeTemplate($file, $content, $skipExisting);
    }

    /**
     * @param  string $template
     * @return string
     * @throws \Eureka\Component\Orm\Exception\GeneratorException
     */
    protected function readTemplate(string $template): string
    {
        if (!is_readable($template)) {
            throw new GeneratorException('Template file does not exists or not readable (file: ' . $template . ')');
        }

        $content = file_get_contents($template);

        if ($content === false) {
            throw new GeneratorException('Template file does not exists or not readable (file: ' . $template . ')');
        }

        return $content;
    }

    /**
     * @param  string $file
     * @param  string $content
     * @param  bool $skipExisting
     * @return void
     * @throws \Eureka\Component\Orm\Exception\GeneratorException
     */
    protected function writeTemplate(string $file, string $content, $skipExisting = false): void
    {
        if ($skipExisting && file_exists($file)) {
            return;
        }

        if (file_put_contents($file, $content) === false) {
            throw new GeneratorException('Unable to write final file! (file: ' . $file . ')');
        }
    }
}
