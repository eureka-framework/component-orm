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
     * @return self|RepositoryInterface
     */
    public function enableCacheOnRead(): RepositoryInterface;

    /**
     * Disable cache on read query.
     *
     * @return self|RepositoryInterface
     */
    public function disableCacheOnRead(): RepositoryInterface;
}
