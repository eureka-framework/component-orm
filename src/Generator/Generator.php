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
use Eureka\Component\Orm\Config\Config;
use Eureka\Component\Orm\Config\ConfigInterface;
use Eureka\Component\Orm\Exception\GeneratorException;
use Eureka\Component\Orm\Generator\Compiler\EntityCompiler;
use Eureka\Component\Orm\Generator\Compiler\MapperCompiler;
use Eureka\Component\Orm\Generator\Compiler\RepositoryCompiler;

/**
 * Class Generator
 *
 * @author Romain Cottard
 *
 * @phpstan-type ConfigList array<array{
 *   comment: array{author: string, copyright: string},
 *   class: array{classname: string},
 *   namespace: array{entity: string, mapper: string, repository?: string},
 *   path: array{entity: string, mapper: string, repository?: string},
 *   cache: array{prefix: string},
 *   database: array{table: string, prefix: string|string[]},
 *   validation: array{
 *       extended_validation?: array<array{type?: string, options?: array<string, string|int|float>}>|null,
 *       enabled?: bool,
 *       auto?: bool
 *   },
 *   joins?: array<string, array{
 *       eager_loading?: bool,
 *       config?: string,
 *       relation?: string,
 *       type?: string,
 *       keys?: array<string, string|bool>
 *   }>
 *  }>
 */
class Generator
{
    /**
     * @param Connection $connection
     * @param ConfigList $configList
     * @param string $configName
     * @param bool $isVerbose
     * @return void
     * @throws GeneratorException
     */
    public function generate(
        Connection $connection,
        array $configList,
        string $configName = '',
        bool $isVerbose = true
    ): void {
        $configs = $this->buildConfigs($configList, $configName);

        foreach ($configs as $config) {
            (new RepositoryCompiler($config))
                ->setConnection($connection)
                ->setVerbose($isVerbose)
                ->compile()
            ;

            (new EntityCompiler($config))
                ->setConnection($connection)
                ->setVerbose($isVerbose)
                ->initFields()
                ->compile()
            ;

            (new MapperCompiler($config))
                ->setConnection($connection)
                ->setVerbose($isVerbose)
                ->initFields()
                ->compile()
            ;
        }
    }

    /**
     * Find configs.
     *
     * @param ConfigList $configList
     * @param string $configName Filter on name
     * @return Config[]
     * @throws GeneratorException
     */
    protected function buildConfigs(array $configList, string $configName = ''): array
    {
        /** @var Config[] $configs */
        $configs    = [];
        $baseConfig = [];

        if (empty($configList)) {
            throw new \RuntimeException('Invalid config. Empty information about orm!');
        }

        foreach ($configList as $name => $configValues) {
            $this->generatePaths($configValues['path']);

            $configs[$name]    = new Config($configValues); //~ Final configs, can be updated
            $baseConfig[$name] = new Config($configValues); //~ Use for join, no update on those instances
        }

        foreach ($configs as $name => $config) {
            if (empty($configList[$name]['joins'])) {
                continue;
            }

            /** @var array<array{
             *     eager_loading?: bool,
             *     config?: string,
             *     relation: string,
             *     type: string,
             *     keys: array<bool|string>,
             *     instance?: ConfigInterface
             * }> $joins
             */
            $joins = $configList[$name]['joins'];

            foreach ($joins as $key => $join) {
                if (!isset($join['config'])) {
                    throw new GeneratorException('Invalid orm config file for "' . $name . '"');
                }

                if (!isset($baseConfig[$join['config']])) {
                    throw new GeneratorException(
                        'Invalid config. Joined config "' . $join['config'] . '" does not exist!'
                    );
                }

                $joins[$key]['instance'] = clone $baseConfig[$join['config']];
            }

            /** @var array<array{
             *     eager_loading?: bool,
             *     config: string,
             *     relation: string,
             *     type: string,
             *     keys: array<bool|string>,
             *     instance?: ConfigInterface
             * }> $joins
             */
            $config->setJoinList($joins);
        }

        if (!empty($configName)) {
            $configs = $this->filterConfigs($configs, $configName);
        }

        return $configs;
    }

    /**
     * @param Config[] $configs
     * @return Config[]
     */
    private function filterConfigs(array $configs, string $configName): array
    {
        $filteredConfigs = [];
        foreach ($configs as $name => $config) {
            if (preg_match("`^$configName$`", $name) > 0) {
                $filteredConfigs[$name] = $config;
            }
        }

        return $filteredConfigs;
    }

    /**
     * @param string[] $paths
     * @return void
     * @throws GeneratorException
     */
    private function generatePaths(array $paths): void
    {
        foreach ($paths as $path) {
            if (!is_dir($path) && !mkdir($path, 0755, true)) {
                // @codeCoverageIgnoreStart
                throw new GeneratorException('Cannot created output directory! (dir:' . $path . ')');
                // @codeCoverageIgnoreEnd
            }
        }
    }
}
