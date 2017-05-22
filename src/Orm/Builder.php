<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eureka\Component\Orm;

use Doctrine\DBAL\Connection as ConnectionInterface;
use \PDO;
use \LogicException;
use \RuntimeException;

/**
 * Class to generate model classes. Better way to manipulate table.
 *
 * @author Romain Cottard
 */
class Builder
{
    /**
     * @var Config\ConfigInterface $config ORM configuration object.
     */
    protected $config = null;

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
     * @var ConnectionInterface $db
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
     * @param  ConnectionInterface $db
     * @return $this
     */
    public function setDatabase(ConnectionInterface $db)
    {
        $this->db = $db;

        return $this;
    }

    /**
     * Build model classes.
     *
     * @param  Config\ConfigInterface[] $configs
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

    /**
     * Initialize vars.
     *
     * @return void
     */
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
        while (false !== ($column = $statement->fetch(PDO::FETCH_OBJ))) {
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

        $separator = "";
        foreach ($this->columns as $column) {
            $getters .= $separator . $column->getGetter();
            $separator = "\n";
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

        $key = "'" . $this->config->getCachePrefix() . "_data_' . " . implode(" . '_' . ", $keys);

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

        $separator = "";
        foreach ($this->columns as $column) {
            $setters .= $separator . $column->getSetter();
            $separator = "\n";
        }

        $this->vars['setters'] = $setters;
    }

    /**
     * Build join getters
     *
     * @return void
     * @throws LogicException
     */
    protected function buildDataJoins()
    {
        $joins    = array('one' => array(), 'many' => array());
        $joinsUse = array();

        foreach ($this->config->getAllJoin() as $name => $join) {
            if (!class_exists($join['class'])) {
                throw new LogicException('Joined config class does not exist! (class: ' . $join['class'] . ')');
            }
            $config = new $join['class']();

            if (!($config instanceof Config\ConfigInterface)) {
                throw new LogicException('Joined class is not an instance of ConfigInterface! (class: ' . $join['class'] . ')');
            }

            $class = $config->getClassname();

            if ('one' === $join['join']) {
                $joinMethod = $this->buildDataJoinsOne($config, $name, $join['keys']);
            } else {
                $joinMethod = $this->buildDataJoinsMany($config, $name, $join['keys']);
            }
            $joins[$join['type']][] = $joinMethod;

            $use = 'use ' . $config->getBaseNamespaceForData() . '\\' . $config->getNamespace() . '\\' . $class;
            $useMapper =  'use ' . $config->getBaseNamespaceForMapper() . '\\' . $config->getNamespace() . '\\' . $class . 'Mapper';
            $parentNamespaces = explode('\\', $config->getNamespace());
            $parentNamespace  = end($parentNamespaces);
            $joinsUse[$parentNamespace .$class] = $use . ' as ' .$parentNamespace . $class . ';';
            $joinsUse[$parentNamespace . $class . 'Mapper'] = $useMapper . ' as ' . $parentNamespace . $class . 'Mapper;';

        }

        $this->vars['joins_use'] = implode("\n", $joinsUse);
        $this->vars['joins']     = implode("\n", $joins['one']) . implode("\n", $joins['many']);
    }

    /**
     * Build join one getters
     *
     * @param  Config\ConfigInterface $config
     * @param  string $name
     * @param  array $joinKeys
     * @return string
     * @throws LogicException
     */
    protected function buildDataJoinsOne(Config\ConfigInterface $config, $name, array $joinKeys)
    {
        //~ Search for keys
        $keys = '';
        foreach ($this->columns as $column) {
            if (!isset($joinKeys[$column->getName()])) {
                continue;
            }

            $mappedBy = $column->getName();
            if (true !== $joinKeys[$column->getName()]) {
                $mappedBy = $joinKeys[$column->getName()];
            }

            $keys .= '
                \''  . $mappedBy . '\' => $this->' . $column->getMethodNameGet() . '(),';
        }

        if (empty($keys)) {
            throw new LogicException('Empty keys list for Mapper::findByKeys() method !');
        }

        $propertyCacheName = 'joinOneCache' . ucfirst($name);
        $classScopeNames   = explode('\\', $config->getNamespace());
        $classScopeName    = end($classScopeNames) . $config->getClassname();
        $this->vars['properties'] .= '

    /**
     * @var ' . $classScopeName . ' $' . $propertyCacheName . ' Cache property for ' . $propertyCacheName . '
     */
     protected $' . $propertyCacheName . ' = null;';

        //~ Generate method
        return '
    /**
     * Get ' . $classScopeName . ' data object.
     *
     * @param  bool $isForceReload
     * @return ' . $classScopeName . '
     */
    public function get' . ucfirst($name) . '($isForceReload = false)
    {
        if ($isForceReload || null === $this->' . $propertyCacheName . ') {
            $mapper = new ' . $classScopeName . 'Mapper($this->dependencyContainer->getDatabase(\'' . $config->getDbConfig() . '\'));

            //~ Use var to fix PSR-2 norms with multiple lines
            $keys = array(' . $keys . '
            );
            $this->' . $propertyCacheName . ' = $mapper->findByKeys($keys);
        }

        return $this->' . $propertyCacheName . ';
    }
';
    }

    /**
     * Build join many getters
     *
     * @param  Config\ConfigInterface $config Config instance.
     * @param  string $name Config name.
     * @param  array $joinKeys List of joined configs
     * @return string
     * @throws LogicException
     */
    protected function buildDataJoinsMany(Config\ConfigInterface $config, $name, array $joinKeys)
    {
        //~ Search for keys
        $keys = '';
        foreach ($this->columns as $column) {
            if (!isset($joinKeys[$column->getName()])) {
                continue;
            }

            $mappedBy = $column->getName();
            if (true !== $joinKeys[$column->getName()]) {
                $mappedBy = $joinKeys[$column->getName()];
            }

            $keys .= '

            $mapper->addWhere(\'' . $mappedBy . '\', $this->' . $column->getMethodNameGet() .'());';
        }

        if (empty($keys)) {
            throw new LogicException('Empty keys list for join all method !');
        }

        $propertyCacheName = 'joinManyCache' . ucfirst($name);
        $classScopeNames   = explode('\\', $config->getNamespace());
        $classScopeName    = end($classScopeNames) . $config->getClassname();
        $this->vars['properties'] .= '

    /**
     * @var ' . $classScopeName . '[] $' . $propertyCacheName . ' Cache property for ' . $propertyCacheName . '
     */
     protected $' . $propertyCacheName . ' = null;';

        //~ Generate method
        return '
    /**
     * Get list of ' . $classScopeName . ' data objects.
     *
     * @param  bool $isForceReload
     * @return ' . $classScopeName . '[]
     */
    public function getAll' . ucfirst($name) . '($isForceReload = false)
    {
        if ($isForceReload || null === $this->' . $propertyCacheName . ') {
            $mapper = new ' . $classScopeName . 'Mapper($this->getConnection());' .
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
     * @throws RuntimeException
     */
    protected function generateDataFiles()
    {
        $dir  = $this->baseDir . $this->config->getBasePathForData() . '/' . trim(str_replace('\\', '/', $this->config->getNamespace()), '/');

        if (!is_dir($dir) && false === mkdir($dir, 0755, true)) {
            throw new RuntimeException('Cannot create directory: ' . $dir);
        }

        $this->generateDataFileAbstract($dir);
        $this->generateDataFile($dir);
    }

    /**
     * Generate abstract file class.
     *
     * @param  string $dir Directory for class
     * @return void
     * @throws RuntimeException
     */
    protected function generateDataFileAbstract($dir)
    {
        $file = $dir . '/Abstract' . $this->config->getClassname() . '.php';

        if (!is_readable($file) && false === file_put_contents($file, '')) {
            throw new RuntimeException('Cannot create empty class file: ' . $file);
        }

        $namespace  = $this->config->getBaseNamespaceForData();

        $content = '<?' . 'php

/**
 * Copyright (c) ' . $this->config->getAuthor() . '
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ' . $namespace . ';

use Eureka\Component\Orm\DataMapper\AbstractData;' .
            (strlen($this->vars['joins_use']) ? '
' : '') . $this->vars['joins_use'] . '

/**
 * Abstract ' . $this->config->getClassname() . ' data class.
 * /!\ AUTO GENERATED FILE. DO NOT EDIT THIS FILE.
 * THIS FILE IS OVERWRITTEN WHEN THE ORM SCRIPT GENERATOR IS RUN.
 * You can add you specific code in child class: ' . $this->config->getClassname() . '
 *
 * @author  ' . $this->config->getAuthor() . '
 */
abstract class Abstract' . $this->config->getClassname() . ' extends AbstractData
{' . $this->vars['properties'] . '
' . $this->vars['cache_key'] . '
' . $this->vars['getters'] . '
' . $this->vars['setters'] . '
' . $this->vars['joins'] . '}
';
        $content = str_replace("\r\n", "\n", $content);

        if (false === file_put_contents($file, $content)) {
            throw new RuntimeException('Unable to write file content! (file: ' . $file . ')');
        }
    }

    /**
     * Generate child class if not already exists.
     *
     * @param  string $dir Directory for class
     * @return void
     * @throws RuntimeException
     */
    protected function generateDataFile($dir)
    {
        $file = $dir . '/' . $this->config->getClassname() . '.php';

        if (file_exists($file)) {
            return;
        }

        if (!is_readable($file) && false === file_put_contents($file, '')) {
            throw new RuntimeException('Cannot create empty class file: ' . $file);
        }

        $namespace  = $this->config->getBaseNamespaceForData();
        $extends    = 'Abstract' . $this->config->getClassname();

        $content = '<?' . 'php

/**
 * Copyright (c) ' . $this->config->getAuthor() . '
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ' . $namespace . ';

/**
 * DataMapper Data class for table "' . $this->config->getDbTable() . '"
 *
 * @author  ' . $this->config->getAuthor() . '
 */
class ' . $this->config->getClassname() . ' extends ' . $extends . '
{
}
';
        if (false === file_put_contents($file, $content)) {
            throw new RuntimeException('Unable to write file content! (file: ' . $file . ')');
        }
    }

    /**
     * Generate main class model.
     *
     * @return void
     * @throws RuntimeException
     */
    protected function generateMapperFiles()
    {
        $dir  = $this->baseDir . $this->config->getBasePathForMapper() . '/' . trim(str_replace('\\', '/', $this->config->getNamespace()), '/');

        if (!is_dir($dir) && false === mkdir($dir, 0755, true)) {
            throw new RuntimeException('Cannot create directory: ' . $dir);
        }

        $this->generateMapperFileAbstract($dir);
        $this->generateMapperFile($dir);
    }

    /**
     * Generate abstract file mapper class.
     *
     * @param  string $dir Directory for class
     * @return void
     * @throws RuntimeException
     */
    protected function generateMapperFileAbstract($dir)
    {
        $file = $dir . '/Abstract' . $this->config->getClassname() . 'Mapper.php';

        if (!is_readable($file) && false === file_put_contents($file, '')) {
            throw new RuntimeException('Cannot create empty class file: ' . $file);
        }

        $namespace  = $this->config->getBaseNamespaceForMapper();
        $useData    = '';

        $content = '<?' . 'php

/**
 * Copyright (c) ' . $this->config->getAuthor() . '
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ' . $namespace . ';

use Eureka\Component\Orm\DataMapper\AbstractMapper;
use ' . $this->config->getBaseNamespaceForData() . $useData . '\\' . $this->config->getClassname() . ';

/**
 * Abstract ' . $this->config->getClassname() . ' mapper class.
 *
 * /!\ AUTO GENERATED FILE. DO NOT EDIT THIS FILE.
 * THIS FILE IS OVERWRITTEN WHEN THE ORM SCRIPT GENERATOR IS RAN.
 * You can add you specific code in child class: ' . $this->config->getClassname() . '
 *
 * @author  ' . $this->config->getAuthor() . '
 */
abstract class Abstract' . $this->config->getClassname() . 'Mapper extends AbstractMapper
{
    /**
     * @var string $dataClass Name of class use to instance DataMapper Data class.
     */
    protected $dataClass = \'' . $this->config->getBaseNamespaceForData() . '\\' . $this->config->getClassname() . '\';

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
     * @var bool $isCacheEnabled If cache is enable or not by default.
     */
    protected $isCacheEnabled = ' . var_export($this->config->hasCache(), true) . ';

    /**
     * Get first row corresponding of the id.
     *
     * @param  int $id
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
     * Create new instance of extended AbstractData class & return it.
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
            throw new RuntimeException('Unable to write file content! (file: ' . $file . ')');
        }
    }

    /**
     * Generate main file mapper class.
     *
     * @param  string $dir Directory for class
     * @return void
     * @throws RuntimeException
     */
    protected function generateMapperFile($dir)
    {
        $file = $dir . '/' . $this->config->getClassname() . 'Mapper.php';

        if (file_exists($file)) {
            return;
        }

        if (!is_readable($file) && false === file_put_contents($file, '')) {
            throw new RuntimeException('Cannot create empty class file: ' . $file);
        }

        $namespace  = $this->config->getBaseNamespaceForMapper();

        $extends    = 'Abstract' . $this->config->getClassname() . 'Mapper';

        $content = '<?' . 'php

/**
 * Copyright (c) ' . $this->config->getAuthor() . '
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ' . $namespace . ';

/**
 * DataMapper Mapper class for table "' . $this->config->getDbTable() . '"
 *
 * @author  ' . $this->config->getAuthor() . '
 */
class ' . $this->config->getClassname() . 'Mapper extends ' . $extends . '
{
}
';
        if (false === file_put_contents($file, $content)) {
            throw new RuntimeException('Unable to write file content! (file: ' . $file . ')');
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
