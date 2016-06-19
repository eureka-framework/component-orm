<?php

/**
 * Copyright (c) 2010-2016 Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eureka\Component\Orm;

use Eureka\Component\Debug\Debug;
use Eureka\Component\Orm\Config\ConfigInterface;
use Eureka\Component\Yaml\Yaml;

/**
 * Class to generate model classes. Better way to manipulate table.
 *
 * @author Romain Cottard
 * @version 2.0.0
 */
class Builder
{
    /**
     * @var ConfigInterface $config ORM configuration object.
     */
    protected $config = null;

    /**
     * @var array $config_names List of config names.
     */
    protected $config_names = array();

    /**
     * @var array $vars List of variables to replace in template
     */
    protected $vars = array();

    /**
     * @var Column[] $columns List of columns to treat for current table.
     */
    protected $columns = array();

    /**
     * @var boolean $verbose Verbose active or not.
     */
    protected $verbose = true;

    /**
     * @var string $baseDir Base directory for Data Mapper classes
     */
    protected $baseDir = '';

    /**
     * @var string $baseNamespace Base namespace for Data Mapper classes
     */
    protected $baseNamespace = '';

    /**
     * @var \PDO $db
     */
    protected $db = null;

    /**
     * Set verbose mode
     *
     * @param  bool $verbose
     * @return $this
     */
    public function setVerbose($verbose)
    {
        $this->verbose       = (bool) $verbose;

        return $this;
    }

    /**
     * Set directory.
     *
     * @param  string $baseDir
     * @return $this
     */
    public function setDirectory($baseDir)
    {
        $this->baseDir = $baseDir;

        return $this;
    }

    /**
     * Set namespace
     *
     * @param  string $namespace
     * @return $this
     */
    public function setNamespace($namespace)
    {
        $this->baseNamespace = $namespace;

        return $this;
    }

    /**
     * Set database connection.
     *
     * @param \PDO $db
     * @return $this
     */
    public function setDatabase(\PDO $db)
    {
        $this->db = $db;

        return $this;
    }

    /**
     * Build model classes.
     *
     * @param  ConfigInterface[] $configs
     * @return $this
     */
    public function build($configs)
    {
        foreach ($configs as $config) {

            $this->config = $config;

            $this->initVars();
            $this->loadColumns();

            $this->buildDataClasses();
            $this->buildMapperClasses();
            $this->generateClasses();
        }

        return $this;
    }

    protected function initVars()
    {
        $this->vars = array();

        //---- BUILT VARS ----
        $this->vars['db_fields']       = '';
        $this->vars['db_primary_keys'] = '';

        //~ Getters / Setters / Joins vars
        $this->vars['properties'] = '';
        $this->vars['getters']    = '';
        $this->vars['setters']    = '';
        $this->vars['joins']      = '';
    }

    /**
     * Load columns data.
     *
     * @return void
     */
    protected function loadColumns()
    {
        $statement = $this->db->query('SHOW COLUMNS FROM ' . $this->config->getDbTable());

        $this->columns = array();
        while (false !== ($column = $statement->fetchObject())) {
            $this->columns[] = new Column($column, $this->config->getDbPrefix());
        }
    }

    /**
     * Build data classes
     *
     * @return void
     */
    protected function buildDataClasses()
    {
        $this->display(' * Build Data classes for table "' . $this->config->getDbTable() . '"' . PHP_EOL);
        $this->display('  > build    [  0%]: properties...   ' . PHP_EOL);
        $this->buildDataProperties();
        $this->display('  > build    [ 25%]: getters...      ' . PHP_EOL);
        $this->buildDataGetterCacheKey();
        $this->buildDataGetters();
        $this->display('  > build    [ 50%]: setters...      ' . PHP_EOL);
        $this->buildDataSetters();
        $this->display('  > build    [ 75%]: joins...        ' . PHP_EOL);
        $this->buildDataJoins();
        $this->display('  > build    [100%]: done !          ' . PHP_EOL);
        $this->display(PHP_EOL);
    }

    /**
     * Build properties var
     *
     * @return void
     */
    protected function buildDataProperties()
    {
        $properties = '';
        $hasAutoincrement = false;

        foreach ($this->columns as $column) {
            $properties .= "\n" . $column->getProperty();
            $hasAutoincrement |= $column->isAutoIncrement();
        }

        if ($hasAutoincrement) {
            $properties = '
    /**
     * @var bool $hasAutoIncrement If data has auto increment value.
     */
    protected $hasAutoIncrement = true;' . $properties;
        }

        $this->vars['properties'] = $properties;
    }

