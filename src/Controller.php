<?php

declare(strict_types=1);

namespace Medas\HttpFileClient;

use Medas\Core\{Attributes\Service, Interfaces\DataStorage};
use Medas\HttpClient\SimpleRequests;

#[Service]
readonly class Controller implements DataStorage
{
    public function __construct(
        private ClientManager  $clientManager,
        private SimpleRequests $simpleRequests,
    )
    {
    }

    public function exists(string $path, Client $client = null): bool
    {
        $client ??= $this->clientManager->default();

        return $this->simpleRequests->get($client->url . '/' . $path, ['return' => 'null'])->code === 204;
    }

    public function delete(string $path, Client $client = null): bool
    {
        $client ??= $this->clientManager->default();

        return $this->simpleRequests->delete($client->url . '/' . $path)->code === 200;
    }

    public function content(string $path, Client $client = null): string|null
    {
        $client ??= $this->clientManager->default();

        return $this->simpleRequests->get($client->url . '/' . $path)->body;
    }

    public function size(string $path, Client $client = null): string|null
    {
        $client ??= $this->clientManager->default();

        return $this->simpleRequests->get($client->url . '/' . $path, ['return' => 'size'])->body;
    }

    public function modificationTime(string $path, Client $client = null): \DateTime|null
    {
        $client ??= $this->clientManager->default();

        $timestamp = $this->simpleRequests->get(
            $client->url . '/' . $path,
            ['return' => 'modificationTime']
        )->body;

        return $timestamp === null ? null : new \DateTime('@' . $timestamp);
    }

    public function store(
        string    $path,
        string    $content,
        \DateTime $modificationTime = null,
        Client    $client = null,
    ): bool
    {
        $client ??= $this->clientManager->default();

        return $this->simpleRequests->post($client->url . '/' . $path, [
            'content' => $content,
            'modificationTime' => $modificationTime,
        ])->code === 201;
    }
}
