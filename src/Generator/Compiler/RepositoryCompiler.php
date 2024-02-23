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

/**
 * Repository Compiler
 *
 * @author Romain Cottard
 */
class RepositoryCompiler extends AbstractClassCompiler
{
    /**
     * RepositoryCompiler constructor.
     *
     * @param Config\ConfigInterface $config
     */
    public function __construct(Config\ConfigInterface $config)
    {
        parent::__construct(
            $config,
            self::TYPE_REPOSITORY,
            [
                __DIR__ . '/../Templates/RepositoryInterface.template' => false,
            ]
        );
    }

    /**
     * @param Context $context
     * @param bool $isAbstract
     * @return Context
     */
    protected function updateContext(Context $context, bool $isAbstract = false): Context
    {
        $context->add('class.namespace', $this->config->getBaseNamespaceForRepository());
        $context->add('entity.namespace', $this->config->getBaseNamespaceForEntity());

        return $context;
    }
}
