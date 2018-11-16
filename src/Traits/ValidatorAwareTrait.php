<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Eureka\Component\Orm\Traits;

use Eureka\Component\Orm\RepositoryInterface;
use Eureka\Component\Validation\Entity\FormEntity;
use Eureka\Component\Validation\ValidatorFactoryInterface;
use Eureka\Component\Validation\ValidatorInterface;
use Psr\Container\ContainerInterface;

/**
 * Repository trait.
 *
 * @author Romain Cottard
 */
trait ValidatorAwareTrait
{
    /** @var null|\Psr\Container\ContainerInterface */
    protected $validatorFactoryContainer = null;

    /**
     * @param  \Psr\Container\ContainerInterface $validatorFactoryContainer
     * @return $this
     */
    protected function setValidatorFactoryContainer(ContainerInterface $validatorFactoryContainer = null): RepositoryInterface
    {
        $this->validatorFactoryContainer = $validatorFactoryContainer;

        return $this;
    }

    /**
     * @return \Eureka\Component\Validation\Entity\FormEntity
     */
    protected function newEntityForm(): FormEntity
    {
        return new FormEntity($this->getValidatorFactory(), $this->getValidatorConfig());
    }

    /**
     * @param  string $type
     * @return \Eureka\Component\Validation\ValidatorInterface
     */
    protected function getValidator($type): ValidatorInterface
    {
        return $this->validatorFactoryContainer->get($type);
    }

    /**
     * @return \Eureka\Component\Validation\ValidatorFactoryInterface
     */
    protected function getValidatorFactory(): ValidatorFactoryInterface
    {
        return $this->validatorFactoryContainer->get('factory');
    }

    /**
     * @return array
     */
    protected function getValidatorConfig(): array
    {
        $configs   = $this->validatorFactoryContainer->get('config');
        $className = get_class($this);
        if (!isset($configs[$className])) {
            return [];
        }

        return $configs[$className];
    }
}
