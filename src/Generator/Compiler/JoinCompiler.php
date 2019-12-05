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

    /** @var string $name */
    private $name;

    /**
     * JoinCompiler constructor.
     *
     * @param ConfigInterface $config
     * @param array $joinConfig
     * @param Field[] $fields
     * @param Context $mainContext
     * @param string $name
     */
    public function __construct(ConfigInterface $config, array $joinConfig, array $fields, Context $mainContext, string $name = '')
    {
        if ($joinConfig['relation'] === 'many') {
            $templates = [
                __DIR__ . '/../Templates/MethodJoinMany.template' => false,
            ];
        } else {
            $templates = [
                __DIR__ . '/../Templates/MethodJoinOne.template'  => false,
            ];
        }

        parent::__construct(
            $config,
            $templates
        );

        $this->joinConfig  = $joinConfig;
        $this->fields      = $fields;
        $this->mainContext = $mainContext;
        $this->name        = $name;
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

        $className = $config->getClassname();
        $name      = !empty($this->name) ? $this->name : $className;

        $context
            ->add('join.entity.class', $className)
            ->add('join.entity.name', $name)
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
        $name      = !empty($this->name) ? $this->name : $className;

        //~ Update class properties
        $classProperties = $this->mainContext->get('class.properties');

        if (!empty($classProperties)) {
            if ($this->joinConfig['relation'] === 'many') {
                $compiler        = new PropertyCompiler(
                    'joinManyCache' . $name, $className, $className, 'null'
                );

                $this->mainContext->add(
                    'class.properties',
                    $classProperties . "\n" . implode("\n", $compiler->compile())
                );

            } else {

                $compiler = new PropertyCompiler(
                    'joinOneCache' . $name, $className, $className, 'null'
                );

                $this->mainContext->add(
                    'class.properties',
                    $classProperties . "\n" . implode("\n", $compiler->compile())
                );
            }
        }

        //~ Update class "uses"
        $classUses = $this->mainContext->get('entity.uses');

        $useEntityClassName = $config->getBaseNamespaceForEntity() . '\\' . $className;
        $useMapperClassName = $config->getBaseNamespaceForMapper() . '\\' . $className . 'Mapper';
        $classUses[$useEntityClassName] = 'use ' . $useEntityClassName . ';';
        $classUses[$useMapperClassName] = 'use ' . $useMapperClassName . ';';

        $this->mainContext->add('entity.uses', $classUses);

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
