<?php

declare(strict_types=1);

use Medas\HttpFileClient\HttpFileClientPackage;
use Medas\ObjectInstantiator\{ObjectInstantiator, ObjectInstantiatorPackage};
use Medas\ServiceManager\{ServiceConfigBuilder, ServiceManager};

chdir(__DIR__);

new ServiceManager(function (): ServiceConfigBuilder {
    $config = new ServiceConfigBuilder(ObjectInstantiator::class);

    $config->addPackages([
        HttpFileClientPackage::instance(),
        ObjectInstantiatorPackage::instance(),
    ]);

    return $config;
});
