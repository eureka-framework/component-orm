<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Eureka\Component\Orm\Generator\Compiler;

/**
 * Class Context
 *
 * @author Romain Cottard
 */
class Context
{
    /** @var string[] $context */
    private array $context;

    /**
     * Context constructor.
     */
    public function __construct()
    {
        $this->context = [];
    }

    /**
     * @param string $key
     * @param mixed $value
     * @return Context
     */
    public function add(string $key, $value): self
    {
        $this->context['{{ ' . $key . ' }}'] = $value;

        return $this;
    }

    /**
     * @param string $key
     * @return mixed|string|null
     */
    public function get(string $key)
    {
        if (isset($this->context['{{ ' . $key . ' }}'])) {
            return $this->context['{{ ' . $key . ' }}'];
        }

        return null; // @codeCoverageIgnore
    }

    /**
     * @param Context $context
     * @return Context
     */
    public function merge(Context $context): self
    {
        $this->context += $context->toArray();

        return $this;
    }

    /**
     * @return string[]
     */
    public function getKeys(): array
    {
        return array_keys($this->context);
    }

    /**
     * @return string[]
     */
    public function getValues(): array
    {
        return array_values($this->context);
    }

    /**
     * @return array
     */
    private function toArray(): array
    {
        return $this->context;
    }
}
