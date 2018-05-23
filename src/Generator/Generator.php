<?php

/*
 * Copyright (c) Romain Cottard
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eureka\Component\Orm\Generator;

use Eureka\Component\Database\Connection;
use Eureka\Component\Orm\Config;
use Eureka\Component\Orm\Enumerator\JoinRelation;
use Eureka\Component\Orm\Enumerator\JoinType;
use Eureka\Eurekon\IO\Out;
use Eureka\Eurekon\Style\Color;
use Eureka\Eurekon\Style\Style;

/**
 * Class to generate model classes. Better way to manipulate table !
 *
 * @author Romain Cottard
 */
class Generator
{
    /** @var Config\ConfigInterface $config ORM configuration object. */
    protected $config = null;

    /** @var array $vars List of variables to replace in template */
    protected $vars = [];

    /** @var Column[] $columns List of columns to treat for current table. */
    protected $columns = [];

    /** @var bool $verbose Verbose active or not. */
    protected $verbose = true;

    /** @var bool $hasRepository Generate repository interface or not. */
    protected $hasRepository = false;

    /** @var string $rootDir */
    protected $rootDir = __DIR__ . '/../../..';

    /** @var Connection $connection */
    protected $connection = null;

    /**
     * Set verbose mode
     *
     * @param  bool $verbose
     * @return $this
     */
    public function setVerbose($verbose)
    {
        $this->verbose = (bool) $verbose;

        return $this;
    }

    /**
     * Set if has repository mode
     *
     * @param  bool $hasRepository
     * @return $this
     */
    public function setHasRepository($hasRepository)
    {
        $this->hasRepository = (bool) $hasRepository;

        return $this;
    }

    /**
     * Set root directory
     *
     * @param  string $rootDir
     * @return $this
     */
    public function setRootDirectory($rootDir)
    {
        $this->rootDir = (string) $rootDir;

        return $this;
    }

