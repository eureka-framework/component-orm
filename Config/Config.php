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
 * @version 2.0.0
 */
class Config extends ConfigAbstract
{

    /**
     * Initialize config.
     *
     * @param  array $config
     * @return $this
     */
    public function init($config, $global)
    {
        //~ Comment
        $this->author      = $global['comment']['author'];
        $this->version     = $global['comment']['version'];

        //~ Class
        $this->classname   = $config['class']['classname'];
        $this->namespace   = $config['class']['namespace'];

        //~ Cache
        $this->cacheName   = $config['cache']['name'];
        $this->cachePrefix = $config['cache']['prefix'];

        //~ Db
        $this->dbConfig    = $config['db']['config'];
        $this->dbTable     = $config['db']['table'];
        $this->dbPrefix    = $config['db']['prefix'];

        //~ Joins
        $this->joinList    = (isset($config['joins']) ? $config['joins'] : array());

        return $this;
    }

}
