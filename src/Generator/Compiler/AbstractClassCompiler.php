<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Eureka\Component\Orm\Generator\Compiler;

use Eureka\Component\Database\Connection;
use Eureka\Component\Orm\Config;
use Eureka\Component\Orm\Exception\GeneratorException;
use Eureka\Component\Orm\Generator\Compiler\Field\Field;
use Eureka\Component\Orm\Generator\Compiler\Field\FieldValidatorService;

/**
 * Class AbstractClassCompiler
 *
 * @author Romain Cottard
 */
class AbstractClassCompiler extends AbstractCompiler
{
    protected const TYPE_REPOSITORY = 'repository';
    protected const TYPE_MAPPER     = 'mapper';
    protected const TYPE_ENTITY     = 'entity';

    /** @var string $type */
    private string $type;

    /** @var Config\ConfigInterface $config */
    protected Config\ConfigInterface $config;

    /** @var Field[] $fields */
    protected array $fields = [];

    /**
     * AbstractCompiler constructor.
     *
     * @param Config\ConfigInterface $config
     * @param string $type
     * @param array $templates
     */
    public function __construct(Config\ConfigInterface $config, string $type, array $templates)
    {
        parent::__construct($templates);

        $this->config    = $config;
        $this->type      = $type;
    }

    /**
     * @return $this
     * @throws GeneratorException
     */
    public function initFields(): self
    {
        $statement = $this->connection->query('SHOW FULL COLUMNS FROM ' . $this->config->getDbTable());

        $this->fields = [];
        while (false !== ($column = $statement->fetch(Connection::FETCH_OBJ))) {
            $this->fields[] = new Field($column, $this->config->getDbPrefix(), $this->config->getValidation());
        }

        return $this;
    }

    /**
     * Compile abstract file mapper class.
     *
     * @return void
     * @throws GeneratorException
     */
    public function compile(): void
    {
        foreach ($this->templates as $template => $isAbstract) {
            $file = $this->getOutputFile($this->type, $isAbstract);

            if (!$isAbstract && file_exists($file)) {
                continue;
            }

            //~ Get context
            $context = $this->updateContext($this->getContext(), $isAbstract);

            //~ Render template
            $rendered = $this->renderTemplate($template, $context);

            //~ Write template
            $this->writeTemplate($file, $rendered, !$isAbstract);
        }
    }

    /**
     * @param string $file
     * @param string $content
     * @param bool $skipExisting
     * @return void
     * @throws GeneratorException
     */
    protected function writeTemplate(string $file, string $content, bool $skipExisting = false): void
    {
        if ($this->verbose) {
            echo '$file: ' . $file . PHP_EOL;
        }

        if ($skipExisting && file_exists($file)) {
            return;
        }

        if (file_put_contents($file, $content) === false) {
            throw new GeneratorException('Unable to write final file! (file: ' . $file . ')'); // @codeCoverageIgnore
        }
    }

    /**
     * @param string $type
     * @param bool $isAbstract
     * @return string
     */
    protected function getOutputFile(string $type, bool $isAbstract = false): string
    {
        $basePathAbstract = $isAbstract ? 'Abstracts/' : '';
        $abstractPrefix   = $isAbstract ? 'Abstract' : '';

        switch ($type) {
            case self::TYPE_REPOSITORY:
                $basePath = $this->config->getBasePathForRepository();
                $baseName = $this->config->getClassname();
                $filePathName = $basePath . '/' . $baseName . 'RepositoryInterface.php';
                break;
            case self::TYPE_ENTITY:
                $basePath = $this->config->getBasePathForEntity() . '/' . $basePathAbstract;
                $baseName = $this->config->getClassname();
                $filePathName = $basePath . $abstractPrefix . $baseName . '.php';
                break;
            case self::TYPE_MAPPER:
                $basePath = $this->config->getBasePathForMapper() . '/' . $basePathAbstract;
                $baseName = $this->config->getClassname();
                $filePathName = $basePath . $abstractPrefix . $baseName . 'Mapper.php';
                break;
            default:
                throw new \DomainException('Invalid type file'); // @codeCoverageIgnore
        }

        if (!is_dir($basePath) && !mkdir($basePath, 0755, true)) {
            throw new \RuntimeException('Cannot created output directory! (dir:' . $basePath . ')'); // @codeCoverageIgnore
        }

        return $filePathName;
    }

    /**
     * @return string
     */
    protected function buildValidatorConfig(): string
    {
        $fieldValidatorService = new FieldValidatorService();

        $config = [];
        foreach ($this->fields as $field) {
            $config[$field->getName()] = "
            '" . $field->getName() . "' => [
                'type'      => '" . $field->getType()->getValidatorType() . "',
                'options'   => " . $fieldValidatorService->getValidatorOptions($field, true) . ",
            ],";
        }

        return implode('', $config);
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
        $context->add('comment.author', $this->config->getAuthor());
        $context->add('comment.copyright', $this->config->getCopyright());
        $context->add('class.name', $this->config->getClassname());
        $context->add('class.entity', $this->config->getClassname());
        $context->add('class.mapper', $this->config->getClassname() . 'Mapper');
        $context->add('class.repository', $this->config->getClassname() . 'RepositoryInterface');
        $context->add('orm.namespace', $ormNamespace);
        $context->add('database.table', $this->config->getDbTable());

        return $context;
    }
}
