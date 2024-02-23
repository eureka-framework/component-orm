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
use Eureka\Component\Orm\Enumerator\JoinRelation;
use Eureka\Component\Orm\Enumerator\JoinType;
use Eureka\Component\Orm\Exception\GeneratorException;

/**
 * Class MapperCompiler
 *
 * @author Romain Cottard
 */
class MapperCompiler extends AbstractClassCompiler
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
            self::TYPE_MAPPER,
            [
                __DIR__ . '/../Templates/Mapper.template'         => false,
                __DIR__ . '/../Templates/AbstractMapper.template' => true,
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
        $context
            ->add('class.namespace', $this->config->getBaseNamespaceForMapper())
            ->add('repository.namespace', $this->config->getBaseNamespaceForRepository())
            ->add('entity.namespace', $this->config->getBaseNamespaceForEntity())
            ->add('database.tab:e', $this->config->getDbTable())
            ->add('database.fields', $this->buildFields())
            ->add('database.keys', $this->buildFields(true))
            ->add('database.map', $this->buildMap())
            ->add('database.joins', $this->buildJoins())
            ->add('mapper.uses', $this->buildUses())
            ->add('validator.config', $this->buildValidatorConfig())
        ;

        return $context;
    }

    /**
     * @param bool $onlyKey
     * @return string
     */
    private function buildFields(bool $onlyKey = false): string
    {
        $names = [];
        foreach ($this->fields as $field) {
            if ($onlyKey && !$field->isPrimaryKey()) {
                continue;
            }
            $names[] = "'" . $field->getName() . "'";
        }

        return implode(",\n            ", $names);
    }

    /**
     * @return string
     */
    private function buildMap(): string
    {
        $map = [];
        foreach ($this->fields as $field) {
            $map[$field->getName()] = "
            '" . $field->getName() . "' => [
                'get'      => '" . $this->getNameForGetter($field) . "',
                'set'      => '" . $this->getNameForSetter($field) . "',
                'property' => '" . $this->getPropertyName($field) . "',
            ],";
        }

        return implode('', $map);
    }

    /**
     * @return string
     * @throws GeneratorException
     */
    private function buildJoins(): string
    {
        $joins = [];

        foreach ($this->config->getAllJoin() as $name => $join) {
            if (!isset($join['eager_loading']) || (bool) $join['eager_loading'] !== true) {
                continue;
            }

            $config = $join['instance'];

            if (!($config instanceof Config\ConfigInterface)) {
                // @codeCoverageIgnoreStart
                throw new GeneratorException(
                    'Joined class is not an instance of ConfigInterface! (class: ' . get_class($config) . ')'
                );
                // @codeCoverageIgnoreEnd
            }

            $keys     = $join['keys'];
            $joins [] = "
            '$name' => [
                'mapper'   => " . $config->getClassname() . "Mapper::class,
                'type'     => '" . (!empty($join['type']) ? strtoupper($join['type']) : JoinType::INNER) . "',
                'relation' => '" . (!empty($join['relation']) ? $join['relation'] : JoinRelation::ONE) . "',
                'keys'     => [" . var_export(key($keys), true) . " => " . var_export(current($keys), true) . "],
            ],";
        }

        return implode('', $joins);
    }

    /**
     * @return string
     * @throws GeneratorException
     */
    private function buildUses(): string
    {
        $uses = [];

        foreach ($this->config->getAllJoin() as $join) {
            if (!isset($join['eager_loading']) || (bool) $join['eager_loading'] !== true) {
                continue;
            }

            $config = $join['instance'];

            if (!($config instanceof Config\ConfigInterface)) {
                // @codeCoverageIgnoreStart
                throw new GeneratorException(
                    'Joined class is not an instance of ConfigInterface! (class: ' . get_class($config) . ')'
                );
                // @codeCoverageIgnoreEnd
            }

            $className = $config->getBaseNamespaceForMapper() . '\\' . $config->getClassname() . 'Mapper';
            $uses[$className] = 'use ' . $className . ';';
        }

        return implode("\n", $uses);
    }
}
