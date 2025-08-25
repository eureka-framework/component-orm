<?php

use ShipMonk\ComposerDependencyAnalyser\Config\Configuration;

$config = new Configuration();

$config
    ->addPathToScan(__DIR__ . '/../src', isDev: false)
    ->addPathToScan(__DIR__ . '/../tests', isDev: true)
    ->ignoreUnknownClassesRegex('`Eureka\\\\Component\\\\Orm\\\\Tests\\\\Unit\\\\Generated.+`')
;


return $config;
