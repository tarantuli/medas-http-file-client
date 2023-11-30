<?php

declare(strict_types=1);

namespace Medas\HttpFileClient;

use Medas\Core\AsSingleton;
use Medas\HttpClient\HttpClientPackage;
use Medas\ServiceManager\BasePackage;

class HttpFileClientPackage extends BasePackage
{
    use AsSingleton;

    public function dependencies(): array
    {
        return [
            HttpClientPackage::instance(),
        ];
    }

    public function sourceDirectory(): string
    {
        return __DIR__;
    }
}
