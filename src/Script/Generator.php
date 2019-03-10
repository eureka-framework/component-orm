<?php declare(strict_types=1);

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
use Eureka\Eurekon;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class Generator
 *
 * @author Romain Cottard
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
     * Display help.
     *
     * @return void
     */
    public function help(): void
    {
        $help = new Eurekon\Help('...');
        $help->addArgument('', 'config-dir', 'Config directory to inspect for config file', true, true);
        $help->addArgument('', 'config-item', 'Config name in config file to generate.', true, false);
        $help->addArgument('', 'db-service', 'Database service name (default: database.connection.common)', true, false);
        $help->addArgument('', 'without-repository', 'Do not generate repository interfaces', false, false);

        $help->display();
    }

    /**
     * @return void
     * @throws GeneratorException
     */
    public function run(): void
    {
        $argument      = Eurekon\Argument\Argument::getInstance();
        $configName    = (string) trim((string) $argument->get('config-item'));
        $dbServiceName = (string) $argument->get('db-service', null, 'database.connection.common');

        /** @var ContainerInterface $container */
        $container = $this->getContainer();

        /** @var Connection $connection */
        $connection = $container->get($dbServiceName);
        $configList = $container->getParameter('orm.configs');

        $generator = new GeneratorService();
        $generator->generate($connection, $configList, $configName);
    }
}
