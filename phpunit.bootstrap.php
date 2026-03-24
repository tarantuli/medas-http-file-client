<?php

declare(strict_types=1);

use Medas\HttpFileClient\HttpFileClientPackage;
use Medas\ObjectInstantiator\{ObjectInstantiator, ObjectInstantiatorPackage};
use Medas\ServiceManager\{ServiceConfig, ServiceManager};

chdir(__DIR__);

new ServiceManager(function (): ServiceConfig {
    $config = new ServiceConfig(ObjectInstantiator::class);

    $config->addPackages([
        HttpFileClientPackage::instance(),
        ObjectInstantiatorPackage::instance(),
    ]);

    return $config;
});
