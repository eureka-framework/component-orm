<?php

/**
 * Copyright (c) 2010-2016 Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eureka\Component\Orm\Config;

/**
 * Data Mapper config interface for db/table orm generator.
 *
 * @author  Romain Cottard
 */
class Config extends AbstractConfig
{
    /**
     * Initialize config.
     *
     * @param  array $config
     * @param  array $global
     * @return $this
     */
    protected function init($config, $global)
    {
        //~ Comment
        $this->author  = $global['comment']['author'];
        $this->version = $global['comment']['version'];

        //~ Class
        $this->classname = $config['class']['classname'];
        $this->namespace = $config['class']['namespace'];

        //~ Namespace
        $this->baseNamespaceForData   = $config['namespace']['data'];
        $this->baseNamespaceForMapper = $config['namespace']['mapper'];

        //~ Path
        $this->basePathForData   = $config['path']['data'];
        $this->basePathForMapper = $config['path']['mapper'];

        //~ Cache
        $this->cacheName   = $config['cache']['name'];
        $this->cachePrefix = $config['cache']['prefix'];

        //~ Db
        $this->dbTable  = $config['db']['table'];
        $this->dbPrefix = $config['db']['prefix'];

        //~ Joins
        $this->joinList = (isset($config['joins']) ? $config['joins'] : array());

        return $this;
    }
}
