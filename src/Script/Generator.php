<?php

declare(strict_types=1);

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eureka\Component\Orm\Script;

use Eureka\Component\Database\Connection;
use Eureka\Component\Orm\Exception\GeneratorException;
use Eureka\Component\Orm\Generator\Generator as GeneratorService;
use Eureka\Component\Console;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class Generator
 *
 * @author Romain Cottard
 * @codeCoverageIgnore
 */
class Generator extends Console\AbstractScript
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
     * Display help.
     *
     * @return void
     */
    public function help(): void
    {
        $help = new Console\Help('...');
        $help->addArgument('', 'config-name', 'Generate config only for given config name', true, false);
        $help->addArgument('', 'connection-name', 'Name of the connection to use for generation (default: common)', true, false);
        $help->addArgument('', 'without-repository', 'Do not generate repository interfaces', false, false);

        $help->display();
    }

    /**
     * @return void
     * @throws GeneratorException
     */
    public function run(): void
    {
        $argument       = Console\Argument\Argument::getInstance();
        $configName     = (string) trim((string) $argument->get('config-name'));
        $connectionName = (string) $argument->get('connection-name', null, 'common');

        /** @var ContainerInterface $container */
        $container = $this->getContainer();

        /** @var Connection $connection */
        $connection = $container->get('database.factory')->getConnection($connectionName);
        $configList = $container->getParameter('orm.configs');

        $generator = new GeneratorService();
        $generator->generate($connection, $configList, $configName);
    }
}
