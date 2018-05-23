<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eureka\Component\Orm\Script;

use Eureka\Component\Config\Config;
use Eureka\Component\Orm\Generator\Generator as GeneratorService;
use Eureka\Component\Orm\Config\Config as OrmConfig;
use Eureka\Eurekon;

/**
 * Console Abstraction class.
 * Must be parent class for every console script class.
 *
 * @author  Romain Cottard
 */
class Generator extends Eurekon\AbstractScript
{
    /**
     * Generator constructor.
     */
    public function __construct()
    {
        $this->setDescription('Orm generator');
        $this->setExecutable(true);
    }

    /**
     * {@inheritdoc}
     */
    public function help()
    {
        $help = new Eurekon\Help('...');
        $help->addArgument('', 'config-dir', 'Config directory to inspect for config file', true, true);
        $help->addArgument('', 'config-item', 'Config name in config file to generate.', true, false);
        $help->addArgument('', 'db-service', 'Database service name (default: database.connection.common)', true, false);
        $help->addArgument('', 'with-repository', 'Also generate repository interfaces', false, false);

        $help->display();
    }

    /**
     * {@inheritdoc}
     * @throws \Exception
     */
    public function run()
    {
        $argument      = Eurekon\Argument\Argument::getInstance();
        $directory     = (string) $argument->get('config-dir');
        $configName    = (string) $argument->get('config-item');
        $dbServiceName = (string) $argument->get('db-service', null, 'database.connection.common');

        $directory  = realpath(trim(rtrim($directory, '/')));
        $configName = trim($configName);

        /** @var Config $config */
        $config = clone $this->getConfig();
        $config->loadYamlFromDirectory($directory . '/orm', 'orm.', null, false);

        $configs  = $this->findConfigs($config, $configName);

        (new GeneratorService())->setConnection($this->getContainer()->get($dbServiceName))
            ->setHasRepository($argument->has('with-repository'))
            ->setRootDirectory('')
            ->build($configs)
        ;
    }

    /**
     * Find configs.
     *
     * @param Config $configApp
     * @param string $configName Filter on name
     * @return array|Config
     * @throws \Exception
     */
    protected function findConfigs(Config $configApp, $configName = '')
    {
        /** @var OrmConfig[] $configs */
        $configs    = [];
        $baseConfig = [];

        $data = $configApp->get('orm');

        if (!is_array($data)) {
            throw new \RuntimeException('Invalid config. Empty information about orm!');
        }

        foreach ($data as $name => $configValues) {

            foreach ($configValues['path'] as $path) {
                if (!is_dir($path) && !mkdir($path, 0755, true)) {
                    throw new \RuntimeException('Cannot created output directory! (dir:' . $path . ')');
                }
            }

            $configs[$name]    = new OrmConfig($configValues); //~ Final configs, can be updated
            $baseConfig[$name] = new OrmConfig($configValues); //~ Use for join, no update on those instances
        }

        foreach ($configs as $name => $config) {
            if (empty($data[$name]['joins'])) {
                continue;
            }

            $joins = $data[$name]['joins'];

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

}
