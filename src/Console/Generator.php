<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eureka\Component\Orm\Console;

use Eureka\Component\Container\Container;
use Eureka\Component\Database\Database;
use Eureka\Component\Orm\Builder;
use Eureka\Component\Orm\Config\Config;
use Eureka\Component\Config\Config as Conf;
use Eureka\Component\Yaml\Yaml;
use Eureka\Eurekon;

/**
 * Console Abstraction class.
 * Must be parent class for every console script class.
 *
 * @author  Romain Cottard
 */
class Generator extends Eurekon\Console
{
    /** @var boolean $executable Set to true to set class as an executable script */
    protected $executable = true;

    /** @var boolean $executable Console script description. */
    protected $description = 'Orm generator';

    /**
     * Help method.
     *
     * @return void
     */
    public function help()
    {
        $style = new Eurekon\Style(' *** RUN - HELP ***');
        Eurekon\Out::std($style->color('fg', Eurekon\Style::COLOR_GREEN)->get());
        Eurekon\Out::std('');

        $help = new Eurekon\Help('...', true);
        $help->addArgument('', 'db-namespace', 'Config namespace (default: global.database)', true, false);
        $help->addArgument('', 'db-name', 'Database config name', true, false);
        $help->addArgument('', 'config-dir', 'Config directory to inspect for config file', true, true);
        $help->addArgument('', 'config-item', 'Config name in config file to generate.', true, false);

        $help->display();
    }

    /**
     * Run method.
     *
     * @return void
     */
    public function run()
    {
        $argument    = Eurekon\Argument::getInstance();
        $directory   = (string) $argument->get('config-dir');
        $configName  = (string) $argument->get('config-item');
        $dbName      = (string) $argument->get('db-name');
        $dbNamespace = (string) $argument->get('db-namespace', null, 'global.database');

        $directory  = realpath(trim(rtrim($directory, '/')));
        $configName = trim($configName);
        //$filter     = !empty($configName) ? $configName . '.yml' : '*.yml';

        $files = glob($directory . DIRECTORY_SEPARATOR . '*.yml');

        if (empty($files)) {
            throw new \RuntimeException('Invalid config-dir or config-item!');
        }

        foreach ($files as $file) {

            if (!is_readable($file)) {
                throw new \RuntimeException('Cannot read orm config file! (file: ' . $file . ')');
            }

            $yaml = new Yaml();
            $data = $yaml->load($file);

            $data = $this->replace($file, $data);

            foreach ($data['path'] as $path) {
                if (!is_dir($path) && !mkdir($path, 0755, true)) {
                    throw new \RuntimeException('Cannot created output directory! (dir:' . $path . ')');
                }
            }

            $configs['configs'][basename($file, '.yml')] = $data;
        }
        $configs = $this->findConfigs($configs, $configName);

        $database = Database::getInstance();
        $database->setConfig(Container::getInstance()->get('config')->get($dbNamespace));

        (new Builder())->setDatabase($database->getConnection($dbName))
            ->setRootDirectory('/home/romain/moon/dev/allinwedding')
            ->build($configs);
    }

    /**
     * Find configs.
     *
     * @param array $data
     * @param string $configName Filter on name
     * @return array|Config
     */
    protected function findConfigs(array $data, $configName = '')
    {
        /** @var Config[] $configs */
        $configs = [];

        foreach ($data['configs'] as $name => $configValues) {
            $configs[$name]    = new Config($configValues); //~ Final configs, can be updated
            $baseConfig[$name] = new Config($configValues); //~ Use for join, no update on those instances
        }

        foreach ($configs as $name => $config) {
            if (empty($data['configs'][$name]['joins'])) {
                continue;
            }

            $joins = $data['configs'][$name]['joins'];

            foreach ($joins as $key => $join) {
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
     * Add config value(s).
     *
     * @param  string $currentFile
     * @param  mixed $config Configuration value.
     * @return mixed
     */
    private function replace($currentFile, $config)
    {
        $patterns = array(
            'constants' => array(
                '`EKA_[A-Z_]+`',
            ),
            'php'       => array(
                '__DIR__',
            ),
        );

        if (!is_array($config)) {

            foreach ($patterns['constants'] as $pattern) {
                if ((bool) preg_match_all($pattern, $config, $matches)) {

                    $matches   = array_unique($matches[0]);
                    $constants = array();//'.' => '');

                    foreach ($matches as $index => $constant) {
                        $constants[$constant] = constant($constant);
                    }

                    $config = str_replace(array_keys($constants), array_values($constants), $config);

                    if (is_numeric($config)) {
                        $config = (int) $config;
                    }
                }
            }

            $currentDir = dirname($currentFile);
            foreach ($patterns['php'] as $pattern) {

                if (strpos($config, $pattern) !== false) {

                    switch ($pattern) {
                        case '__DIR__':
                            $replace = $currentDir;
                            break;
                        default:
                            continue 2;
                    }

                    $config = str_replace($pattern, $replace, $config);
                }
            }
            /*if (false !== strpos($config, '..')) {
                $config = realpath($config);
            }*/
        } elseif (is_array($config)) {

            foreach ($config as $key => $conf) {
                if (empty($conf)) {
                    continue;
                }
                $config[$key] = $this->replace($currentFile, $conf);
            }
        }

        return $config;
    }
}