    /**
     * Set database connection.
     *
     * @param  Connection $connection
     * @return $this
     */
    public function setConnection(Connection $connection)
    {
        $this->connection = $connection;

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
        $this->vars = [];

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
        $statement = $this->connection->query('SHOW FULL COLUMNS FROM ' . $this->config->getDbTable());

        $this->columns = [];
        while (false !== ($column = $statement->fetch(Connection::FETCH_OBJ))) {
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
        $this->displayTitle(' * Build Data classes for table "' . $this->config->getDbTable() . '"' . PHP_EOL);
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
        $properties       = '';
        $hasAutoincrement = false;

        foreach ($this->columns as $column) {
            $properties       .= "\n" . $column->getProperty();
            $hasAutoincrement |= $column->isAutoIncrement();
        }

        if ($hasAutoincrement) {
            $properties = '
    /** @var bool $hasAutoIncrement If data has auto increment value. */
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
            $getters   .= $separator . $column->getGetter();
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
        $keys = [];

        foreach ($this->columns as $column) {
            if ($column->isPrimaryKey()) {
                $keys[] = '$this->' . $column->getMethodNameGet() . '()';
            }
        }

        $key = "'orm." . $this->config->getCachePrefix() . ".' . " . implode(" . '.' . ", $keys);

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
            $setters   .= $separator . $column->getSetter();
            $separator = "\n";
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
        $joins        = ['one' => [], 'many' => []];
        $joinsUse     = [];
        $joinsMappers = [];

        foreach ($this->config->getAllJoin() as $name => $join) {

            $config = $join['instance'];

            if (!($config instanceof Config\ConfigInterface)) {
                throw new \LogicException('Joined class is not an instance of ConfigInterface! (class: ' . get_class($config) . ')');
            }

            $class = $config->getClassname();
            $name  = (!empty($join['name']) ? $join['name'] : $class);

            if ('one' === $join['type']) {
                $joinMethod = $this->buildDataJoinsOne($config, $name, $join['keys']);
                $joinsMappers['get' . ucfirst($name)] = [
                    'config'  => $config->getDbConfig(),
                    'service' => $config->getDbService(),
                    'class'   => $config->getClassname() . 'Mapper::class',
                ];
                $joinsUse[] = 'use ' . $config->getBaseNamespaceForData() . '\\' . $config->getClassname() . ';';
            } else {
                $joinMethod = $this->buildDataJoinsMany($config, $name, $join['keys']);
                $joinsMappers['getAll' . ucfirst($name)] = [
                    'config'  => $config->getDbConfig(),
                    'service' => $config->getDbService(),
                    'class'  => '\\' . $config->getBaseNamespaceForMapper() . '\\' . $config->getClassname() . 'Mapper::class',
                ];
            }

            $joinsUse[] = 'use ' . $config->getBaseNamespaceForMapper() . '\\' . $config->getClassname() . 'Mapper;';
            $joins[$join['type']][] = $joinMethod;
        }

        $this->vars['joins_use']  = implode("\n", $joinsUse);
        $this->vars['joins']      = implode("\n", $joins['one']) . implode("\n", $joins['many']);
    }

    /**
     * Build join one getters
     *
     * @param  Config\ConfigInterface $config
     * @param  string $name
     * @param  array $joinKeys
     * @return string
     * @throws \LogicException
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
                \'' . $mappedBy . '\' => $this->' . $column->getMethodNameGet() . '(),';
        }

        if (empty($keys)) {
            throw new \LogicException('Empty keys list for Mapper::findByKeys() method !');
        }

        $propertyCacheName        = 'joinOneCache' . ucfirst($name);
        $dataClassName            = $config->getClassname();
        $mapperClassName          = $config->getClassname() . 'Mapper';
        $this->vars['properties'] .= '

    /** @var ' . $dataClassName . ' $' . $propertyCacheName . ' Cache property for ' . $propertyCacheName . ' */
    protected $' . $propertyCacheName . ' = null;';

        //~ Generate method
        return '
    /**
     * Get ' . $config->getClassname() . ' data object.
     *
     * @param  bool $isForceReload
     * @return ' . $dataClassName . '
     * @throws \Psr\Cache\InvalidArgumentException
     * @throws \Exception
     */
    public function get' . ucfirst($name) . '($isForceReload = false)
    {
        if ($isForceReload || null === $this->' . $propertyCacheName . ') {
            if (!isset($this->mappers[' . $mapperClassName . '::class])) {
                throw new \LogicException("Undefined mapper " . ' . $mapperClassName . '::class . "!");
            }

            $mapper = $this->mappers[' . $mapperClassName . '::class];

            $this->' . $propertyCacheName . ' = $mapper->findByKeys([' . $keys . '
            ]);
        }

        return $this->' . $propertyCacheName . ';
    }

    /**
     * Set ' . $config->getClassname() . ' entity instance.
     *
     * @param ' . $dataClassName . ' $entity
     * @return $this
     */
    public function set' . ucfirst($name) . '(' . $dataClassName . ' $entity)
    {
        $this->' . $propertyCacheName . ' = $entity;
        return $this;
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
     * @throws \LogicException
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
            $queryBuilder->addWhere(\'' . $mappedBy . '\', $this->' . $column->getMethodNameGet() . '());';
        }

        if (empty($keys)) {
            throw new \LogicException('Empty keys list for join all method !');
        }

        $propertyCacheName        = 'joinManyCache' . ucfirst($name);
        $dataClassName            = '\\' . $config->getBaseNamespaceForData() . '\\' . $config->getClassname();
        $mapperClassName          = $config->getClassname() . 'Mapper';
        $this->vars['properties'] .= '

    /** @var ' . $dataClassName . '[] $' . $propertyCacheName . ' Cache property for ' . $propertyCacheName . ' */
     protected $' . $propertyCacheName . ' = null;';

        //~ Generate method
        return '
    /**
     * Get list of ' . $config->getClassname() . ' data objects.
     *
     * @param  bool $isForceReload
     * @return ' . $dataClassName . '[]
     * @throws \Psr\Cache\InvalidArgumentException
     * @throws \Exception
     */
    public function getAll' . ucfirst($name) . '($isForceReload = false)
    {
        if ($isForceReload || null === $this->' . $propertyCacheName . ') {
            if (!isset($this->mappers[' . $mapperClassName . '::class])) {
                throw new \LogicException("Undefined mapper " . ' . $mapperClassName . '::class . "!");
            }

            $mapper       = $this->mappers[' . $mapperClassName . '::class];
            $queryBuilder = $mapper->getQueryBuilder();' . $keys . '
            $this->' . $propertyCacheName . ' = $mapper->select($queryBuilder);
        }

        return $this->' . $propertyCacheName . ';
    }

    /**
     * Set ' . $config->getClassname() . ' data objects.
     *
     * @param ' . $dataClassName . '[] $entities
     * @return $this
     */
    public function setAll' . ucfirst($name) . '(array $entities)
    {
        $this->' . $propertyCacheName . ' = $entities;
        return $this;
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
        $this->displayTitle(' * Build Mapper classes for table "' . $this->config->getDbTable() . '"' . PHP_EOL);

        $this->display('  > build    [  0%]: field...        ' . PHP_EOL);
        $this->buildMapperFields();
        $this->display('  > build    [ 33%]: primary keys... ' . PHP_EOL);
        $this->buildMapperPrimaryKeys();
        $this->display('  > build    [ 66%]: joined configs... ' . PHP_EOL);
        $this->buildMapperJoinsConfig();
        $this->display('  > build    [100%]: done !          ' . PHP_EOL);
        $this->display(PHP_EOL);
    }

    /**
     * Build fields var
     *
     * @return void
     */
    protected function buildMapperFields()
    {
        $fields       = [];
        $dataNamesMap = '';

        foreach ($this->columns as $column) {
            $field        = $column->getName();
            $fields[]     = "        '" . $field . "'";
            $dataNamesMap .= "
        '" . $field . "' => [
            'get'      => '" . $column->getMethodNameGet() . "',
            'set'      => '" . $column->getMethodNameSet() . "',
            'property' => '" . $column->getPropertyName() . "',
        ],";
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
        $fields = [];

        foreach ($this->columns as $column) {
            if ($column->isPrimaryKey()) {
                $fields[] = "        '" . $column->getName() . "'";
            }
        }

        $this->vars['db_primary_keys'] = implode(",\n", $fields);
    }

    /**
     * Build joins config var
     *
     * @return void
     */
    protected function buildMapperJoinsConfig()
    {
        $joinsConfig = '';

        foreach ($this->config->getAllJoin() as $name => $join) {
            if (!isset($join['smart_join']) || (bool) $join['smart_join'] !== true) {
                continue;
            }

            $config = $join['instance'];

            if (!($config instanceof Config\ConfigInterface)) {
                throw new \LogicException('Joined class is not an instance of ConfigInterface! (class: ' . get_class($config) . ')');
            }

            $joinsConfig .= "
        '${name}' => [
            'mapper'   => \\" . $config->getBaseNamespaceForMapper() . '\\' . $config->getClassname() . "Mapper::class,
            'type'     => '" . (!empty($join['type']) ? strtoupper($join['type']) : JoinType::INNER) . "',
            'relation' => '" . (!empty($join['relation']) ? $join['relation'] : JoinRelation::ONE) . "',
            'keys'     => [" . var_export(key($join['keys']), true) . " => " . var_export(current($join['keys']), true) . "],
        ],";
        }

        $this->vars['db_joins_config'] = $joinsConfig;
    }

    /**
     * Generate classes
     *
     * @return void
     */
    protected function generateClasses()
    {
        $this->displayTitle(' * Generate classes files for Data & Mapper ' . PHP_EOL);

        $this->display('  > generate [  0%]: Entities Files       ' . "\r");
        $this->generateDataFiles();

        $this->display('  > generate [ 33%]: Mappers Files         ' . "\r");
        $this->generateMapperFiles();

        if ($this->hasRepository) {
            $this->display('  > generate [ 66%]: Repository Files      ' . "\r");
            $this->generateRepositoryFiles();
        }

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
        $dir = $this->rootDir . '/' . $this->config->getBasePathForData() . '/';

        $dirAbstract = $dir . '/Abstracts';

        if (!is_dir($dirAbstract) && false === mkdir($dirAbstract, 0755, true)) {
            throw new \RuntimeException('Cannot create directory: ' . $dir);
        }

        $this->generateDataFileAbstract($dirAbstract);
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
        $file = $dir . '/Abstract' . $this->config->getClassname() . '.php';

        if (!is_readable($file) && false === file_put_contents($file, '')) {
            throw new \RuntimeException('Cannot create empty class file: ' . $file);
        }

        $namespace = $this->config->getBaseNamespaceForData() . '\\Abstracts';
        $currentNamespace = substr(__NAMESPACE__, 0, strrpos(__NAMESPACE__,'\\'));

        $content = '<?' . 'php

/*
 * Copyright (c) ' . $this->config->getCopyright() . '
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ' . $namespace . ';

use ' . $currentNamespace . '\AbstractEntity;' . (strlen($this->vars['joins_use']) ? '
' : '') . $this->vars['joins_use'] . '

/**
 * Abstract ' . $this->config->getClassname() . ' data class.
 * /!\ AUTO GENERATED FILE. DO NOT EDIT THIS FILE.
 * THIS FILE IS OVERWRITTEN WHEN THE ORM SCRIPT GENERATOR IS RUN.
 * You can add you specific code in child class: ' . $this->config->getClassname() . '
 *
 * @author ' . $this->config->getAuthor() . '
 */
abstract class Abstract' . $this->config->getClassname() . ' extends AbstractEntity
{' . $this->vars['properties'] . '
' . $this->vars['cache_key'] . '
' . $this->vars['getters'] . '
' . $this->vars['setters'] . '
' . $this->vars['joins'] . '}
';
        $content = str_replace("\r\n", "\n", $content);

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

        $namespace = $this->config->getBaseNamespaceForData();

        $extends = 'Abstracts\\Abstract' . $this->config->getClassname();

        $content = '<?' . 'php

/*
 * Copyright (c) ' . $this->config->getCopyright() . '
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ' . $namespace . ';

use ' . substr(__NAMESPACE__, 0, strrpos(__NAMESPACE__, '\\')) . '\EntityInterface;

/**
 * DataMapper Data class for table "' . $this->config->getDbTable() . '"
 *
 * @author ' . $this->config->getAuthor() . '
 */
class ' . $this->config->getClassname() . ' extends ' . $extends . ' implements EntityInterface
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
        $dir = $this->rootDir . '/' . $this->config->getBasePathForMapper();

        $dirAbstract = $dir . '/Abstracts';

        if (!is_dir($dirAbstract) && false === mkdir($dirAbstract, 0755, true)) {
            throw new \RuntimeException('Cannot create directory: ' . $dir);
        }

        $this->generateMapperFileAbstract($dirAbstract);
        $this->generateMapperFile($dir);
    }

    /**
     * Generate main class model.
     *
     * @return void
     * @throws \RuntimeException
     */
    protected function generateRepositoryFiles()
    {
        $dir = $this->rootDir . '/' . $this->config->getBasePathForRepository();

        $this->generateRepositoryFile($dir);
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
        $file = $dir . '/Abstract' . $this->config->getClassname() . 'Mapper.php';

        if (!is_readable($file) && false === file_put_contents($file, '')) {
            throw new \RuntimeException('Cannot create empty class file: ' . $file);
        }

        $namespace = $this->config->getBaseNamespaceForMapper() . '\\Abstracts';
        $currentNamespace = substr(__NAMESPACE__, 0, strrpos(__NAMESPACE__,'\\'));

        $content = '<?' . 'php

/*
 * Copyright (c) ' . $this->config->getCopyright() . '
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ' . $namespace . ';

use ' . $currentNamespace . '\AbstractMapper;
use ' . $this->config->getBaseNamespaceForData() . '\\' . $this->config->getClassname() . ';

/**
 * Abstract ' . $this->config->getClassname() . ' mapper class.
 *
 * /!\ AUTO GENERATED FILE. DO NOT EDIT THIS FILE.
 * THIS FILE IS OVERWRITTEN WHEN THE ORM SCRIPT GENERATOR IS RUN.
 * You can add you specific code in child class: ' . $this->config->getClassname() . '
 *
 * @author ' . $this->config->getAuthor() . '
 */
abstract class Abstract' . $this->config->getClassname() . 'Mapper extends AbstractMapper
{
    /** @var string $dataClass Name of class use to instance DataMapper Data class. */
    protected $dataClass = ' . $this->config->getClassname() . '::class;

    /** @var string $table Table name */
    protected $table = \'' . $this->config->getDbTable() . '\';

    /** @var string[] $fields List of fields */
    protected $fields = [
' . $this->vars['db_fields'] . '
    ];

    /** @var string[] $primaryKeys List of primary keys */
    protected $primaryKeys = [
' . $this->vars['db_primary_keys'] . '
    ];

    /** @var string[] $dataNamesMap List of mapped names */
    protected $dataNamesMap = [
' . $this->vars['data_names_map'] . '
    ];
    
    /** @var string[][] $joinsConfig List of config for smart joins */
    protected $joinsConfig = [
' . $this->vars['db_joins_config'] . '
    ];

    /** @var bool $isCacheEnabled If cache is enable or not by default. */
    protected $isCacheEnabled = ' . var_export($this->config->hasCache(), true) . ';
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

        $namespace  = $this->config->getBaseNamespaceForMapper();
        $extends    = 'Abstracts\\Abstract' . $this->config->getClassname() . 'Mapper';
        if ($this->hasRepository) {
            $interface    = $this->config->getClassname() . 'RepositoryInterface';
            $useNamespace = $this->config->getBaseNamespaceForRepository() . '\\' . $interface;
        } else {
            $interface    = 'RepositoryInterface';
            $useNamespace = substr(__NAMESPACE__, 0, strrpos(__NAMESPACE__, '\\')) . '\\' . $interface;
        }

        $content = '<?' . 'php

/*
 * Copyright (c) ' . $this->config->getCopyright() . '
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ' . $namespace . ';

use ' . $useNamespace . ';

/**
 * DataMapper Mapper class for table "' . $this->config->getDbTable() . '"
 *
 * @author ' . $this->config->getAuthor() . '
 */
class ' . $this->config->getClassname() . 'Mapper extends ' . $extends . ' implements ' . $interface . '
{
}
';
        if (false === file_put_contents($file, $content)) {
            throw new \RuntimeException('Unable to write file content! (file: ' . $file . ')');
        }
    }

    /**
     * Generate abstract file mapper class.
     *
     * @param  string $dir Directory for class
     * @return void
     * @throws \RuntimeException
     */
    protected function generateRepositoryFile($dir)
    {
        $file = $dir . '/' . $this->config->getClassname() . 'RepositoryInterface.php';

        if (file_exists($file)) {
            return;
        }

        if (!is_readable($file) && false === file_put_contents($file, '')) {
            throw new \RuntimeException('Cannot create empty class file: ' . $file);
        }

        $namespace = $this->config->getBaseNamespaceForRepository();
        $currentNamespace = substr(__NAMESPACE__, 0, strrpos(__NAMESPACE__,'\\'));

        $content = '<?' . 'php

/*
 * Copyright (c) ' . $this->config->getCopyright() . '
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ' . $namespace . ';

use ' . $currentNamespace . '\Query\QueryBuilderInterface;
use ' . $currentNamespace . '\Query\SelectBuilder;
use ' . $currentNamespace . '\RepositoryInterface;
use ' . $this->config->getBaseNamespaceForData() . '\\' . $this->config->getClassname() . ';

/**
 * ' . $this->config->getClassname() . ' repository interface.
 *
 * @author ' . $this->config->getAuthor() . '
 */
interface ' . $this->config->getClassname() . 'RepositoryInterface extends RepositoryInterface
{
    /**
     * {@inheritdoc}
     * @return ' . $this->config->getClassname() . '
     */
    public function findById($id);

    /**
     * {@inheritdoc}
     * @return ' . $this->config->getClassname() . '
     */
    public function findByKeys(array $primaryKeys);

    /**
     * {@inheritdoc}
     * @return ' . $this->config->getClassname() . '[]
     */
    public function findAllByKeys(array $keys);

    /**
     * {@inheritdoc}
     * @return ' . $this->config->getClassname() . '
     */
    public function newEntity(\stdClass $row = null, $exists = false);
    
    /**
     * {@inheritdoc}
     * @return ' . $this->config->getClassname() . '[]
     */
    public function select(SelectBuilder $queryBuilder);

    /**
     * {@inheritdoc}
     * @return ' . $this->config->getClassname() . '
     */
    public function selectOne(SelectBuilder $queryBuilder);

    /**
     * {@inheritdoc}
     * @return ' . $this->config->getClassname() . '[]
     */
    public function query(QueryBuilderInterface $queryBuilder);
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
            Out::std($text, '');
        }
    }

    /**
     * Display text only if is verbose mode (not for phpunit)
     *
     * @param string $text
     */
    protected function displayTitle($text)
    {
        if ($this->verbose) {
            Out::std((new Style($text))->colorForeground(Color::GREEN)->bold(), '');
        }
    }
}
