<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Eureka\Component\Orm\Generator\Compiler;

use Eureka\Component\Orm\Config;
use Eureka\Component\Orm\Exception\GeneratorException;

/**
 * Class AbstractClassCompiler
 *
 * @author Romain Cottard
 */
class AbstractMethodCompiler extends AbstractCompiler
{
    /** @var Config\ConfigInterface $config */
    protected Config\ConfigInterface $config;

    /**
     * AbstractCompiler constructor.
     *
     * @param Config\ConfigInterface $config
     * @param array<string, bool> $templates
     */
    public function __construct(Config\ConfigInterface $config, array $templates)
    {
        parent::__construct($templates);

        $this->config = $config;
    }


    /**
     * Compile fields element.
     *
     * @return string[]
     * @throws GeneratorException
     */
    public function compile(): array
    {
        $rendered = [];
        foreach ($this->templates as $template => $isAbstract) {
            $name = basename($template, '.template');

            //~ Get context
            $context = $this->updateContext($this->getContext(), $isAbstract);

            //~ Render template
            $rendered[$name] = $this->renderTemplate($template, $context);
        }

        return array_values($rendered);
    }

    /**
     * @return Context
     */
    protected function getContext(): Context
    {
        $ormNamespace = explode('\\', __NAMESPACE__);
        $ormNamespace = array_slice($ormNamespace, 0, -2);
        $ormNamespace = implode('\\', $ormNamespace);

        $context = new Context();
        $context->add('orm.namespace', $ormNamespace);

        return $context;
    }
}
