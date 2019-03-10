<?php declare(strict_types=1);

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eureka\Component\Orm\Generator;

use Eureka\Component\Database\Connection;
use Eureka\Component\Orm\Config\Config;
use Eureka\Component\Orm\Exception\GeneratorException;
use Eureka\Component\Orm\Generator\Compiler\EntityCompiler;
use Eureka\Component\Orm\Generator\Compiler\MapperCompiler;
use Eureka\Component\Orm\Generator\Compiler\RepositoryCompiler;

/**
 * Class Generator
 *
 * @author Romain Cottard
 */
class Generator
{
    /**
     * @param Connection $connection
     * @param array $configList
     * @param string $configName
     * @return void
     * @throws GeneratorException
     */
    public function generate(Connection $connection, array $configList, string $configName = ''): void
    {
        $configs = $this->buildConfigs($configList, $configName);

        foreach ($configs as $config) {
            (new RepositoryCompiler($config))
                ->setConnection($connection)
                ->setVerbose(true)
                ->compile()
            ;

            (new EntityCompiler($config))
                ->setConnection($connection)
                ->setVerbose(true)
                ->initFields()
                ->compile()
            ;

            (new MapperCompiler($config))
                ->setConnection($connection)
                ->setVerbose(true)
                ->initFields()
                ->compile()
            ;
        }
    }
    /**
     * Find configs.
     *
     * @param array $configList
     * @param string $configName Filter on name
     * @return Config[]
     */
    protected function buildConfigs(array $configList, string $configName = ''): array
    {
        /** @var Config[] $configs */
        $configs    = [];
        $baseConfig = [];

        if (!is_array($configList)) {
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
            $joins = $configList[$name]['joins'];

            foreach ($joins as $key => $join) {
                if (!isset($join['config'])) {
                    throw new \RuntimeException('Invalid orm config file for "' . $name . '"');
                }

                if (!isset($baseConfig[$join['config']])) {
                    unset($joins[$key]);
                    continue;
                }

                $joins[$key]['instance'] = clone $baseConfig[$join['config']];
            }

            $config->setJoinList($joins);
        }

        if (!empty($configName) && !empty($configs[$configName])) {
            $configs = [$configName => $configs[$configName]];
        }

        return $configs;
    }

    /**
     * @param string[] $paths
     * @return void
     */
    private function generatePaths(array $paths): void
    {
        foreach ($paths as $path) {
            if (!is_dir($path) && !mkdir($path, 0755, true)) {
                throw new \RuntimeException('Cannot created output directory! (dir:' . $path . ')');
            }
        }
    }
}
