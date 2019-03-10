<?php declare(strict_types=1);

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eureka\Component\Orm\Generator\Compiler;

use Eureka\Component\Orm\Config;
use Eureka\Component\Orm\Generator\Compiler\Field\FieldGetterCompiler;
use Eureka\Component\Orm\Generator\Compiler\Field\FieldPropertyCompiler;
use Eureka\Component\Orm\Generator\Compiler\Field\FieldSetterCompiler;
use Eureka\Component\Orm\Exception\GeneratorException;

/**
 * Class EntityCompiler
 *
 * @author Romain Cottard
 */
class EntityCompiler extends AbstractClassCompiler
{
    /**
     * EntityCompiler constructor.
     *
     * @param Config\ConfigInterface $config
     */
    public function __construct(Config\ConfigInterface $config)
    {
        parent::__construct(
            $config,
            self::TYPE_ENTITY,
            [
                __DIR__ . '/../Templates/Entity.template'         => false,
                __DIR__ . '/../Templates/AbstractEntity.template' => true,
            ]
        );
    }

    /**
     * @param Context $context
     * @param bool $isAbstract
     * @return Context
     * @throws GeneratorException
     */
    protected function updateContext(Context $context, bool $isAbstract = false): Context
    {
        $context->add('class.namespace', $this->config->getBaseNamespaceForEntity() . ($isAbstract ? '\Abstracts' : ''));
        $context->add('cache.key.prefix', $this->config->getCachePrefix());

        $context->add('entity.uses', '');

        $compiledTemplate = [
            'properties' => [],
            'getters'    => [],
            'setters'    => [],
            'joins'      => [],
        ];

        //~ Compile templates about fields
        foreach ($this->fields as $field) {
            $compiledTemplate['properties'] = array_merge($compiledTemplate['properties'], (new FieldPropertyCompiler($field))->compile());
            $compiledTemplate['getters']    = array_merge($compiledTemplate['getters'], (new FieldGetterCompiler($field))->compile());
            $compiledTemplate['setters']    = array_merge($compiledTemplate['setters'], (new FieldSetterCompiler($field))->compile());
        }

        $context->add('class.properties', implode("\n", $compiledTemplate['properties']));
        $context->add('method.getters', implode("\n", $compiledTemplate['getters']));
        $context->add('method.setters', implode("\n", $compiledTemplate['setters']));

        //~ Compile templates about joins
        foreach ($this->config->getAllJoin() as $joinConfig) {
            $compiler = new JoinCompiler($this->config, $joinConfig, $this->fields, $context);
            $compiler->updateGlobalContext();
            $compiledTemplate['joins'] = array_merge($compiledTemplate['joins'], $compiler->compile());
        }

        $context->add('method.joins', implode("\n", $compiledTemplate['joins']));

        return $context;
    }
}
