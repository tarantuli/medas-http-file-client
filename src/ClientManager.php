<?php

declare(strict_types=1);

namespace Medas\HttpFileClient;

use Medas\Core\Attributes\Service;

#[Service]
class ClientManager
{
    /** @var array<string, Client> */
    private array $clients = [];

    private Client|null $default = null;

    public function register(Client $client, bool $asDefault = false): void
    {
        $this->clients[$client->url] = $client;

        if ($asDefault || count($this->clients) === 1) {
            $this->default = $client;
        }
    }

    public function default(): Client
    {
        if ($this->default === null) {
            throw new \RuntimeException('No HTTP file client has been registered.');
        }

        return $this->default;
    }

    public function find(string $url): Client|null
    {
        return $this->clients[$url] ?? null;
    }
}