    /**
     * Build getters var
     *
     * @return void
     */
    protected function buildDataGetters()
    {
        $getters = '';

        foreach ($this->columns as $column) {
            $getters .= "\n" . $column->getGetter();
        }

        $this->vars['getters'] = $getters;
    }

    /**
     * Build cache key method
     *
     * @return void
     */
    protected function buildDataGetterCacheKey()
    {
        $keys = array();

        foreach ($this->columns as $column) {
            if ($column->isPrimaryKey()) {
                $keys[] = '$this->' . $column->getMethodNameGet() . '()';
            }
        }

        $key = "'" . $this->config->getDbTable() . "_data_' . " . implode(" . '_' . ", $keys);

        $this->vars['cache_key'] = '
    /**
     * Get cache key
     *
     * @return string
     */
    public function getCacheKey()
    {
        return ' . $key . ';
    }';
    }

    /**
     * Build setters var
     *
     * @return void
     */
    protected function buildDataSetters()
    {
        $setters = '';

        foreach ($this->columns as $column) {
            $setters .= "\n" . $column->getSetter();
        }

        $this->vars['setters'] = $setters;
    }

    /**
     * Build join getters
     *
     * @return void
     * @throws \LogicException
     */
    protected function buildDataJoins()
    {
        $joins    = array('one' => array(), 'many' => array());
        $joinsUse = array();

        foreach ($this->config->getAllJoin() as $name => $join) {

            $config = $join['class'];

            if (!($config instanceof ConfigInterface)) {
                throw new \LogicException('Joined class is not an instance of ConfigInterface!');
            }

            $class = $config->getClassname();

            if ('one' === $join['type']) {
                $joinMethod = $this->buildDataJoinsOne($config, $join['keys']);
            } else {
                $joinMethod = $this->buildDataJoinsMany($config, $join['keys']);
            }
            $joins[$join['type']][] = $joinMethod;

            $joinsUse[$class] = 'use ' . $this->baseNamespace . 'Data\\' . $config->getNamespace() . '\\' . $class . ';';
            $joinsUse[$class . 'Mapper'] = 'use ' . $this->baseNamespace . 'Mapper\\' . $config->getNamespace() . '\\' . $class . 'Mapper;';

        }

        $this->vars['joins_use'] = implode("\n", $joinsUse);
        $this->vars['joins']     = implode("\n", $joins['one']) . "\n" . implode("\n", $joins['many']);
    }

