<?php

/**
 * Copyright (c) 2010-2016 Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eureka\Component\Orm\Console;

use Eureka\Component\Database\Database;
use Eureka\Component\Debug\Debug;
use Eureka\Component\Orm\Builder;
use Eureka\Component\Orm\Config\Config;
use Eureka\Component\Yaml\Yaml;
use Eureka\Eurekon;
use Eureka\Eurekon\Style;
use Eureka\Eurekon\Out;

/**
 * Console Abstraction class.
 * Must be parent class for every console script class.
 *
 * @author  Romain Cottard
 * @version 2.0.0
 */
class Generator extends Eurekon\Console
{
    /**
     * Set to true to set class as an executable script
     *
     * @var boolean $executable
     */
    protected $executable = true;

    /**
     * Console script description.
     *
     * @var boolean $executable
     */
    protected $description = 'Orm generator';

    /**
     * Help method.
     * Must be overridden.
     *
     * @return void
     */
    public function help()
    {
        $style = new Style(' *** RUN - HELP ***');
        Out::std($style->color('fg', Style::COLOR_GREEN)->get());
        Out::std('');

        $help = new Eurekon\Help('...', true);
        $help->addArgument('', 'directory', 'Config directory to inspect for config file', true, true);
        $help->addArgument('', 'config', 'Config name in config file to generate.', true, false);
        $help->addArgument('', 'db', 'Database config name', true, false);

        $help->display();
    }

    /**
     * Run method.
     * Must be overridden.
     *
     * @return void
     */
    public function run()
    {
        $argument   = Eurekon\Argument::getInstance();
        $directory  = (string) $argument->get('directory');
        $configName = (string) $argument->get('config');
        $dbName     = (string) $argument->get('db');

        $directory  = realpath(trim(rtrim($directory, '\\')));
        $file = $directory . DIRECTORY_SEPARATOR . 'orm.yml';

        if (!is_readable($file)) {
            throw new \LogicException('Configuration file for ORM not available!');
        }

        $yaml = new Yaml();
        $data = $yaml->load($file);

        $directory = $directory . DIRECTORY_SEPARATOR . $data['orm']['directory'];
        $namespace = $data['orm']['namespace'];

        if (!is_dir($directory) && !mkdir($directory, 0764, true)) {
            throw new \RuntimeException('Cannot created output directory! (dir:' . $directory . ')');
        }

        $configs = $this->findConfigs($file, $data, $configName, true);

        $builder = new Builder();
        $builder->setDatabase(Database::get($dbName));
        $builder->setNamespace($namespace);
        $builder->setDirectory($directory);
        $builder->build($configs);
    }

    /**
     * Find configs.
     *
     * @param string $currentFile
     * @param array  $data
     * @param string $configName Filter on name
     * @param bool   $doLoadJoins
     * @return array|Config
     */
    protected function findConfigs($currentFile, array $data, $configName = '', $doLoadJoins = false)
    {
        $configs = array();

        $data = $this->replace($currentFile, $data);

        foreach ($data['configs'] as $name => $config) {

            if (!empty($configName) && $name !== $configName) {
                continue;
            }

            if ($doLoadJoins) {
                $this->loadJoins($currentFile, $config, $data);
            } else {
                $config['joins'] = array();
            }

            $configs[] = new Config($config, $data['orm']);
        }

        if (!$doLoadJoins) {
            $configs = array_shift($configs);
        }

        return $configs;
    }

    /**
     * Load joined configs.
     *
     * @param  string $currentFile
     * @param  array $config Current config
     * @param  array $data Global current config data
     * @return $this
     */
    protected function loadJoins($currentFile, &$config, $data)
    {
        if (empty($config['joins']) || !is_array($config['joins'])) {
            $config['joins'] = array();

            return $this;
        }

        foreach ($config['joins'] as $joinName => &$joinConfig) {

            $filename = $currentFile;
            if (isset($joinConfig['file']) && 'this' !== $joinConfig['file']) {
                //echo '$file: ' . $joinConfig['file'] . PHP_EOL;
                $filename = str_replace('EKA_ROOT', EKA_ROOT, $joinConfig['file']);
                $yaml     = new Yaml();
                $data     = $yaml->load($filename);
            }

            $joinConfig['class'] = $this->findConfigs($filename, $data, $joinName);
        }

        return $this;
    }

    /**
     * Add config value(s).
     *
     * @param    string $currentFile
     * @param    mixed $config Configuration value.
     * @return   mixed
     */
    public function replace($currentFile, $config)
    {
        $patterns = array(
            'constants' => array(
                '`EKA_[A-Z_]+`',
            ), 'php'    => array(
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

                switch ($pattern) {
                    case '__DIR__':
                        $replace = $currentDir;
                        break;
                    default:
                        continue 2;
                }

                $config = str_replace($pattern, $replace, $config);
            }

            if (false !== strpos($config, '..')) {
                $config = realpath($config);
            }

        } elseif (is_array($config)) {

            foreach ($config as $key => $conf) {
                $config[$key] = $this->replace($currentFile, $conf);
            }
        }

        return $config;
    }
}
