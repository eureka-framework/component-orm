<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eureka\Component\Orm\Console;

use Eureka\Component\Orm\Generator\Generator as GeneratorService;
use Eureka\Component\Orm\Config\Config;
use Eureka\Component\Yaml\Yaml;
use Eureka\Eurekon;

/**
 * Orm generator class.
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
        $this->setDescription('Orm generator script');
        $this->setExecutable();
    }

    /**
     * {@inheritdoc}
     */
    public function help()
    {
        $style = new Eurekon\Style\Style(' *** RUN - HELP ***');
        Eurekon\IO\Out::std($style->color('fg', Eurekon\Style\Color::GREEN)->get());
        Eurekon\IO\Out::std('');

        $help = new Eurekon\Help('...');
        $help->addArgument('', 'service-name', 'Database connection service end name', true, false);
        $help->addArgument('', 'service-prefix', 'Service prefix name (default: "database.connection.")', true, false);
        $help->addArgument('', 'config-dir', 'Config directory to inspect for config file', true, true);
        $help->addArgument('', 'config-item', 'Config name in config file to generate.', true, false);

        $help->display();
    }

    /**
     * {@inheritdoc}
     */
    public function run()
    {
        $argument      = Eurekon\Argument\Argument::getInstance();
        $directory     = (string) $argument->get('config-dir');
        $configName    = (string) $argument->get('config-item');
        $serviceName   = (string) $argument->get('service-name');
        $servicePrefix = (string) $argument->get('service-prefix', null, 'database.connection.');

        $directory  = realpath(trim(rtrim($directory, '/')));
        $configName = trim($configName);

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

            $configs['configs'][basename($file, '.yml')] = $data;
        }

        $this->replaceReferences($configs);

        $configs = $this->findConfigs($configs, $configName);

        $connection = $this->getContainer()->get($servicePrefix . $serviceName);

        (new GeneratorService())->setConnection($connection)
            ->setRootDirectory($this->getConfig()->get('kernel.root'))
            ->build($configs);
    }

    /**
     * Find configs.
     *
     * @param array $data
     * @param string $configName Filter on name
     * @return array|Config
     * @throws \Exception
     */
    protected function findConfigs(array $data, $configName = '')
    {
        /** @var Config[] $configs */
        $configs    = [];
        $baseConfig = [];

        foreach ($data['configs'] as $name => $configValues) {

            foreach ($configValues['path'] as $path) {
                if (!is_dir($path) && !mkdir($path, 0755, true)) {
                    throw new \RuntimeException('Cannot created output directory! (dir:' . $path . ')');
                }
            }

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


    /**
     * Replace references values in all configurations.
     *
     * @param  array $config
     * @return void
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     */
    private function replaceReferences(array &$config)
    {
        foreach ($config as $key => &$value) {
            if (is_array($value)) {
                $this->replaceReferences($value);
                continue;
            }

            //~ Not string, skip
            if (!is_string($value)) {
                continue;
            }

            //~ Value not %my.reference.config%, skip
            if (!(bool) preg_match('`%(.*?)%`', $value, $matches)) {
                continue;
            }

            $referenceValue = $this->getConfig()->get($matches[1]);

            if ($referenceValue !== null) {
                $value = preg_replace('`(%.*?%)`', $referenceValue, $value);
            }
        }
    }
}