    /**
     * Build join one getters
     *
     * @param  ConfigInterface $config
     * @param  array $joinKeys
     * @return string
     * @throws \LogicException
     */
    protected function buildDataJoinsOne(ConfigInterface $config, array $joinKeys)
    {
        //~ Search for keys
        $keys = '';
        $joinKeys = array_flip($joinKeys);
        foreach ($this->columns as $column) {
            if (!isset($joinKeys[$column->getName()])) {
                continue;
            }

            $keys .= '
                \''  . $column->getName() . '\' => $this->' . $column->getMethodNameGet() . '(),';
        }

        if (empty($keys)) {
            throw new \LogicException('Empty keys list for Mapper::findByKeys() method !');
        }

        $propertyCacheName = 'joinOneCache' . $config->getClassname();
        $this->vars['properties'] .= '

    /**
     * @var ' . $config->getClassname() . ' $' . $propertyCacheName . '
     */
     protected $' . $propertyCacheName . ' = null;';

        //~ Generate method
        return '
    /**
     * Get ' . $config->getClassname() . ' data object.
     *
     * @param  bool $isForceReload
     * @return ' . $config->getClassname() . '
     */
    public function get' . $config->getClassname() . '($isForceReload = false)
    {
        if ($isForceReload || null === $this->' . $propertyCacheName . ') {
            $mapper = new ' . $config->getClassname() . 'Mapper($this->dependencyContainer->getDatabase(\'' . $config->getDbConfig() . '\'));
            $this->' . $propertyCacheName . ' = $mapper->findByKeys(array(' . $keys . '
            ));
        }
        
        return $this->' . $propertyCacheName . ';
    }
';
    }

    /**
     * Build join many getters
     *
     * @param  ConfigInterface $config
     * @param  array $joinKeys
     * @return string
     * @throws \LogicException
     */
    protected function buildDataJoinsMany(ConfigInterface $config, array $joinKeys)
    {
        //~ Search for keys
        $keys = '';
        $joinKeys = array_flip($joinKeys);
        foreach ($this->columns as $column) {
            if (!isset($joinKeys[$column->getName()])) {
                continue;
            }

            $keys .= '
            $mapper->addWhere(\'' . $column->getName() . '\', $this->' . $column->getMethodNameGet() .'());';
        }

        if (empty($keys)) {
            throw new \LogicException('Empty keys list for join all method !');
        }

        $propertyCacheName = 'joinManyCache' . $config->getClassname();
        $this->vars['properties'] .= '

    /**
     * @var ' . $config->getClassname() . '[] $' . $propertyCacheName . '
     */
     protected $' . $propertyCacheName . ' = null;';

        //~ Generate method
        return '
    /**
     * Get list of ' . $config->getClassname() . ' data objects.
     *
     * @param  bool $isForceReload
     * @return ' . $config->getClassname() . '[]
     */
    public function getAll' . $config->getClassname() . '($isForceReload = false)
    {
        if ($isForceReload || null === $this->' . $propertyCacheName . ') {
            $mapper = new ' . $config->getClassname() . 'Mapper($this->dependencyContainer->getDatabase(\'' . $config->getDbConfig() . '\'));' .
            $keys . '

            $this->' . $propertyCacheName . ' = $mapper->select();
        }
        
        return $this->' . $propertyCacheName . ';
    }
';
    }

    /**
     * Build data classes
     *
     * @return void
     */
    protected function buildMapperClasses()
    {
        $this->display(' * Build Mapper classes for table "' . $this->config->getDbTable() . '"' . PHP_EOL);

        $this->display('  > build    [  0%]: field...        ' . PHP_EOL);
        $this->buildMapperFields();
        $this->display('  > build    [ 25%]: primary keys... ' . PHP_EOL);
        $this->buildMapperPrimaryKeys();
        $this->display('  > build    [ 50%]: cache...        ' . PHP_EOL);
        $this->buildMapperCache();
        $this->display('  > build    [100%]: done !          ' . PHP_EOL);
        $this->display(PHP_EOL);
    }

    /**
     * Build cache var
     *
     * @return void
     */
    protected function buildMapperCache()
    {
        $this->vars['cache_name'] = (string) $this->config->getCacheName();
    }

    /**
     * Build fields var
     *
     * @return void
     */
    protected function buildMapperFields()
    {
        $fields       = array();
        $dataNamesMap = '';

        foreach ($this->columns as $column) {
            $field    = $column->getName();
            $fields[] = "        '" . $field . "'";
            $dataNamesMap .= "
        '" . $field . "' => array(
            'get'      => '" . $column->getMethodNameGet() . "',
            'set'      => '" . $column->getMethodNameSet() . "',
            'property' => '" . $column->getPropertyName() . "',
        ),";
        }

        $this->vars['db_fields']      = implode(",\n", $fields);
        $this->vars['data_names_map'] = $dataNamesMap;
    }

    /**
     * Build primary keys var
     *
     * @return void
     */
    protected function buildMapperPrimaryKeys()
    {

        $fields = array();

        foreach ($this->columns as $column) {
            if ($column->isPrimaryKey()) {
                $fields[] = "        '" . $column->getName() . "'";
            }
        }

        $this->vars['db_primary_keys'] = implode(",\n", $fields);
    }

    /**
     * Generate classes
     *
     * @return void
     */
    protected function generateClasses()
    {
        $this->display(' * Generate classes files for Data & Mapper ' . PHP_EOL);

        $this->display('  > generate [  0%]: Data File      ' . "\r");
        $this->generateDataFiles();

        $this->display('  > generate [ 50%]: Data File      ' . "\r");
        $this->generateMapperFiles();

        $this->display('  > generate [100%]: done !    ' . "\r");
        $this->display(PHP_EOL . PHP_EOL);
    }

    /**
     * Generate main class model.
     *
     * @return void
     * @throws \RuntimeException
     */
    protected function generateDataFiles()
    {
        $dir = $this->baseDir . '/Data/' . str_replace('\\', '/', trim($this->config->getNamespace(), '\\ '));

        if (!is_dir($dir . '/Abstracts') && false === mkdir($dir . '/Abstracts', 0755, true)) {
            throw new \RuntimeException('Cannot create directory: ' . $dir . '/Abstracts');
        }

        $this->generateDataFileAbstract($dir . '/Abstracts');
        $this->generateDataFile($dir);
    }

    /**
     * Generate abstract file class.
     *
     * @param  string $dir Directory for class
     * @return void
     * @throws \RuntimeException
     */
    protected function generateDataFileAbstract($dir)
    {
        $file = $dir . '/' . $this->config->getClassname() . 'Abstract.php';

        if (!is_readable($file) && false === file_put_contents($file, '')) {
            throw new \RuntimeException('Cannot create empty class file: ' . $file);
        }

        $content = '<?php

/**
 * Copyright (c) 2010-2016 ' . $this->config->getAuthor() . '
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ' . $this->baseNamespace . 'Data\\' . $this->config->getNamespace() . '\Abstracts;

use Eureka\Component\Orm\DataMapper\DataAbstract;
' . $this->vars['joins_use']  . '

/**
 * /!\ AUTO GENERATED FILE. DO NOT EDIT THIS FILE.
 * THIS FILE IS OVERWRITTEN WHEN THE ORM SCRIPT GENERATOR IS RUN.
 * You can add you specific code in child class: ' . $this->config->getClassname() . '
 *
 * @author  ' . $this->config->getAuthor() . '
 * @version ' . $this->config->getVersion() . '
 */
abstract class ' . $this->config->getClassname() . 'Abstract extends DataAbstract
{' . $this->vars['properties'] . '
' . $this->vars['cache_key'] . '
' . $this->vars['getters'] . '
' . $this->vars['setters'] . '
' . $this->vars['joins'] . '}
';
        if (false === file_put_contents($file, $content)) {
            throw new \RuntimeException('Unable to write file content! (file: ' . $file . ')');
        }
    }

    /**
     * Generate child class if not already exists.
     *
     * @param  string $dir Directory for class
     * @return void
     * @throws \RuntimeException
     */
    protected function generateDataFile($dir)
    {
        $file = $dir . '/' . $this->config->getClassname() . '.php';

        if (file_exists($file)) {
            return;
        }

        if (!is_readable($file) && false === file_put_contents($file, '')) {
            throw new \RuntimeException('Cannot create empty class file: ' . $file);
        }

        $content = '<?php

/**
 * Copyright (c) 2010-2016 ' . $this->config->getAuthor() . '
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ' . $this->baseNamespace . 'Data\\' . $this->config->getNamespace() . ';

/**
 * DataMapper Data class for table "' . $this->config->getDbTable() . '"
 *
 * @author  ' . $this->config->getAuthor() . '
 * @version ' . $this->config->getVersion() . '
 */
class ' . $this->config->getClassname() . ' extends Abstracts\\' . $this->config->getClassname() . 'Abstract
{
}
';
        if (false === file_put_contents($file, $content)) {
            throw new \RuntimeException('Unable to write file content! (file: ' . $file . ')');
        }
    }

    /**
     * Generate main class model.
     *
     * @return void
     * @throws \RuntimeException
     */
    protected function generateMapperFiles()
    {
        $dir = $this->baseDir . '/Mapper/' . str_replace('\\', '/', trim($this->config->getNamespace(), '\\ '));

        if (!is_dir($dir . '/Abstracts') && false === mkdir($dir . '/Abstracts', 0755, true)) {
            throw new \RuntimeException('Cannot create directory: ' . $dir);
        }

        $this->generateMapperFileAbstract($dir . '/Abstracts');
        $this->generateMapperFile($dir);
    }

    /**
     * Generate abstract file mapper class.
     *
     * @param  string $dir Directory for class
     * @return void
     * @throws \RuntimeException
     */
    protected function generateMapperFileAbstract($dir)
    {
        $file = $dir . '/' . $this->config->getClassname() . 'MapperAbstract.php';

        if (!is_readable($file) && false === file_put_contents($file, '')) {
            throw new \RuntimeException('Cannot create empty class file: ' . $file);
        }

        $content = '<?php

/**
 * Copyright (c) 2010-2016 ' . $this->config->getAuthor() . '
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ' . $this->baseNamespace . 'Mapper\\' . $this->config->getNamespace() . '\Abstracts;

use Eureka\Component\Orm\DataMapper\MapperAbstract;
use ' . $this->baseNamespace . 'Data\\' . $this->config->getNamespace() . '\\' . $this->config->getClassname() . ';

/**
 * /!\ AUTO GENERATED FILE. DO NOT EDIT THIS FILE.
 * THIS FILE IS OVERWRITTEN WHEN THE ORM SCRIPT GENERATOR IS RAN.
 * You can add you specific code in child class: ' . $this->config->getClassname() . '
 *
 * @author  ' . $this->config->getAuthor() . '
 * @version ' . $this->config->getVersion() . '
 */
abstract class ' . $this->config->getClassname() . 'MapperAbstract extends MapperAbstract
{
    /**
     * @var string $dataClass Name of class use to instance DataMapper Data class.
     */
    protected $dataClass = \'\\' . $this->baseNamespace . 'Data\\' . $this->config->getNamespace() . '\\' . $this->config->getClassname() . '\';

    /**
     * @var string $databaseConfig Database config name
     */
    protected $databaseConfig = \'' . $this->config->getDbConfig() . '\';

    /**
     * @var array $fields List of fields
     */
    protected $table = \'' . $this->config->getDbTable() . '\';

    /**
     * @var array $fields List of fields
     */
    protected $fields = array(
' . $this->vars['db_fields'] . '
    );

    /**
     * @var array $primaryKeys List of primary keys
     */
    protected $primaryKeys = array(
' . $this->vars['db_primary_keys'] . '
    );

    /**
     * @var array $primaryKeys List of primary keys
     */
    protected $dataNamesMap = array(
' . $this->vars['data_names_map'] . '
    );

    /**
     * @var string $cacheName Name of cache config to use.
     */
    protected $cacheName = \'' . $this->config->getCacheName() . '\';

    /**
     * @var bool $isCacheEnabled
     */
    protected $isCacheEnabled = ' . var_export($this->config->hasCache(), true) . ';

    /**
     * Get first row corresponding of the id.
     *
     * @param  integer $id
     * @return ' . $this->config->getClassname() . '
     */
    public function findById($id)
    {
        return parent::findById($id);
    }

    /**
     * Get first row corresponding of the primary keys.
     *
     * @param  array $primaryKeys
     * @return ' . $this->config->getClassname() . '
     */
    public function findByKeys($primaryKeys)
    {
        return parent::findByKeys($primaryKeys);
    }

    /**
     * Select all rows corresponding of where clause.
     *
     * @return ' . $this->config->getClassname() . '[] List of row.
     */
    public function select()
    {
        return parent::select();
    }

    /**
     * Select first rows corresponding to where clause.
     *
     * @return ' . $this->config->getClassname() . '
     */
    public function selectOne()
    {
        return parent::selectOne();
    }

    /**
     * Fetch rows for specified query.
     *
     * @param string $query
     * @return ' . $this->config->getClassname() . '[] Array of model_base object for query.
     */
    public function query($query)
    {
        return parent::query($query);
    }

    /**
     * Create new instance of extended DataAbstract class & return it.
     *
     * @param  \stdClass $row
     * @param  bool      $exists
     * @return ' . $this->config->getClassname() . '
     */
    public function newDataInstance(\stdClass $row = null, $exists = false)
    {
        return parent::newDataInstance($row, $exists);
    }
}
';
        if (false === file_put_contents($file, $content)) {
            throw new \RuntimeException('Unable to write file content! (file: ' . $file . ')');
        }
    }

    /**
     * Generate main file mapper class.
     *
     * @param  string $dir Directory for class
     * @return void
     * @throws \RuntimeException
     */
    protected function generateMapperFile($dir)
    {
        $file = $dir . '/' . $this->config->getClassname() . 'Mapper.php';

        if (file_exists($file)) {
            return;
        }

        if (!is_readable($file) && false === file_put_contents($file, '')) {
            throw new \RuntimeException('Cannot create empty class file: ' . $file);
        }

        $content = '<?php

/**
 * Copyright (c) 2010-2016 ' . $this->config->getAuthor() . '
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ' . $this->baseNamespace . 'Mapper\\' . $this->config->getNamespace() . ';

/**
 * DataMapper Mapper class for table "' . $this->config->getDbTable() . '"
 *
 * @author  ' . $this->config->getAuthor() . '
 * @version ' . $this->config->getVersion() . '
 */
class ' . $this->config->getClassname() . 'Mapper extends Abstracts\\' . $this->config->getClassname() . 'MapperAbstract
{
}
';
        if (false === file_put_contents($file, $content)) {
            throw new \RuntimeException('Unable to write file content! (file: ' . $file . ')');
        }
    }

    /**
     * Display text only if is verbose mode (not for phpunit)
     *
     * @param string $text
     */
    protected function display($text)
    {
        if ($this->verbose) {
            echo $text;
        }

    }
}
