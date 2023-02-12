<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Eureka\Component\Orm\Script;

use Eureka\Component\Database\ConnectionFactory;
use Eureka\Component\Orm\Exception\GeneratorException;
use Eureka\Component\Orm\Generator\Generator as GeneratorService;
use Eureka\Component\Console;

/**
 * Class Generator
 *
 * @author Romain Cottard
 * @codeCoverageIgnore
 */
class Generator extends Console\AbstractScript
{
    /**
     * @param ConnectionFactory $factory
     * @param array<mixed> $ormConfigs
     */
    public function __construct(
        private readonly ConnectionFactory $factory,
        private readonly array $ormConfigs
    ) {
        $this->setDescription('Orm generator');
        $this->setExecutable();
    }

    public function help(): void
    {
        (new Console\Help('...'))
            ->addArgument(
                '',
                'config-name',
                'Generate config only for given config name',
                true
            )
            ->addArgument(
                '',
                'connection-name',
                'Name of the connection to use for generation (default: common)',
                true
            )
            ->addArgument(
                '',
                'without-repository',
                'Do not generate repository interfaces'
            )
            ->display()
        ;
    }

    /**
     * @throws GeneratorException
     */
    public function run(): void
    {
        $argument       = Console\Argument\Argument::getInstance();
        $configName     = trim((string) $argument->get('config-name'));
        $connectionName = (string) $argument->get('connection-name', null, 'common');

        $connection = $this->factory->getConnection($connectionName);
        $configList = $this->ormConfigs;

        $generator = new GeneratorService();
        $generator->generate($connection, $configList, $configName);
    }
}
