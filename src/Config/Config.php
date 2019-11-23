<?php declare(strict_types=1);

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
    protected function init(array $config): ConfigInterface
    {
        //~ Comment
        $this->author    = $config['comment']['author'];
        $this->copyright = $config['comment']['copyright'];

        //~ Class
        $this->classname = $config['class']['classname'];

        //~ Namespace
        $this->baseNamespaceForEntity     = $config['namespace']['entity'];
        $this->baseNamespaceForMapper     = $config['namespace']['mapper'];
        $this->baseNamespaceForRepository = isset($config['namespace']['repository']) ? $config['namespace']['repository'] : null;

        //~ Path
        $this->basePathForEntity     = $config['path']['entity'];
        $this->basePathForMapper     = $config['path']['mapper'];
        $this->basePathForRepository = isset($config['path']['repository']) ? $config['path']['repository'] : null;

        //~ Cache
        $this->cachePrefix = $config['cache']['prefix'];

        //~ Db
        $this->dbTable   = $config['database']['table'];
        if (!is_array($config['database']['prefix'])) {
            $this->dbPrefix = [$config['database']['prefix']];
        } else {
            $this->dbPrefix = $config['database']['prefix'];
        }

        //~ Validation
        $this->validation = !empty($config['validation']) ? $config['validation'] : [];

        //~ Joins
        $this->joinList = (!empty($config['joins']) ? $config['joins'] : []);

        return $this;
    }
}
