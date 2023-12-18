<?php

declare(strict_types=1);

namespace Medas\HttpFileClient;

use Medas\Core\{Attributes\Service, Interfaces\DataStorage};
use Medas\HttpClient\{Body, Request, RequestController, ResponseCodes};

#[Service]
readonly class Controller implements DataStorage
{
    public function __construct(
        private ClientManager     $clientManager,
        private RequestController $requestController,
    )
    {
    }

    public function exists(string $path, Client $client = null): bool
    {
        $request = $this->createRequest($client, $path, ['return' => 'null']);

        return $this->requestController->execute($request)->code === ResponseCodes::NO_CONTENT;
    }

    public function delete(string $path, Client $client = null): bool
    {
        $request = $this->createRequest($client, $path);

        $request->method = 'DELETE';

        return $this->requestController->execute($request)->code === ResponseCodes::OK;
    }

    public function content(string $path, Client $client = null): string|null
    {
        $request = $this->createRequest($client, $path);

        return $this->requestController->execute($request)->body;
    }

    public function size(string $path, Client $client = null): string|null
    {
        $request = $this->createRequest($client, $path, ['return' => 'size']);

        return $this->requestController->execute($request)->body;
    }

    public function modificationTime(string $path, Client $client = null): \DateTime|null
    {
        $request = $this->createRequest($client, $path, ['return' => 'modificationTime']);
        $timestamp = $this->requestController->execute($request)->body;

        return $timestamp === null ? null : new \DateTime('@' . $timestamp);
    }

    public function store(
        string    $path,
        string    $content,
        \DateTime $modificationTime = null,
        Client    $client = null,
    ): bool
    {
        $request = $this->createRequest($client, $path);

        $request->method = 'POST';

        $request->body = new Body([
            'content' => $content,
            'modificationTime' => $modificationTime,
        ], 'application/json');

        return $this->requestController->execute($request)->code === ResponseCodes::CREATED;
    }

    private function createRequest(Client|null $client, string $path, array $queryArguments = []): Request
    {
        $client ??= $this->clientManager->default();
        $request = new Request($client->url . '/' . $path);

        if ($client->authorizationHeader !== null) {
            $request->headers['Authorization'] = $client->authorizationHeader;
        }

        $request->queryArguments = $queryArguments;

        return $request;
    }
}
