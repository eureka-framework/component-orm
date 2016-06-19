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

        $configs = $this->findConfigs($data, $configName, true);

        $builder = new Builder();
        $builder->setDatabase(Database::get($dbName));
        $builder->setNamespace($namespace);
        $builder->setDirectory($directory);
        $builder->build($configs);
    }

    /**
     * Find configs.
     *
     * @param array  $data
     * @param string $configName Filter on name
     * @param bool   $doLoadJoins
     * @return array|Config
     */
    protected function findConfigs(array $data, $configName = '', $doLoadJoins = false)
    {
        $configs = array();

        foreach ($data['configs'] as $name => $config) {

            if (!empty($configName) && $name !== $configName) {
                continue;
            }

            if ($doLoadJoins) {
                $this->loadJoins($config, $data);
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
     * @param  array $config Current config
     * @param  array $data Global current config data
     * @return $this
     */
    protected function loadJoins(&$config, $data)
    {
        if (empty($config['joins']) || !is_array($config['joins'])) {
            $config['joins'] = array();

            return $this;
        }

        foreach ($config['joins'] as $joinName => &$joinConfig) {

            if (isset($joinConfig['file']) && 'this' !== $joinConfig['file']) {
                $filename = str_replace('EKA_ROOT', EKA_ROOT, $joinConfig['file']);
                $yaml     = new Yaml();
                $data     = $yaml->load($filename);
            }

            $joinConfig['class'] = $this->findConfigs($data, $joinName);
        }

        return $this;
    }
}
