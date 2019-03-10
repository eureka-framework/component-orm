<?php declare(strict_types=1);

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eureka\Component\Orm\Generator\Compiler;

use Eureka\Component\Orm\Config\ConfigInterface;
use Eureka\Component\Orm\Exception\GeneratorException;
use Eureka\Component\Orm\Generator\Compiler\Field\Field;

/**
 * Class JoinCompiler
 *
 * @author Romain Cottard
 */
class JoinCompiler extends AbstractMethodCompiler
{
    /** @var array $joinConfig */
    private $joinConfig;

    /** @var Field[] $fields */
    private $fields;

    /** @var Context $mainContext */
    private $mainContext;

    /**
     * JoinCompiler constructor.
     *
     * @param ConfigInterface $config
     * @param array $joinConfig
     * @param Field[] $fields
     * @param Context $mainContext
     */
    public function __construct(ConfigInterface $config, array $joinConfig, array $fields, Context $mainContext)
    {
        parent::__construct(
            $config,
            [
                __DIR__ . '/../Templates/MethodJoinOne.template'  => false,
                __DIR__ . '/../Templates/MethodJoinMany.template' => false,
            ]
        );

        $this->joinConfig  = $joinConfig;
        $this->fields      = $fields;
        $this->mainContext = $mainContext;
    }

    /**
     * @param Context $context
     * @param bool $isAbstract
     * @return Context
     */
    public function updateContext(Context $context, bool $isAbstract = false): Context
    {
        /** @var ConfigInterface $config */
        $config = $this->joinConfig['instance'];

        $context
            ->add('join.entity.name', $config->getClassname())
            ->add('join.entity.keys', $this->buildKeys())
        ;

        return $context;
    }

    /**
     * @return $this
     * @throws GeneratorException
     */
    public function updateGlobalContext(): self
    {
        /** @var ConfigInterface $config */
        $config    = $this->joinConfig['instance'];
        $className = $config->getClassname();

        //~ Update class properties
        $classProperties = $this->mainContext->get('class.properties');

        if (!empty($classProperties)) {
            $compiler = new PropertyCompiler(
                'joinManyCache' . $className,
                $className,
                $className,
                'null'
            );
            $classProperties = $classProperties . "\n" . implode("\n", $compiler->compile());

            $compiler = new PropertyCompiler(
                'joinOneCache' . $className,
                $className,
                $className,
                'null'
            );
            $this->mainContext->add('class.properties', $classProperties . "\n" . implode("\n", $compiler->compile()));
        }

        //~ Update class "uses"
        $classUses = [];

        if (!empty($this->mainContext->get('entity.uses'))) {
            $classUses[] = $this->mainContext->get('entity.uses');
        }
        $classUses[] = 'use ' . $config->getBaseNamespaceForEntity() . '\\' . $className . ';';
        $classUses[] = 'use ' . $config->getBaseNamespaceForMapper() . '\\' . $className . 'Mapper;';

        $this->mainContext->add('entity.uses', implode("\n", $classUses));

        return $this;
    }

    /**
     * @return string
     */
    private function buildKeys(): string
    {
        $joinKeys = $this->joinConfig['keys'];
        $keys     = [];

        foreach ($this->fields as $field) {
            if (!isset($joinKeys[$field->getName()])) {
                continue;
            }

            $mappedBy = $field->getName();
            if (true !== $joinKeys[$field->getName()]) {
                $mappedBy = $joinKeys[$field->getName()];
            }

            $keys[] = "'${mappedBy}' => \$this->" . $this->getNameForGetter($field) . '(),';
        }

        return implode("            \n", $keys);
    }
}
