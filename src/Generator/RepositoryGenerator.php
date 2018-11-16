<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Eureka\Component\Orm\Generator;

use Eureka\Component\Orm\Exception\GeneratorException;

/**
 * Repository Generator
 *
 * @author Romain Cottard
 */
class RepositoryGenerator extends AbstractGenerator
{
    private $templates = [
        'repository' => __DIR__ . '/Templates/RepositoryInterface.template',
    ];

    /**
     * Generate abstract file mapper class.
     *
     * @param  string $dir Directory for class
     * @return void
     * @throws \Eureka\Component\Orm\Exception\GeneratorException
     */
    protected function generateRepositoryFile($dir)
    {
        $file = $dir . '/' . $this->config->getClassname() . 'RepositoryInterface.php';

        if (file_exists($file)) {
            return;
        }

        if (!is_readable($file) && false === file_put_contents($file, '')) {
            throw new GeneratorException('Cannot create empty class file: ' . $file);
        }

        $namespace = $this->config->getBaseNamespaceForRepository();
        $currentNamespace = substr(__NAMESPACE__, 0, strrpos(__NAMESPACE__,'\\'));

        $this->renderTemplate($template, )
        $content = '<?' . 'php

/*
 * Copyright (c) ' . $this->config->getCopyright() . '
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace ' . $namespace . ';

use ' . $currentNamespace . '\RepositoryInterface;

/**
 * ' . $this->config->getClassname() . ' repository interface.
 *
 * @author ' . $this->config->getAuthor() . '
 */
interface ' . $this->config->getClassname() . 'RepositoryInterface extends RepositoryInterface
{
}
';
        if (false === file_put_contents($file, $content)) {
            throw new \RuntimeException('Unable to write file content! (file: ' . $file . ')');
        }
    }

}
