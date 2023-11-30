<?php

declare(strict_types=1);

namespace Medas\HttpFileClient;

readonly class Client
{
    public function __construct(
        public string $url,
    )
    {
    }
}
