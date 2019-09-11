<?php declare(strict_types=1);

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eureka\Component\Orm\Traits;

use Eureka\Component\Orm\Exception\OrmException;
use Eureka\Component\Validation\Entity\GenericEntity;
use Eureka\Component\Validation\Entity\ValidatorEntityFactory;
use Eureka\Component\Validation\Exception\ValidationException;
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

    /** @var array $validationConfig */
    private $validationConfig = [];

    /**
     * @param array $data
     * @param array $config
     * @return GenericEntity
     */
    public function newGenericEntity(array $data = [], array $config = []): GenericEntity
    {
        return new GenericEntity($this->getValidatorFactory(), $config ?? $this->getValidatorConfig(), $data);
    }

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

    /**
     * @return array
     */
    protected function getValidatorConfig(): array
    {
        return $this->validationConfig;
    }

    /**
     * @param array $config
     * @return void
     */
    protected function setValidatorConfig(array $config): void
    {
        $this->validationConfig = $config;
    }

    /**
     * @param string $field
     * @param $data
     * @return void
     * @throws ValidationException
     */
    protected function validateInput(string $field, $data): void
    {
        if (empty($this->validationConfig[$field])) {
            throw new ValidationException('No validation config defined for given field! (field: ' . $field . ')');
        }

        $config = $this->validationConfig[$field];
        if (empty($config['type'])) {
            throw new ValidationException('No validation type defined for given field! (field: ' . $field . ')');
        }

        $validatorType    = $config['type'];
        $validatorOptions = $config['options'] ?? [];

        if (strpos($validatorType, '\\') !== false) {
            //~ Custom class validator
            $validator = new $validatorType();
        } else {
            //~ Component type validator
            $validator = $this->getValidatorFactory()->getValidator($validatorType);
        }

        $validator->validate($data, $validatorOptions);
    }
}
