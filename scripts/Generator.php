<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Eureka\Component\Orm\Script;

use Eureka\Component\Console\AbstractScript;
use Eureka\Component\Console\Color\Bit8Color;
use Eureka\Component\Console\Color\Bit8StandardColor;
use Eureka\Component\Console\Help;
use Eureka\Component\Console\Option\Option;
use Eureka\Component\Console\Option\Options;
use Eureka\Component\Console\Style\Style;
use Eureka\Component\Database\ConnectionFactory;
use Eureka\Component\Database\Exception\UnknownConfigurationException;
use Eureka\Component\Orm\Exception\GeneratorException;
use Eureka\Component\Orm\Generator\Generator as GeneratorService;

/**
 * Class Generator
 *
 * @author Romain Cottard
 * @codeCoverageIgnore
 */
class Generator extends AbstractScript
{
    /**
     * @param ConnectionFactory $factory
     * @param array<mixed> $ormConfigs
     */
    public function __construct(
        private readonly ConnectionFactory $factory = new ConnectionFactory([]),
        private readonly array $ormConfigs = []
    ) {
        $this->setDescription('Orm generator');
        $this->setExecutable();

        $this->initOptions(
            (new Options())
                ->add(
                    new Option(
                        shortName: '',
                        longName: 'config-name',
                        description: 'Generate config only for given config name',
                        hasArgument: true
                    )
                )
                ->add(
                    new Option(
                        shortName: '',
                        longName: 'connection-name',
                        description: 'Name of the connection to use for generation (default: common)',
                        hasArgument: true,
                        default: 'common'
                    )
                )
                ->add(
                    new Option(
                        shortName: '',
                        longName: 'without-repository',
                        description: 'Do not generate repository interfaces',
                        hasArgument: false
                    )
                )
        );
    }

    public function help(): void
    {
        (new Help('...', $this->declaredOptions(), $this->output(), $this->options()))->display();
    }

    /**
     * @throws GeneratorException
     */
    public function run(): void
    {
        $configName     = trim((string) $this->options()->value('config-name'));
        $connectionName = (string) $this->options()->value('connection-name');

        try {
            $connection = $this->factory->getConnection($connectionName);
            $configList = $this->ormConfigs;

            $generator = new GeneratorService();
            $generator->generate($connection, $configList, $configName);
        } catch (UnknownConfigurationException $exception) {
            $style   = (new Style($this->options()))->color(Bit8StandardColor::Red)->bold();
            $message = 'Error with configuration: ' . $exception->getMessage();
            $this->outputErr()->writeln($style->apply($message));
        }
    }
}
