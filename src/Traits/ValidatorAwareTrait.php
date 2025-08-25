<?php

declare(strict_types=1);

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eureka\Component\Orm\Traits;

use Eureka\Component\Validation\Entity\GenericEntity;
use Eureka\Component\Validation\Exception\ValidationException;
use Eureka\Component\Validation\ValidatorEntityFactoryInterface;
use Eureka\Component\Validation\ValidatorFactoryInterface;
use Eureka\Component\Validation\ValidatorInterface;

/**
 * Repository trait.
 *
 * @author Romain Cottard
 */
trait ValidatorAwareTrait
{
    /** @var ValidatorFactoryInterface|null $validatorFactory */
    private ?ValidatorFactoryInterface $validatorFactory;

    /** @var ValidatorEntityFactoryInterface|null $validatorEntityFactory */
    private ?ValidatorEntityFactoryInterface $validatorEntityFactory;

    /** @var array<string, array{type?:string, options?: array<string, string|int|float>}> $validationConfig */
    private array $validationConfig = [];

    /**
     * @param array<string, int|float|bool|string|null> $data
     * @param array<string, array{type?:string, options?: array<string, string|int|float>}> $config
     * @return GenericEntity
     */
    public function newGenericEntity(array $data = [], array $config = []): GenericEntity
    {
        if ($this->getValidatorEntityFactory() === null) {
            // @codeCoverageIgnoreStart
            throw new \LogicException(
                'Validator Entity Factory is null, cannot create generic entity from this service!',
            );
            // @codeCoverageIgnoreEnd
        }

        $config = $config !== [] ? $config : $this->getValidatorConfig();
        return $this->getValidatorEntityFactory()->createGeneric($config, $data);
    }

    /**
     * @param ValidatorFactoryInterface|null $validatorFactory
     * @param ValidatorEntityFactoryInterface|null $validatorEntityFactory
     * @return static
     */
    protected function setValidatorFactories(
        ?ValidatorFactoryInterface $validatorFactory,
        ?ValidatorEntityFactoryInterface $validatorEntityFactory,
    ): static {
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
     * @return array<string, array{type?:string, options?: array<string, string|int|float>}>
     */
    public function getValidatorConfig(): array
    {
        return $this->validationConfig;
    }

    /**
     * @param array<string, array{type?:string, options?: array<string, string|int|float>}> $config
     * @return void
     */
    protected function setValidatorConfig(array $config): void
    {
        $this->validationConfig = $config;
    }

    /**
     * @param string $field
     * @param mixed $data
     * @return void
     * @throws ValidationException
     */
    protected function validateInput(string $field, mixed $data): void
    {
        if (empty($this->validationConfig[$field])) {
            // @codeCoverageIgnoreStart
            throw new ValidationException("No validation config defined for given field! (field: $field)");
            // @codeCoverageIgnoreEnd
        }

        $config = $this->validationConfig[$field];
        if (empty($config['type'])) {
            // @codeCoverageIgnoreStart
            throw new ValidationException("No validation type defined for given field! (field: $field)");
            // @codeCoverageIgnoreEnd
        }

        $validatorType    = $config['type'];
        $validatorOptions = $config['options'] ?? [];

        if (str_contains($validatorType, '\\')) {
            //~ Custom class validator
            /** @var class-string<ValidatorInterface> $validatorType */
            $validator = new $validatorType(); // @codeCoverageIgnore
        } else {
            //~ Component type validator
            if ($this->getValidatorFactory() === null) {
                // @codeCoverageIgnoreStart
                throw new \LogicException('Validator factory is null, cannot validate type!');
                // @codeCoverageIgnoreEnd
            }
            $validator = $this->getValidatorFactory()->getValidator($validatorType);
        }

        $validator->validate($data, $validatorOptions);
    }
}
