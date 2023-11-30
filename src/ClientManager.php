<?php

declare(strict_types=1);

namespace Medas\HttpFileClient;

use Medas\Core\Attributes\Service;

#[Service]
class ClientManager
{
    /** @var Client[] */
    private array $clients = [];

    private Client $default;

    public function register(Client $client, bool $asDefault = false): void
    {
        $this->clients[] = $client;

        if ($asDefault || count($this->clients) === 1) {
            $this->default = $client;
        }
    }

    public function default(): Client
    {
        return $this->default;
    }
}
