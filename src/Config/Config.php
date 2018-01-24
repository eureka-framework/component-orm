<?php

/*
 * Copyright (c) Romain Cottard
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
     * @return $this
     */
    protected function init($config)
    {
        //~ Comment
        $this->author    = $config['comment']['author'];
        $this->copyright = $config['comment']['copyright'];

        //~ Class
        $this->classname = $config['class']['classname'];

        //~ Namespace
        $this->baseNamespaceForData   = $config['namespace']['data'];
        $this->baseNamespaceForMapper = $config['namespace']['mapper'];

        //~ Path
        $this->basePathForData   = $config['path']['data'];
        $this->basePathForMapper = $config['path']['mapper'];

        //~ Cache
        $this->cacheName   = '';//$config['cache']['name'];
        $this->cachePrefix = $config['cache']['prefix'];

        //~ Db
        $this->dbTable  = $config['database']['table'];
        $this->dbPrefix = $config['database']['prefix'];
        $this->dbConfig = $config['database']['config'];

        //~ Joins
        $this->joinList = (!empty($config['joins']) ? $config['joins'] : []);

        return $this;
    }
}
