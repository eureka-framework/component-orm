<?php declare(strict_types=1);

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eureka\Component\Orm\Traits;

use Eureka\Component\Validation\Entity\GenericEntity;
use Eureka\Component\Validation\Entity\ValidatorEntityFactory;
use Eureka\Component\Validation\ValidatorEntityFactoryInterface;
use Eureka\Component\Validation\ValidatorFactoryInterface;

/**
 * Repository trait.
 *
 * @author Romain Cottard
 */
trait ValidatorAwareTrait
{
    /** @var ValidatorFactoryInterface $validatorFactory */
    private $validatorFactory;

    /** @var ValidatorEntityFactory $validatorEntityFactory */
    private $validatorEntityFactory;

    /**
     * @param ValidatorFactoryInterface $validatorFactory
     * @param ValidatorEntityFactoryInterface $validatorEntityFactory
     * @return self
     */
    protected function setValidatorFactories(
        ?ValidatorFactoryInterface $validatorFactory,
        ?ValidatorEntityFactoryInterface $validatorEntityFactory
    ): self {
        $this->validatorFactory       = $validatorFactory;
        $this->validatorEntityFactory = $validatorEntityFactory;

        return $this;
    }

    /**
     * @param array $config
     * @param array $data
     * @return GenericEntity
     */
    protected function newGenericEntity(array $config = [], array $data = []): GenericEntity
    {
        return new GenericEntity($this->getValidatorFactory(), $config, $data);
    }

    /**
     * @return ValidatorFactoryInterface|null
     */
    protected function getValidatorFactory(): ?ValidatorFactoryInterface
    {
        return $this->validatorFactory;
    }

    /**
     * @return ValidatorEntityFactoryInterface|null
     */
    protected function getValidatorEntityFactory(): ?ValidatorEntityFactoryInterface
    {
        return $this->validatorEntityFactory;
    }
}
