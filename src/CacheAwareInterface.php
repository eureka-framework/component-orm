<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Eureka\Component\Orm;

/**
 * Cache aware interface.
 *
 * @author Romain Cottard
 */
interface CacheAwareInterface
{
    /**
     * Enable cache on read queries.
     *
     * @return $this
     */
    public function enableCacheOnRead(): RepositoryInterface;

    /**
     * Disable cache on read query.
     *
     * @return $this
     */
    public function disableCacheOnRead(): RepositoryInterface;
}
